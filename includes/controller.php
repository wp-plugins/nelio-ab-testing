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



/**
 * Nelio AB Testing main controller
 *
 * @package Nelio AB Testing
 * @subpackage Experiment
 * @since 0.1
 */
class NelioABController {

	private $alt_exp_controller;

	public function __construct() {
		$this->alt_exp_controller = NULL;

		// Trick for proper THEME ALT EXP testing
		if ( isset( $_POST['nelioab_load_alt'] ) ) {
			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			// Theme alt exp related
			if ( NelioABWpHelper::is_at_least_version( 3.4 ) ) {
				$aux = $this->get_alt_exp_controller();
				add_filter( 'stylesheet',       array( &$aux, 'modify_stylesheet' ) );
				add_filter( 'template',         array( &$aux, 'modify_template' ) );
				add_filter( 'sidebars_widgets', array( &$aux, 'fix_widgets_for_theme' ) );
			}
		}

		add_action( 'init', array( &$this, 'init' ) );
	}

	private function get_alt_exp_controller() {
		$dir = NELIOAB_DIR . '/experiment-controllers';
		require_once( $dir . '/alternative-experiment-controller.php' );
		if ( $this->alt_exp_controller == NULL )
			$this->alt_exp_controller = new NelioABAlternativeExperimentController();
		return $this->alt_exp_controller;
	}

	public function init() {
		// We do not perform AB Testing if the user accessing the page is...
		// ...a ROBOT...
		if ( $this->is_robot() )
			return;

		// ... or an ADMIN
		if ( current_user_can( 'level_8' ) )
			return;

		// Check if we are syncing cookies...
		if ( isset( $_POST['nelioab_sync'] ) ) {
			// We control that cookies correspond to the last version of the plugin
			$this->version_control();

			// We assign the current user an ID (if she does not have any)
			require_once( NELIOAB_MODELS_DIR . '/user.php' );
			$user_id = NelioABUser::get_id();
		}

		// We load all controllers
		$this->load_experiment_controllers();
	}

	/**
	 * When a user connects to our site, she gets a set of cookies. These
	 * cookies depend on the version of the plugin. If the last time she
	 * connected the site had an older version, we update the information
	 * so that she can get rid of any old cookies (via JS).
	 */
	private function version_control() {
		require_once( NELIOAB_MODELS_DIR . '/settings.php' );
		global $NELIOAB_COOKIES;
		$cookie_name  = NelioABSettings::cookie_prefix() . 'version';
		$last_version = 0;
		if ( isset( $NELIOAB_COOKIES[$cookie_name] ) )
			$last_version = $NELIOAB_COOKIES[$cookie_name];

		if ( $last_version == NELIOAB_PLUGIN_VERSION )
			return;

		$aux = array();
		$userid_key = NelioABSettings::cookie_prefix() . 'userid';
		if ( isset( $NELIOAB_COOKIES[$userid_key] ) )
			$aux[$userid_key] = $NELIOAB_COOKIES[$userid_key];

		$NELIOAB_COOKIES = $aux;
		nelioab_setcookie( $cookie_name, NELIOAB_PLUGIN_VERSION, time() + (86400*28) );
		nelioab_setcookie( '__nelioab_new_version', 'true' );
	}

	private function load_experiment_controllers() {
		$dir = NELIOAB_DIR . '/experiment-controllers';

		// Controller for changing a page using its alternatives:
		$conexp_controller = $this->get_alt_exp_controller();
		$conexp_controller->hook_to_wordpress();

		// Done.
	}

	/**
	 * Quickly detects whether the current user is a bot, based on
	 * User Agent. Keep in mind the function is not very precise.
	 * Do not use for page blocking.
	 *
	 * @return bool true if the user is a bot, false otherwise.
	 */
	private function is_robot() {
		$list = 'bot|crawl|spider|https?:' .
			'|Google|Rambler|Lycos|Y!|Yahoo|accoona|Scooter|AltaVista|yandex' .
			'|ASPSeek|Ask Jeeves|eStyle|Scrubby';

		return preg_match("/$list/i", @$_SERVER['HTTP_USER_AGENT']);
	}

}//NelioABController

if ( !is_admin() )
	$nelioab_controller = new NelioABController();

?>
