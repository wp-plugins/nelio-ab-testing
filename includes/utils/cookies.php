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


// Use custom cookie manager

$NELIOAB_COOKIES = array();

if ( isset( $_POST['nelioab_cookies'] ) )
	$NELIOAB_COOKIES = $_POST['nelioab_cookies'];

function nelioab_setrawcookie( $name, $value, $expire=0 ) {
	global $NELIOAB_COOKIES;
	if ( $expire ) {
		// FORMAT: "Day, dd Mon year hh:mm:ss GMT"
		$expire = gmdate( 'D, d-M-Y H:i:s T', $expire );
		$expire = ";expires=$expire";
	}
	else
		$expire = '';
	$NELIOAB_COOKIES[$name] = "$value$expire";
}

function nelioab_setcookie( $name, $value, $expire=0 ) {
	nelioab_setrawcookie( $name, rawurlencode( $value ), $expire );
}

?>
