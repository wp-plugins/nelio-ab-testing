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


if ( !class_exists( NelioABAlternativesExperimentProgressPageController ) ) {

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives-experiment-progress-page.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );

	class NelioABAlternativesExperimentProgressPageController {

		public static function build() {
			$title = __( 'Results of the Experiment', 'nelioab' );
			$view = new NelioABAlternativesExperimentProgressPage( $title );

			if ( isset( $_GET['id'] ) )
				// The ID of the experiment to which the action applies
				$view->keep_request_param( 'exp_id', $_GET['id'] );

			$view->get_content_with_ajax_and_render( __FILE__, __CLASS__ );
		}

		public static function generate_html_content() {

			$exp_id = $_REQUEST['exp_id'];
			$title  = __( 'Results of the Experiment', 'nelioab' );
			$view   = new NelioABAlternativesExperimentProgressPage( $title );

			$mgr = new NelioABExperimentsManager();
			$exp = null;

			try {
				$exp = $mgr->get_experiment_by_id( $exp_id );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
				NelioABErrorController::build( $e );
			}

			$view->set_experiment( $exp );

			try {
				// The function "get_results" may throw an exception. This is why
				// we use it in here.
				$view->set_results( $exp->get_results() );
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_UTILS_DIR . '/backend.php' );
				if ( $e->getCode() == NelioABErrCodes::RESULTS_NOT_AVAILABLE_YET ) {
					$view->set_results( null );
				}
				else {
					require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
					NelioABErrorController::build( $e );
				}
			}

			$view->render_content();

			die();
		}

		public function apply_alternative() {
			if ( isset( $_POST['original'] ) && isset( $_POST['alternative'] ) ) {
				$ori_id = $_POST['original'];
				$alt_id = $_POST['alternative'];

				require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
				NelioABWPHelper::override( $ori_id, $alt_id );
				echo 'OK';
				die();
			}
		}

	}//NelioABAlternativesExperimentProgressPageController

}

if ( isset( $_GET['forcestop'] ) ) {
	require_once( NELIOAB_ADMIN_DIR . '/experiments-page-controller.php' );
	NelioABExperimentsPageController::stop_experiment( $_GET['id'] );
	echo sprintf(
		'[SUCCESS]%sadmin.php?page=nelioab-experiments&action=progress&id=%s',
		admin_url(), $_GET['id'] );
	die();
}

if ( isset( $_GET['apply-alternative'] ) ) {
	NelioABAlternativesExperimentProgressPageController::apply_alternative();
}

?>
