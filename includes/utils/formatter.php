<?php
/**
 * Copyright 2015 Nelio Software S.L.
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

if ( !class_exists( 'NelioABFormatter' ) ) {

	/**
	 * A collection of useful functions for formatting certain elements.
	 *
	 * @since 1.0.10
	 * @package \NelioABTesting\Utils
	 */
	abstract class NelioABFormatter {

		/**
		 * Returns the given date in the appropriate timezone.
		 *
		 * @param string|int     $timestamp a unix timestamp or a string date.
		 * @param boolean|string $tz        the timezone to use.
		 *                       If none is specified, WordPress' default timezone
		 *                       will be used.
		 *
		 * @return string the given date in the appropriate timezone.
		 *
		 * @see self::format_unix_timestamp
		 *
		 * @since 1.0.10
		 */
		public static function format_date( $timestamp, $tz = false ) {
			$aux = $timestamp;
			if ( !is_int( $aux ) )
				$aux = strtotime( $timestamp );

			return NelioABFormatter::format_unix_timestamp( $aux, $tz );
		}


		/**
		 * Returns the given date in the appropriate timezone.
		 *
		 * @param int            $timestamp a unix timestamp.
		 * @param boolean|string $tz        the timezone to use.
		 *                       If none is specified, WordPress' default timezone
		 *                       will be used.
		 *
		 * @return string the given date in the appropriate timezone.
		 *
		 * @see self::format_unix_timestamp
		 *
		 * @since 1.0.10
		 */
		public static function format_unix_timestamp( $timestamp, $tz = false ) {
			if ( $tz === false )
				$tz = get_option( 'timezone_string' );

			$tz_text = '';
			if ( $tz === 'UTC' )
				$tz_text = ' (UTC)';

			$format = get_option( 'date_format' ) . ' - ' . get_option( 'time_format' );
			try {
				$aux = new DateTime();
				if ( is_callable( array( $aux, 'setTimestamp' ) ) &&
				     is_callable( array( $aux, 'setTimezone' ) ) ) {
					$aux->setTimestamp( $timestamp );
					$aux->setTimezone( new DateTimeZone( $tz ) );
					return $aux->format( $format ) . $tz_text;
				}
				else {
					return date_i18n( $format, $timestamp ) . $tz_text;
				}
			} catch ( Exception $e ) {
				return date_i18n( $format, $timestamp ) . $tz_text;
			}

		}


	}//NelioABFormatter

}

