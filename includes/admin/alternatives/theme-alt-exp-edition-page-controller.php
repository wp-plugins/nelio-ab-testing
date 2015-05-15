<?php
/**
 * Copyright 2013 Nelio Software S.L.
 * This script is distributed under the terms of the GNU General Public License.
 *
 * This script is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free Software
 * Foundation, either version 3 of the License.
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program.  If not, see <http://www.gnu.org/licenses/>.
 */


if ( !class_exists( 'NelioABThemeAltExpEditionPageController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/theme-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/theme-alt-exp-edition-page.php' );

	require_once( NELIOAB_ADMIN_DIR . '/alternatives/alt-exp-super-controller.php' );
	class NelioABThemeAltExpEditionPageController extends NelioABAltExpSuperController {

		public static function get_instance() {
			return new NelioABThemeAltExpEditionPageController();
		}

		public static function build() {
			// Check settings
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			$error = NelioABErrorController::build_error_page_on_invalid_settings();
			if ( $error ) return;

			$aux  = NelioABThemeAltExpEditionPageController::get_instance();
			$view = $aux->do_build();
			$view->render();
		}

		public static function generate_html_content() {
			$aux  = NelioABThemeAltExpEditionPageController::get_instance();
			$view = $aux->do_build();
			$view->render_content();
			die();
		}

		protected function do_build() {

			// Check settings
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			$error = NelioABErrorController::build_error_page_on_invalid_settings();
			if ( $error ) return;

			// We recover the experiment (if any)
			// ----------------------------------------------

			global $nelioab_admin_controller;
			$experiment = NULL;
			$other_names = array();
			if ( !empty( $nelioab_admin_controller->data ) ) {
				$experiment = $nelioab_admin_controller->data;
			}
			else {
				$experiment = new NelioABThemeAlternativeExperiment( -time() );
				$experiment->clear();
			}

			// ...and we also recover other experiment names (if any)
			if ( isset( $_POST['other_names'] ) ) {
				$other_names = json_decode( urldecode( $_POST['other_names'] ) );
			}
			else {
				foreach( NelioABExperimentsManager::get_experiments() as $aux ) {
					if ( $aux->get_id() != $experiment->get_id() )
						array_push( $other_names, $aux->get_name() );
				}
			}


			// Checking whether there are pages or posts available
			// ---------------------------------------------------

			$list_of_pages = get_pages();
			$options_for_posts = array(
				'posts_per_page' => 1 );
			$list_of_posts = get_posts( $options_for_posts );
			require_once( NELIOAB_UTILS_DIR . '/data-manager.php' );
			NelioABArrays::sort_posts( $list_of_posts );

			if ( ( count( $list_of_pages ) + count( $list_of_posts ) ) == 0 ) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/message-page.php' );
				$view = new NelioABMessagePage(
					__( 'There are no posts or pages available.', 'nelioab' ) );
				return $view;
			}


			// Checking whether there is more than one theme
			// available
			// ---------------------------------------------------
			$themes = wp_get_themes();
			usort( $themes, array( $this, 'sort_themes_alphabetically' ) );
			if ( count( $themes ) < 2 ) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/message-page.php' );
				$view = new NelioABMessagePage(
					__( 'There is only one theme available', 'nelioab' ),
					__( 'Please, install one or more themes to create an experiment of this type.', 'nelioab' ) );
				return $view;
			}


			// If everything is OK, we keep going!
			// ---------------------------------------------------

			$current_theme = wp_get_theme();
			$current_theme_id = $current_theme['Stylesheet'];
			$current_theme_name = $current_theme->offsetGet( 'Title' );

			// We select the alternatives
			$experiment->add_selected_theme( $current_theme_id, $current_theme_name );
			if ( isset( $_POST['nelioab_selected_themes'] ) ) {
				$selected_themes = json_decode( urldecode( $_POST['nelioab_selected_themes'] ) );
				if ( is_array( $selected_themes ) )
					foreach( $selected_themes as $theme )
						if ( isset( $theme->isSelected ) &&  $theme->isSelected )
							$experiment->add_selected_theme( $theme->value, $theme->name );
			}
			else {
				$ori = $experiment->get_original();
				if ( $ori )
					$experiment->add_selected_theme( $ori->get_value(), $ori->get_name() );
				foreach( $experiment->get_alternatives() as $alt )
					$experiment->add_selected_theme( $alt->get_value(), $alt->get_name() );
			}

			if ( isset( $_POST['nelioab_appspot_ids'] ) )
				$experiment->set_appspot_ids( json_decode( urldecode( $_POST['nelioab_appspot_ids'] ) ) );
			else
				$experiment->set_appspot_ids( $experiment->get_appspot_ids() );

			// Creating the view
			$view = $this->create_view();

			// Experiment information
			$view->set_basic_info(
				$experiment->get_id(),
				$experiment->get_name(),
				$experiment->get_description(),
				$experiment->get_finalization_mode(),
				$experiment->get_finalization_value()
			);

			// Experiment alternatives
			$view->set_selected_themes( $experiment->get_selected_themes() );
			$view->set_appspot_ids( $experiment->get_appspot_ids() );

			$view->set_current_theme(
				$current_theme_id,
				$current_theme_name,
				$current_theme->get_screenshot(),
				$current_theme->offsetGet( 'Author' ) );

			foreach ( $themes as $theme ) {
				$id = $theme['Stylesheet'];
				if ( $id == $current_theme_id )
					continue;
				$view->add_theme(
					$id,
					$theme->offsetGet( 'Title' ),
					$theme->get_screenshot(),
					$theme->offsetGet( 'Author' ),
					$experiment->is_theme_selected( $id ) );
			}

			// Goals
			$goals = $experiment->get_goals();
			foreach ( $goals as $goal )
				$view->add_goal( $goal->json4js() );

			if ( count( $goals ) == 0 ) {
				$new_goal = new NelioABAltExpGoal( $experiment );
				$new_goal->set_name( __( 'Default', 'nelioab' ) );
				$view->add_goal( $new_goal->json4js() );
			}

			return $view;
		}

		public function sort_themes_alphabetically( $a, $b ) {
			return strcasecmp( $a['Name'], $b['Name'] );
		}

		public function create_view() {
			return new NelioABThemeAltExpEditionPage();
		}

		public function validate() {
			$ok_parent = parent::validate();

			// Check whatever is needed
			$ok = true;

			return $ok_parent && $ok;
		}

		public function build_experiment_from_post_data() {
			$exp = new NelioABThemeAlternativeExperiment( $_POST['exp_id'] );
			$exp = $this->compose_basic_alt_exp_using_post_data( $exp );

			if ( isset( $_POST['nelioab_selected_themes'] ) ) {
				$selected_themes = json_decode( urldecode( $_POST['nelioab_selected_themes'] ) );
				if ( is_array( $selected_themes ) )
					foreach( $selected_themes as $theme )
						if ( isset( $theme->isSelected ) &&  $theme->isSelected )
							$exp->add_selected_theme( $theme->value, $theme->name );
			}

			if ( isset( $_POST['nelioab_appspot_ids'] ) )
				$exp->set_appspot_ids( json_decode( urldecode( $_POST['nelioab_appspot_ids'] ) ) );

			global $nelioab_admin_controller;
			$nelioab_admin_controller->data = $exp;
		}

	}//NelioABThemeAltExpEditionPageController

}

if ( isset( $_POST['nelioab_edit_ab_theme_exp_form'] ) ) {
	$controller = NelioABThemeAltExpEditionPageController::get_instance();
	$controller->manage_actions();
	if ( !$controller->validate() )
		$controller->print_ajax_errors();
}

