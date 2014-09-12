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


if( !class_exists( 'NelioABGTest' ) ) {

	class NelioABGTest {

		const UNKNOWN                = 1;
		const NO_CLEAR_WINNER        = 2;
		const NOT_ENOUGH_VISITS      = 3;
		const DROP_VERSION           = 4;
		const WINNER                 = 5;
		const WINNER_WITH_CONFIDENCE = 6;

		private $type;
		private $original;
		private $min;
		private $min_name;
		private $min_short_name;
		private $max;
		private $max_name;
		private $max_short_name;
		private $gtest;
		private $pvalue;
		private $certainty;

		public function __construct( $type, $original ) {
			$this->type     = NelioABGTest::get_result_status_from_str( $type );
			$this->original = $original;
		}

		public function is_original_the_best() {
			return $this->original == $this->max;
		}

		public function get_type() {
			return $this->type;
		}

		public function set_min( $min ) {
			$this->min = $min;
		}

		public function get_min() {
			return $this->min;
		}

		public function set_min_name( $min_short_name, $min_name = false ) {
			$this->min_short_name = $min_short_name;
			$this->min_name = $min_name;
		}

		public function set_max( $max ) {
			$this->max = $max;
		}

		public function get_max() {
			return $this->max;
		}

		public function set_max_name( $max_short_name, $max_name = false ) {
			$this->max_short_name = $max_short_name;
			$this->max_name = $max_name;
		}

		public function set_gtest( $gtest ) {
			$this->gtest = $gtest;
		}

		public function set_pvalue( $pvalue ) {
			$this->pvalue = $pvalue;
		}

		public function set_certainty( $certainty ) {
			$this->certainty = $certainty;
		}

		public function get_certainty() {
			return $this->certainty;
		}

		private function prepare_name( $name, $popup ) {
			if ( $popup ) {
				$popup = str_replace( '"', '\'\'', $popup );
				$aux = "<span title=\"$popup\">$name</span>";
			}
			else {
				$aux = $name;
			}
			return $aux;
		}

		public function to_string() {
			switch( $this->type ) {
				case NelioABGTest::NO_CLEAR_WINNER:
					return __( 'No alternative is better than the rest.', 'nelioab' );

				case NelioABGTest::NOT_ENOUGH_VISITS:
					return __( 'No statistic results available due to too few visits.', 'nelioab' );

				case NelioABGTest::DROP_VERSION:
					return sprintf(
						__( '«%1$s» beats «%2$s» with a %3$s%% confidence.', 'nelioab' ),
							$this->prepare_name( $this->max_short_name, $this->max_name ),
							$this->prepare_name( $this->min_short_name, $this->min_name ),
							$this->certainty
						);

				case NelioABGTest::WINNER:
					$string = __( '«%1$s» beats «%2$s» with a %3$s%% confidence. Therefore, we can conclude that «%1$s» is the best alternative, but with a low confidence value <small>(<a href="http://wp-abtesting.com/faqs/what-is-the-meaning-of-the-confidence-value-you-provide-together-with-the-results/">why is this important?</a>)</small>.', 'nelioab' );
					$aux = $this->certainty;
					if ( is_string( $aux ) )
						$aux = floatval( $aux );
					if ( $aux >= 90 ) {
						$string = __( '«%1$s» beats «%2$s» with a %3$s%% confidence. Therefore, we can conclude that «%1$s» is the best alternative.', 'nelioab' );
					}
					return sprintf( $string,
						$this->prepare_name( $this->max_short_name, $this->max_name ),
						$this->prepare_name( $this->min_short_name, $this->min_name ),
						$this->certainty
					);

				default:
					return __( 'There was an error while processing the statistics.', 'nelioab' );
			}
		}

		public static function get_result_status_from_str( $status ) {
			switch ( $status ) {
				case 'NO_CLEAR_WINNER':
					return NelioABGTest::NO_CLEAR_WINNER;
				case 'NOT_ENOUGH_VISITS':
					return NelioABGTest::NOT_ENOUGH_VISITS;
				case 'DROP_VERSION':
					return NelioABGTest::DROP_VERSION;
				case 'WINNER':
					return NelioABGTest::WINNER;
				case 'WINNER_WITH_CONFIDENCE':
					return NelioABGTest::WINNER_WITH_CONFIDENCE;
				default:
					return NelioABGTest::UNKNOWN;
			}
		}

		public static function generate_status_light( $status ) {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );
			$cb = '';
			if ( NelioABSettings::use_colorblind_palette() )
				$cb = ' status-colorblind';

			$light = '<div class="status-icon status-%s" title="%s"></div>';
			switch ( $status ) {
				case NelioABGTest::WINNER_WITH_CONFIDENCE:
					$light = sprintf( $light, 'tick' . $cb,
						__( 'There is a clear winner, with a confidence greater than 90%', 'nelioab' ) );
					break;
				case NelioABGTest::WINNER:
					$light = sprintf( $light, 'star' . $cb,
						__( 'There is a possible winner, but keep in mind the confidence does not reach 90%', 'nelioab' ) );
					break;
				case NelioABGTest::NO_CLEAR_WINNER:
					$light = sprintf( $light, 'clock' . $cb,
						__( 'There is not enough data to determine any winner yet', 'nelioab' ) );
					break;
				case NelioABGTest::NOT_ENOUGH_VISITS:
					$light = sprintf( $light, 'clock' . $cb,
						__( 'There are not enough visits', 'nelioab' ) );
					break;
				case NelioABGTest::UNKNOWN:
				default:
					$light = sprintf( $light, 'gray' . $cb,
						__( 'There are not enough visits', 'nelioab' ) );
			}

			return $light;
		}

	}//NelioABGTest
}

