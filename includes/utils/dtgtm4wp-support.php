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


add_filter( 'gtm4wp_get_the_gtm_tag', 'nelioab_tweak_gtm4wp' );
function nelioab_tweak_gtm4wp( $script ) {

	$open = '<script>function nelioabActivateGoogleTagMgr() {' . "\n";
	$open .= 'console.log( "Loading Google Tag Manager..." );' . "\n";
	$script = str_replace( '<script>', $open, $script );

	$close = '; ' . "\n" . 'console.log( "Done!" );}</script>';
	$script = str_replace( '</script>', $close, $script );

	return $script;

}

