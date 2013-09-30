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


if ( !class_exists( 'NelioABFormatter' ) ) {

	abstract class NelioABFormatter {

		public static function format_date( $timestamp, $tz=false ) {
			if ( !is_int( $timestamp ) )
				return $timestamp;

			if ( $tz === false )
				$tz = date_default_timezone_get();

			$format = get_option( 'date_format' ) . ' - ' . get_option( 'time_format' );
			try {
				$aux = new DateTime();
				$aux->setTimestamp( $timestamp );
				$aux->setTimezone( new DateTimeZone( $tz ) );
				return $aux->format( $format );
			} catch ( Exception $e ) {
				$date = date_i18n( $format, $timestamp );
			}

		}

	}//NelioABFormatter

}

?>
