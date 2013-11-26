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


function nelioab_clean() {
	global $wpdb;

	// Rename the meta key that identifies post/page alternatives...
	$wpdb->update(
		$wpdb->postmeta,
		array( 'meta_key' => '_is_nelioab_alternative' ),
		array( 'meta_key' => 'is_nelioab_alternative' )
	);

//	// Remove the options related to "user_id". Now we are using "reg_num"
//	$res = $wpdb->query(
//		$wpdb->prepare(
//			'DELETE FROM ' . $wpdb->options .
//			' WHERE option_name LIKE \'%\'',
//			'%nelioab%client_id%'
//		)
//	);

}

?>
