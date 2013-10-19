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



/*
 * Plugin Name: Nelio A/B Testing
 * Description: Optimize your site based on data, not opinions. With this plugin, you will be able to perform A/B testing (and more) on your wordpress site.
 * Version: 1.1.2
 * Author: Nelio Software
 * Plugin URI: http://wp-abtesting.com
 * Text Domain: nelioab
 */

// PLUGIN VERSION
define( 'NELIOAB_PLUGIN_VERSION', '1.1.2' );

// Plugin dir name...
define( 'NELIOAB_PLUGIN_NAME', 'Nelio A/B Testing' );
//define( 'NELIOAB_PLUGIN_DIR_NAME', 'nelio-ab-testing' );
define( 'NELIOAB_PLUGIN_DIR_NAME', basename( dirname( __FILE__ ) ) );

// Defining a few important directories
define( 'NELIOAB_ROOT_DIR', plugin_dir_path( __FILE__ ) );
define( 'NELIOAB_DIR', NELIOAB_ROOT_DIR . '/includes' );

define( 'NELIOAB_ADMIN_DIR', NELIOAB_DIR . '/admin' );
define( 'NELIOAB_UTILS_DIR', NELIOAB_DIR . '/utils' );
define( 'NELIOAB_MODELS_DIR', NELIOAB_DIR . '/models' );

// Some URLs...
define( 'NELIOAB_URL', plugin_dir_url( __FILE__ ) );
define( 'NELIOAB_BACKEND_URL', 'https://nelioabtesting.appspot.com/_ah/api/nelioab/v2');
define( 'NELIOAB_FEEDBACK_URL', 'https://neliofeedback.appspot.com/_ah/api/feedback/v1');
define( 'NELIOAB_ASSETS_URL', plugins_url() . '/' . NELIOAB_PLUGIN_DIR_NAME . '/assets' );
define( 'NELIOAB_ADMIN_ASSETS_URL', NELIOAB_ASSETS_URL . '/admin' );

function nelioab_i18n() {
	load_plugin_textdomain( 'nelioab', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );
}
add_action( 'plugins_loaded', 'nelioab_i18n' );

require_once( NELIOAB_UTILS_DIR . '/cookies.php' );
require_once( NELIOAB_DIR . '/controller.php' );
require_once( NELIOAB_ADMIN_DIR . '/admin-controller.php' );


// Clean all stuff when activating the plugin
require_once( NELIOAB_UTILS_DIR . '/cleaner.php' );
register_activation_hook( __FILE__, 'nelioab_clean' );

?>
