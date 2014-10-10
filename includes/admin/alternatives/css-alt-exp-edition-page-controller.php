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
	require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );

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
			$other_names = array();
			if ( !empty( $nelioab_admin_controller->data ) ) {
				$experiment = $nelioab_admin_controller->data;
			}
			else {
				$experiment = new NelioABCssAlternativeExperiment( -1 );
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


			// If everything is OK, we keep going!
			// ---------------------------------------------------

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
			$alts = $experiment->get_json4js_alternatives();
			for ( $i = 0; $i < count( $alts ); ++$i )
				$alts[$i]['value'] = urlencode( $alts[$i]['value'] );
			$view->set_alternatives( $alts );

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

		public function create_view() {
			$title = __( 'Edit CSS Experiment', 'nelioab' );
			return new NelioABCssAltExpEditionPage( $title );
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
			echo '[SUCCESS]' . admin_url( 'admin.php?page=nelioab-css-edit&exp_id=' . $exp_id . '&css_id=' . $css_alt_id );
			die();
		}

		public function build_experiment_from_post_data() {
			$exp = new NelioABCssAlternativeExperiment( $_POST['exp_id'] );
			$exp = $this->compose_basic_alt_exp_using_post_data( $exp );
			if ( isset( $_POST['nelioab_alternatives'] ) ) {
				$alts = json_decode( urldecode( $_POST['nelioab_alternatives'] ) );
				for ( $i = 0; $i < count( $alts ); ++$i )
					if ( isset( $alts[$i]->value ) )
						$alts[$i]->value = urldecode( $alts[$i]->value );
				$exp->load_json4js_alternatives( $alts );
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

	}//NelioABCssAltExpEditionPageController

}

if ( isset( $_POST['nelioab_edit_ab_css_exp_form'] ) ) {
	$controller = NelioABCssAltExpEditionPageController::get_instance();
	$controller->manage_actions();
	if ( !$controller->validate() )
		$controller->print_ajax_errors();
}

