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


if ( !class_exists( 'NelioABPostAltExpCreationPageController' ) ) {

	require_once( NELIOAB_ADMIN_DIR . '/alternatives/post-alt-exp-edition-page-controller.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/post-alt-exp-creation-page.php' );

	class NelioABPostAltExpCreationPageController extends
			NelioABPostAltExpEditionPageController {

		public static function get_instance() {
			return new NelioABPostAltExpCreationPageController();
		}

		public static function build() {
			// Check settings
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			$error = NelioABErrorController::build_error_page_on_invalid_settings();
			if ( $error ) return;

			$aux  = NelioABPostAltExpCreationPageController::get_instance();
			$view = $aux->do_build();
			$page_on_front = get_option( 'page_on_front' );
			if ( isset( $_GET['lp'] ) && $page_on_front )
				$view->set_original_id( $page_on_front );
			$view->render();
		}

		public static function generate_html_content() {
			$aux  = NelioABPostAltExpCreationPageController::get_instance();
			$view = $aux->do_build();
			$view->render_content();
			die();
		}

		public function create_view( $alt_type ) {
			if ( $alt_type == NelioABExperiment::PAGE_ALT_EXP )
				$title = __( 'Add Page Experiment', 'nelioab' );
			else
				$title = __( 'Add Post Experiment', 'nelioab' );
			return new NelioABPostAltExpCreationPage( $title, $alt_type );
		}

	}//NelioABPostAltExpCreationPageController

}

if ( isset( $_POST['nelioab_new_ab_post_exp_form'] ) ) {
	$controller = NelioABPostAltExpCreationPageController::get_instance();
	$controller->manage_actions();
	if ( !$controller->validate() )
		$controller->print_ajax_errors();
}

