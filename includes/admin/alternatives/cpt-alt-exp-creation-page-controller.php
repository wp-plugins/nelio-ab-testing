<?php
/**
 * Copyright 2015 Nelio Software S.L.
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


if ( !class_exists( 'NelioABCptAltExpCreationPageController' ) ) {

	require_once( NELIOAB_ADMIN_DIR . '/alternatives/cpt-alt-exp-edition-page-controller.php' );
	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/cpt-alt-exp-creation-page.php' );

	class NelioABCptAltExpCreationPageController extends
			NelioABCptAltExpEditionPageController {

		public static function get_instance() {
			return new NelioABCptAltExpCreationPageController();
		}

		public static function build() {

			// Check settings
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			$error = NelioABErrorController::build_error_page_on_invalid_settings();
			if ( $error ) return;

			$aux  = NelioABCptAltExpCreationPageController::get_instance();
			$view = $aux->do_build();
			$page_on_front = get_option( 'page_on_front' );
			$view->render();
		}

		public static function generate_html_content() {
			$aux  = NelioABCptAltExpCreationPageController::get_instance();
			$view = $aux->do_build();
			$view->render_content();
			die();
		}

		public function create_view( $alt_type ) {
			$title = __( 'Add Custom Post Type Experiment', 'nelioab' );
			return new NelioABCptAltExpCreationPage( $title, $alt_type );
		}

	}//NelioABCptAltExpCreationPageController

}

if ( isset( $_POST['nelioab_new_ab_cpt_exp_form'] ) ) {
	$controller = NelioABCptAltExpCreationPageController::get_instance();
	$controller->manage_actions();
	if ( !$controller->validate() )
		$controller->print_ajax_errors();
}

