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
			if ( !is_int( $aux ) ) {
				$aux = strtotime( $timestamp );
			}

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
			if ( $tz === false ) {
				$tz = self::get_timezone_string();
			}

			$tz_text = '';
			if ( $tz === 'UTC' ) {
				$tz_text = ' (UTC)';
			}

			$format = get_option( 'date_format' ) . ' - ' . get_option( 'time_format' );
			try {
				$aux = new DateTime();
				if ( is_callable( array( $aux, 'setTimestamp' ) ) &&
						is_callable( array( $aux, 'setTimezone' ) ) ) {
					$aux->setTimestamp( $timestamp );
					$aux->setTimezone( new DateTimeZone( $tz ) );
					return $aux->format( $format ) . $tz_text;
				} else {
					return date_i18n( $format, $timestamp ) . $tz_text;
				}
			} catch ( Exception $e ) {
				return date_i18n( $format, $timestamp ) . $tz_text;
			}

		}

		/**
		 * Returns a string with the timelapse between two dates.
		 *
		 * @param int            $timestamp_start a unix timestamp.
		 * @param int            $timestamp_end a unix timestamp.
		 *
		 * @return string the timelapse between the two timestamps.
		 *
		 * @since 1.0.10
		 */
		public static function get_timelapse( $timestamp_start, $timestamp_end ) {
			$d1 = new DateTime( $timestamp_start );
			$d2 = new DateTime( $timestamp_end );
			$since_start = $d1->diff( $d2 );

			$hours = $since_start->h;
			$days = $since_start->d;
			$months = $since_start->m;

			if ( $months > 0 ) {
				$result = sprintf(
					_n( '1 month', '%d months', $months, 'nelioab' ),
					$months );
				if ( $days > 0 ) {
					$result .= ' ' . sprintf(
						_n( 'and 1 day', 'and %d days', $days, 'nelioab' ),
						$days );
				}
			} else if ( $days > 0 ) {
				$result = sprintf(
					_n( '1 day', '%d days', $days, 'nelioab' ),
					$days );
				if ( $hours > 0 ) {
					$result .= ' ' . sprintf(
						_n( 'and 1 hour', 'and %d hours', $hours, 'nelioab' ),
						$hours );
				}

			} else if ( $hours > 0 ) {
				$result = sprintf(
					_n( '1 hour', '%d hours', $hours, 'nelioab' ), $hours );
			} else {
				$result = __( 'Less than 1 hour', 'nelioab' );
			}

			return $result;
		}



		/**
		 * Returns the timezone string for a site, even if it's set to a UTC offset
		 *
		 * Adapted from http://www.php.net/manual/en/function.timezone-name-from-abbr.php#89155
		 *
		 * @return string valid PHP timezone string
		 */
		public static function get_timezone_string() {
			// if site timezone string exists, return it
			if ( $timezone = get_option( 'timezone_string' ) )
				return $timezone;

			// get UTC offset, if it isn't set then return UTC
			if ( 0 === ( $utc_offset = get_option( 'gmt_offset', 0 ) ) )
				return 'UTC';

			// adjust UTC offset from hours to seconds
			$utc_offset *= 3600;

			// attempt to guess the timezone string from the UTC offset
			if ( $timezone = timezone_name_from_abbr( '', $utc_offset, 0 ) ) {
				return $timezone;
			}

			// last try, guess timezone string manually
			$is_dst = date( 'I' );

			foreach ( timezone_abbreviations_list() as $abbr ) {
				foreach ( $abbr as $city ) {
					if ( $city['dst'] == $is_dst && $city['offset'] == $utc_offset ) {
						return $city['timezone_id'];
					}
				}
			}

			// fallback to UTC
			return 'UTC';
		}


	}//NelioABFormatter

}

