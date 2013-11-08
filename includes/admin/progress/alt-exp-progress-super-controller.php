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


if ( !class_exists( 'NelioABAltExpProgressSuperController' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );

	abstract class NelioABAltExpProgressSuperController {

		public abstract static function build();
		public abstract static function generate_html_content();

		public abstract function apply_alternative();

		public function manage_actions() {

			if ( isset( $_GET['forcestop'] ) &&
			     isset( $_GET['id'] ) &&
			     isset( $_GET['exp_type'] )
			) {
				require_once( NELIOAB_ADMIN_DIR . '/experiments-page-controller.php' );
				NelioABExperimentsPageController::stop_experiment( $_GET['id'], $_GET['exp_type'] );
				echo sprintf(
					'[SUCCESS]%sadmin.php?page=nelioab-experiments&action=progress&id=%s&exp_type=%s',
					admin_url(), $_GET['id'], $_GET['exp_type'] );
				die();
			}
			
			if ( isset( $_POST['apply_alternative'] ) ) {
				$this->apply_alternative();
				return;
			}

		}

	}//NelioABAltExpProgressSuperController

}

?>
