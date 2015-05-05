<?php
/**
 * Copyright 2013 Nelio Software S.L.
 * This script is distributed under the terms of the GNU General Public
 * License.
 *
 * This script is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License.
 *
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <http://www.gnu.org/licenses/>.
 */


if ( !class_exists( 'NelioABThemeCompatibilityLayer' ) ) {

	/**
	 * This class helps us make sure that A/B-tested themes can be tested properly.
	 */
	abstract class NelioABThemeCompatibilityLayer {

		/**
		 * This function checks whether there are any Theme experiments running or
		 * not. If there are, it will add some hooks to WordPress, just to make sure
		 * that the framework used to create the active (original or alternative)
		 * theme is recovering the appropriate information.
		 */
		public static function make_compat() {
			// Let's check if there's a theme experiment running
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
			$found = false;
			foreach ( $running_exps as $exp ) {
				/** @var NelioABExperiment $exp */
				if ( $exp->get_type() == NelioABExperiment::THEME_ALT_EXP )
					$found = true;
			}
			if ( !$found )
				return;

			// If there's some exp running, let's make sure we're compatible with the framework
			// used to create it.
			add_filter( 'option_optionsframework',
				array( 'NelioABThemeCompatibilityLayer', 'fix_optionsframework_compatibility' ) );
		}


		/**
		 * This function hooks on the get_option( 'optionsframework' ) call. It
		 * returns the appropriate theme ID, which has to depend on the current
		 * theme.
		 *
		 * See
		 * <a href="http://support.nelioabtesting.com/helpdesk/tickets/564">Ticket
		 * #564</a>.
		 *
		 * @param array $val
		 *        This value contains some information about the currently
		 *        active options framework. We're interested in the ID element,
		 *        which has to be replaced with the current theme (which might
		 *        be A/B Tested).
		 *
		 * @return array $val
		 *        The same original values, but with a (possibly) new ID that
		 *        corresponds to the name of the currently active theme.
		 */
		public static function fix_optionsframework_compatibility( $val ) {
			$theme = wp_get_theme();
			$name = $theme['Name'];
			$name =  preg_replace( '/\W/', '', strtolower( $name ) );
			$val['id'] = $name;
			return $val;
		}

	}//NelioABThemeCompatibilityLayer

}

