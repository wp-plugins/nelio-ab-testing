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


/**
 * This function prints an error on screen.
 *
 * @id  int
 *      the ID parameter.
 * @str string (optional)
 *      an optional string that offers further information about the error.
 */
function nelioabe( $id, $str = '' ) {
	echo '<pre style="text-align:left;font-size:12px;"><b>ERROR #' . $id . '</b> ' . $str . '</pre>' . "\n";
	if ( strlen( $str ) > 0 )
		$str = ': ' . $str;
	error_log( 'ERROR #' . $id . $str );
}

/**
 * This function prints a warning on screen.
 *
 * @id  int
 *      the ID parameter.
 * @str string (optional)
 *      an optional string that offers further information about the warning.
 */
function nelioabw( $id, $str = '' ) {
	echo '<pre style="text-align:left;font-size:12px;"><b>WARNING #' . $id . '</b> ' . $str . '</pre>' . "\n";
	if ( strlen( $str ) > 0 )
		$str = ': ' . $str;
	error_log( 'WARNING #' . $id . $str );
}

/**
 * This function prints some info on screen.
 *
 * @id  int
 *      the ID parameter.
 * @str string (optional)
 *      an optional string that offers further information about the info to debug.
 */
function nelioabi( $id, $str = '' ) {
	echo '<pre style="text-align:left;font-size:12px;"><b>#' . $id . '</b> ' . $str . '</pre>' . "\n";
	if ( strlen( $str ) > 0 )
		$str = ': ' . $str;
	error_log( 'INFO #' . $id . $str );
}

/**
 * This function prints some debug info on screen. It is an alias to `nelioabi`.
 *
 * @id  int
 *      the ID parameter.
 * @str string (optional)
 *      an optional string that offers further information about the info to debug.
 */
function nelioabd( $id, $str = '' ) {
	echo '<pre style="text-align:left;font-size:12px;"><b>#' . $id . '</b> ' . $str . '</pre>' . "\n";
	if ( strlen( $str ) > 0 )
		$str = ': ' . $str;
	error_log( 'DEBUG #' . $id . $str );
}

