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


if ( !class_exists( 'NelioABPostAltExpEditionPageController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	require_once( NELIOAB_MODELS_DIR . '/alternatives/post-alternative-experiment.php' );
	require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/post-alt-exp-edition-page.php' );

	require_once( NELIOAB_ADMIN_DIR . '/alternatives/alt-exp-super-controller.php' );
	class NelioABPostAltExpEditionPageController extends NelioABAltExpSuperController {

		public static function get_instance() {
			return new NelioABPostAltExpEditionPageController();
		}

		public static function build() {
			// Check settings
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			$error = NelioABErrorController::build_error_page_on_invalid_settings();
			if ( $error ) return;

			$aux  = NelioABPostAltExpEditionPageController::get_instance();
			$view = $aux->do_build();
			$view->render();
		}

		public static function generate_html_content() {
			$aux  = NelioABPostAltExpEditionPageController::get_instance();
			$view = $aux->do_build();
			$view->render_content();
			die();
		}

		protected function do_build() {

			// Check settings
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			$error = NelioABErrorController::build_error_page_on_invalid_settings();
			if ( $error ) return;

			// Preparing labels for PAGE vs POST alternatives
			// ----------------------------------------------

			$alt_type = NelioABExperiment::PAGE_ALT_EXP;
			$title = __( 'Edit Page Experiment', 'nelioab' );
			if ( isset( $_GET['experiment-type'] ) &&
			     $_GET['experiment-type'] == NelioABExperiment::POST_ALT_EXP ) {
				$alt_type = NelioABExperiment::POST_ALT_EXP;
				$title = __( 'Edit Post Experiment', 'nelioab' );
			}


			// We recover the experiment (if any)
			// ----------------------------------------------

			global $nelioab_admin_controller;
			$experiment  = NULL;
			$other_names = array();
			if ( !empty( $nelioab_admin_controller->data ) ) {
				$experiment  = $nelioab_admin_controller->data;
				$alt_type    = $experiment->get_type();
			}
			else {
				$experiment = new NelioABPostAlternativeExperiment( -time() );
				$experiment->clear();
			}

			// ...and we also recover other experiment names (if any)
			if ( isset( $_POST['other_names'] ) ) {
				$other_names = json_decode( urldecode( $_POST['other_names'] ) );
			}
			else {
				$mgr = new NelioABExperimentsManager();
				foreach( $mgr->get_experiments() as $aux ) {
					if ( $aux->get_id() != $experiment->get_id() )
						array_push( $other_names, $aux->get_name() );
				}
			}

			// Get id of Original page or post
			// ----------------------------------------------
			if ( isset( $_GET['post-id'] ) &&
				$_GET['experiment-type'] == NelioABExperiment::POST_ALT_EXP )
				$experiment->set_original( $_GET['post-id'] );

			if ( isset( $_GET['page-id'] ) &&
				$_GET['experiment-type'] == NelioABExperiment::PAGE_ALT_EXP )
				$experiment->set_original( $_GET['page-id'] );


			// Checking whether there are pages or posts available
			// ---------------------------------------------------

			// ...pages...
			$list_of_pages = get_pages();
			if ( $alt_type == NelioABExperiment::PAGE_ALT_EXP && count( $list_of_pages ) == 0 ) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/error-page.php' );
				$view = new NelioABErrorPage(
					__( 'There are no pages available.', 'nelioab' ) );
				return $view;
			}

			// ...posts...
			$options_for_posts = array(
				'posts_per_page' => 1 );
			$list_of_posts = get_posts( $options_for_posts );
			require_once( NELIOAB_UTILS_DIR . '/data-manager.php' );
			NelioABArrays::sort_posts( $list_of_posts );

			if ( $alt_type == NelioABExperiment::POST_ALT_EXP && count( $list_of_posts ) == 0 ) {
				require_once( NELIOAB_ADMIN_DIR . '/views/errors/error-page.php' );
				$view = new NelioABErrorPage(
					__( 'There are no posts available.', 'nelioab' ) );
				return $view;
			}


			// If everything is OK, we keep going!
			// ---------------------------------------------------

			// Creating the view
			$view = $this->create_view( $alt_type );
			foreach ( $other_names as $name )
				$view->add_another_experiment_name( $name );

			// Experiment information
			$view->set_basic_info(
				$experiment->get_id(),
				$experiment->get_name(),
				$experiment->get_description(),
				$experiment->get_finalization_mode(),
				$experiment->get_finalization_value()
			);

			// Experiment specific variables and alternatives
			$view->set_original_id( $experiment->get_originals_id() );
			$view->set_alternatives( $experiment->get_json4js_alternatives() );

			// Goals
			$goals = $experiment->get_goals();
			foreach ( $goals as $goal )
				$view->add_goal( $goal->json4js() );

			return $view;
		}

		public function create_view( $alt_type ) {
			if ( $alt_type == NelioABExperiment::PAGE_ALT_EXP )
				$title = __( 'Edit Page Experiment', 'nelioab' );
			else
				$title = __( 'Edit Post Experiment', 'nelioab' );
			return new NelioABPostAltExpEditionPage( $title, $alt_type );
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
			$experiment = $nelioab_admin_controller->data;

			// Before saving the experiment, we have to get the alternative we
			// want to edit (after saving it, its IDs and/or its values may have
			// change).
			$post_id = '' . $_POST['content_to_edit'];
			if ( strpos( $post_id, ':' ) ) {
				$aux = explode( ':', $post_id );
				$post_id = $aux[1];
			}
			$alt_to_edit = false;
			foreach ( $experiment->get_alternatives() as $alt ) {
				if ( $alt->get_value() == $post_id )
					$alt_to_edit = $alt;
			}

			try {
				$experiment->save();
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}

			// 2. Redirect to the edit page
			echo '[SUCCESS]' . admin_url( 'post.php?action=edit&post=' . $alt_to_edit->get_value() . '&nelioab_original_id=' . $experiment->get_originals_id() );
			die();
		}

		public function build_experiment_from_post_data() {
			$exp = new NelioABPostAlternativeExperiment( $_POST['exp_id'] );
			$exp->set_original( $_POST['exp_original'] );
			$exp = $this->compose_basic_alt_exp_using_post_data( $exp );
			foreach ( $exp->get_appspot_alternatives() as $alt ) {
				$id = '' . $alt->get_id();
				$aux = explode( ':', $id );
				$alt->set_id( $aux[0] );
				$alt->set_value( $aux[1] );
			}
			foreach ( $exp->get_local_alternatives() as $alt ) {
				$alt->set_value( $alt->get_id() );
			}
			global $nelioab_admin_controller;
			$nelioab_admin_controller->data = $exp;
		}

		public function manage_actions() {
			if ( !isset( $_POST['action'] ) )
				return;

			parent::manage_actions();

			if ( $_POST['action'] == 'edit_alt_content' )
				if ( $this->validate() )
					$this->edit_alternative_content();

		}

	}//NelioABPostAltExpEditionPageController

}

if ( isset( $_POST['nelioab_edit_ab_post_exp_form'] ) ) {
	$controller = NelioABPostAltExpEditionPageController::get_instance();
	$controller->manage_actions();
	if ( !$controller->validate() )
		$controller->print_ajax_errors();
}

