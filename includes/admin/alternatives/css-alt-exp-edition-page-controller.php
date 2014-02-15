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


if ( !class_exists( 'NelioABCssAltExpEditionPageController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/css-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/goals/page-accessed-goal.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/css-alt-exp-edition-page.php' );

	require_once( NELIOAB_ADMIN_DIR . '/alternatives/alt-exp-super-controller.php' );
	class NelioABCssAltExpEditionPageController extends NelioABAltExpSuperController {

		public static function get_instance() {
			return new NelioABCssAltExpEditionPageController();
		}

		public static function build() {
			$aux  = NelioABCssAltExpEditionPageController::get_instance();
			$view = $aux->do_build();
			$view->render();
		}

		public static function generate_html_content() {
			$aux  = NelioABCssAltExpEditionPageController::get_instance();
			$view = $aux->do_build();
			$view->render_content();
			die();
		}

		protected function do_build() {
			$title = __( 'Edit Experiment', 'nelioab' );

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
				$experiment = new NelioABCssAlternativeExperiment( -1 );
				$experiment->clear();
			}


			// If everything is OK, we keep going!
			// ---------------------------------------------------

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
			$view->set_alternatives( $experiment->get_alternatives() );
			$view->set_encoded_appspot_alternatives( $experiment->encode_appspot_alternatives() );
			$view->set_encoded_local_alternatives( $experiment->encode_local_alternatives() );

			$list_of_pages = get_pages();
			$options_for_posts = array(
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'asc' );
			$list_of_posts = get_posts( $options_for_posts );

			$view->set_wp_pages( $list_of_pages );
			$view->set_wp_posts( $list_of_posts );
			if ( isset( $_POST['action'] ) ) {
				if ( $_POST['action'] == 'show_quickedit_box' )
					$view->show_quickedit_box();
			}
			return $view;
		}

		public function create_view() {
			$title = __( 'Edit Experiment', 'nelioab' );
			return new NelioABCssAltExpEditionPage( $title );
		}

		public function add_alternative() {
			global $nelioab_admin_controller;
			$this->build_experiment_from_post_data();

			if ( isset( $_POST['new_alt_name'] ) ) {
				$alt_name = stripslashes( $_POST['new_alt_name'] );
				$exp = $nelioab_admin_controller->data;
				$exp->create_css_alternative( $alt_name );
			}
		}

		public function validate() {
			$ok_parent = parent::validate();

			// Check whatever is needed
			$ok = true;

			return $ok_parent && $ok;
		}

		public function edit_alternative_content() {
			// 1. Save any local changes
			global $nelioab_admin_controller;
			$this->build_experiment_from_post_data();
			$experiment = $nelioab_admin_controller->data;
			try {
				$experiment->save();
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}

			// 2. Redirect to the edit page
			$exp_id =  $experiment->get_id();
			$css_alt_id = 0;
			if ( isset( $_POST['content_to_edit'] ) ) {
				$css_alt_id = $_POST['content_to_edit'];
				$css_alt_id = $experiment->get_real_id_for_alt( $css_alt_id );
			}
			echo '[SUCCESS]' . admin_url() . 'admin.php?page=nelioab-css-edit&exp_id=' . $exp_id . '&css_id=' . $css_alt_id;
			die();
		}

		public function build_experiment_from_post_data() {
			$exp = new NelioABCssAlternativeExperiment( $_POST['exp_id'] );
			$exp->set_name( stripslashes( $_POST['exp_name'] ) );
			$exp->set_description( stripslashes( $_POST['exp_descr'] ) );

			$exp->load_encoded_appspot_alternatives( $_POST['appspot_alternatives'] );
			$exp->load_encoded_local_alternatives( $_POST['local_alternatives'] );

			$exp_goal = $this->build_goal_from_post_data( $exp );
			$exp->add_goal( $exp_goal );

			global $nelioab_admin_controller;
			$nelioab_admin_controller->data = $exp;
		}

		public function manage_actions() {
			if ( !isset( $_POST['action'] ) )
				return;

			parent::manage_actions();

			if ( $_POST['action'] == 'add_alt' )
				$this->add_alternative();

			if ( $_POST['action'] == 'show_quickedit_box' )
				$this->build_experiment_from_post_data();

			if ( $_POST['action'] == 'edit_alt_content' )
				if ( $this->validate() )
					$this->edit_alternative_content();

			if ( $_POST['action'] == 'hide_new_alt_box' )
				$this->build_experiment_from_post_data();

		}

	}//NelioABCssAltExpEditionPageController

}

if ( isset( $_POST['nelioab_edit_ab_css_exp_form'] ) ) {
	$controller = NelioABCssAltExpEditionPageController::get_instance();
	$controller->manage_actions();
}

?>
