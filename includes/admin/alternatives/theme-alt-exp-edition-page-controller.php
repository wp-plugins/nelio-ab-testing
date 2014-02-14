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
	require_once( NELIOAB_MODELS_DIR . '/page-description.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/theme-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/goals/page-accessed-goal.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/theme-alt-exp-edition-page.php' );

	require_once( NELIOAB_ADMIN_DIR . '/alternatives/alt-exp-super-controller.php' );
	class NelioABThemeAltExpEditionPageController extends NelioABAltExpSuperController {

		public static function get_instance() {
			return new NelioABThemeAltExpEditionPageController();
		}

		public static function build() {
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
			if ( !empty( $nelioab_admin_controller->data ) ) {
				$experiment = $nelioab_admin_controller->data;
			}
			else {
				$experiment = new NelioABThemeAlternativeExperiment( -1 );
				$experiment->clear();
			}


			// Checking whether there are pages or posts available
			// ---------------------------------------------------

			$options_for_posts = array(
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'asc' );
			$list_of_posts = get_posts( $options_for_posts );
			$list_of_pages = get_pages();

			if ( ( count( $list_of_pages ) + count( $list_of_posts ) ) == 0 ) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/error-page.php' );
				$view = new NelioABErrorPage(
					__( 'There are no posts or posts available.', 'nelioab' ) );
				return $view;
			}


			// Checking whether there is more than one theme
			// available
			// ---------------------------------------------------
			$themes = wp_get_themes();
			if ( count( $themes ) < 2 ) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/error-page.php' );
				$view = new NelioABErrorPage(
					__( 'There is only one theme available.<br />Please, install one or more themes to create an experiment of this type.', 'nelioab' ) );
				return $view;
			}


			// If everything is OK, we keep going!
			// ---------------------------------------------------

			// We select appspot_alternatives...
			foreach( $experiment->get_appspot_alternatives() as $alt )
				$experiment->add_selected_theme( $alt->get_value(), $alt->get_name() );

			// Creating the view
			$view = $this->create_view();

			// Experiment information
			$view->set_experiment_id( $experiment->get_id() );
			$view->set_experiment_name( $experiment->get_name() );
			$view->set_experiment_descr( $experiment->get_description() );
			$goals = $experiment->get_goals();
			if ( count( $goals ) > 0 )
				$view->set_goal( $goals[0] );
			else
				$view->set_goal( new NelioABPageAccessedGoal( $experiment ) );
			$view->set_encoded_appspot_alternatives( $experiment->encode_appspot_alternatives() );
			$view->set_encoded_local_alternatives( $experiment->encode_local_alternatives() );

			$view->set_wp_pages( $list_of_pages );
			$view->set_wp_posts( $list_of_posts );

			$current_theme = wp_get_theme();
			$current_theme_id = $current_theme['Stylesheet'];
			$experiment->set_current_default_theme( $current_theme_id, $current_theme->offsetGet( 'Title' ) );
			$view->set_current_theme(
				$current_theme_id,
				$current_theme->offsetGet( 'Title' ),
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
					$experiment->is_theme_selected_locally( $id ) );
			}

			return $view;
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
			$exp->set_name( stripslashes( $_POST['exp_name'] ) );
			$exp->set_description( stripslashes( $_POST['exp_descr'] ) );

			$exp->load_encoded_appspot_alternatives( $_POST['appspot_alternatives'] );
			$exp->load_encoded_local_alternatives( $_POST['local_alternatives'] );

			$exp_goal = $this->build_goal_from_post_data( $exp );
			$exp->add_goal( $exp_goal );

			$current_theme = wp_get_theme();
			$current_theme_id = basename( $current_theme->get_stylesheet_directory() );
			$exp->set_current_default_theme( $current_theme_id, $current_theme->offsetGet( 'Title' ) );

			global $nelioab_admin_controller;
			$nelioab_admin_controller->data = $exp;
		}

	}//NelioABThemeAltExpEditionPageController

}

if ( isset( $_POST['nelioab_edit_ab_theme_exp_form'] ) ) {
	$controller = NelioABThemeAltExpEditionPageController::get_instance();
	$controller->manage_actions();
}

?>
