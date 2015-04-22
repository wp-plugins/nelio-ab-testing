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


if ( !class_exists( 'NelioABFeedbackPageController' ) ) {

	require_once( NELIOAB_ADMIN_DIR . '/views/feedback-page.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );

	class NelioABFeedbackPageController {

		public static function build() {
			// Check settings
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			$error = NelioABErrorController::build_error_page_on_invalid_settings();
			if ( $error ) return;

			$title = __( 'Nelio A/B Testing', 'nelioab' );

			$view = new NelioABFeedbackPage( $title );
			$view->get_content_with_ajax_and_render( __FILE__, __CLASS__ );
		}

		public static function generate_html_content() {
			// Render content
			$title = __( 'Nelio A/B Testing', 'nelioab' );
			$view = new NelioABFeedbackPage( $title );
			$view->render_content();

			// Update cache
			NelioABExperimentsManager::update_running_experiments_cache();

			die();
		}

		public static function validate_feedback() {
			return true;
		}

		public static function submit_feedback() {
			require_once( NELIOAB_UTILS_DIR . '/backend.php' );

			$the_content = '';
			if ( isset( $_POST['content'] ) )
				$the_content = $_POST['content'];

			$url       = NELIOAB_FEEDBACK_URL . '/feedback';
			$data      = array(
					'mail'    => NelioABAccountSettings::get_email(),
					'content' => $the_content,
					'app'     => 'nelioab',
					'status'  => 1,
				);

			for ( $attemp = 0; $attemp < 3; ++$attemp ) {
				try {
					$params = array(
         		   'headers' => array( 'Content-Type' => 'application/json' ),
         		   'body'    => json_encode( $data ),
         		);
					$json_data = NelioABBackend::remote_post_raw( $url, $params );
				}
				catch ( Exception $e ) { }

				echo "OK";
				die();
			}

			echo "FAIL";
			die();
		}

	}//NelioABFeedbackPageController

}

if ( isset( $_POST['nelioab_feedback_form'] ) ) {
	if ( NelioABFeedbackPageController::validate_feedback() )
		NelioABFeedbackPageController::submit_feedback();
}

?>
