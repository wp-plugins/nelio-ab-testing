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


if( !class_exists( 'NelioABGlobalAlternativeExperiment' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/alternatives/alternative-experiment.php' );

	abstract class NelioABGlobalAlternativeExperiment extends NelioABAlternativeExperiment {

		private $ori;

		public function __construct( $id ) {
			parent::__construct( $id );
		}

		public function clear() {
			parent::clear();
			$this->ori = array( -1 );
		}

		public function get_origins() {
			return $this->ori;
		}

		public function set_origins( $ori ) {
			$this->ori = $ori;
		}

		public function add_origin( $ori ) {
			array_push( $this->ori, $ori );
		}

		public function get_results() {
			$results = new NelioABAltExpResult();

			$url = sprintf(
				NELIOAB_BACKEND_URL . '/globalexp/%s/result',
				$this->get_id()
			);

			$json_data = null;
			$json_data = NelioABBackend::remote_get( $url );
			$json_data = json_decode( $json_data['body'], true );

			$results->set_total_visitors( $json_data['totalVisitors'] );
			$results->set_total_conversions( $json_data['totalConversions'] );
			$results->set_total_conversion_rate( $json_data['totalConversionRate'] );
			$results->set_visitors_history( $json_data['historyVisitors'] );
			$results->set_conversions_history( $json_data['historyConversions'] );
			$results->set_first_update( $json_data['firstUpdate'] );
			$results->set_last_update( $json_data['lastUpdate'] );

			$visitors_alt = 0;
			if ( isset( $json_data['visitorsAlt'] ) )
				$visitors_alt = $json_data['visitorsAlt'];

			$conversions_alt = 0;
			if ( isset(	$json_data['conversionsAlt'] ) )
				$conversions_alt = $json_data['conversionsAlt'];

			$conversion_rate_alt = 0;
			if ( isset(	$json_data['conversionRateAlt'] ) )
				$conversion_rate_alt = $json_data['conversionRateAlt'];

			$improvement_factor_alt = 0;
			if ( isset(	$json_data['improvementAlt'] ) )
				$improvement_factor_alt = $json_data['improvementAlt'];

			if ( is_array( $json_data['alternativeStats'] ) ) {
				$aux = $json_data['alternativeStats'];
				for ( $i = 0; $i < count( $aux ); ++$i ) {
					$json_alt = $aux[$i];
					$alt_res  = new NelioABAltStats();

					if ( $i == 0 ) {
						$alternative_name = __( 'Original', 'nelioab' );
					}
					else {
						$alternative = null;
						foreach ( $this->get_alternatives() as $alt )
							if ( $alt->get_id() == $json_alt['altId'] )
								$alternative = $alt;
						if ( $alternative == null)
							continue;
						$alternative_name = $alternative->get_name();
					}

					$alt_res->set_name( $alternative_name );
					$alt_res->set_post_id( $json_alt['altId'] );
					$alt_res->set_num_of_visitors( $json_alt['visitors'] );
					$alt_res->set_num_of_conversions( $json_alt['conversions'] );
					$alt_res->set_conversion_rate( $json_alt['conversionRate'] );
					$alt_res->set_improvement_factor( $json_alt['improvementFactor'] );
					if ( isset( $json_alt['historyVisitors'] ) )
						$alt_res->set_visitors_history( $json_alt['historyVisitors'] );
					if ( isset( $json_alt['historyConversions'] ) )
						$alt_res->set_conversions_history( $json_alt['historyConversions'] );

					$results->add_alternative_results( $alt_res );
				}
			}

			if ( is_array( $json_data['gTests'] ) ) {
				foreach ( $json_data['gTests'] as $stats ) {
					$g = new NelioABGTest( $stats['message'], $this->get_original_theme()->get_id() );
					$min_ver = NULL;
					if ( isset( $stats['minVersion'] ) ) {
						$min_ver = $stats['minVersion'];
						$g->set_min( $min_ver );
					}

					$max_ver = NULL;
					if ( isset( $stats['maxVersion'] ) ) {
						$max_ver = $stats['maxVersion'];
						$g->set_max( $max_ver );
					}

					if ( isset( $stats['gtest'] ) )
						$g->set_gtest( $stats['gtest'] );
					if ( isset( $stats['pvalue'] ) )
						$g->set_pvalue( $stats['pvalue'] );
					if ( isset( $stats['certainty'] ) )
						$g->set_certainty( $stats['certainty'] );

					$g->set_min_name( __( 'Unknown', 'nelioab' ) );
					$g->set_max_name( __( 'Unknown', 'nelioab' ) );
					foreach( $this->get_alternatives() as $alt ) {
						if ( $alt->get_id() == $min_ver )
							$g->set_min_name( $alt->get_name() );
						if ( $alt->get_id() == $max_ver )
							$g->set_max_name( $alt->get_name() );
					}
					if ( $this->get_original_theme()->get_id() == $min_ver )
						$g->set_min_name( __( 'Original', 'nelioab' ) );
					if ( $this->get_original_theme()->get_id() == $max_ver )
						$g->set_max_name( __( 'Original', 'nelioab' ) );

					$results->add_gstat( $g );
				}
			}

			return $results;
		}

		protected function determine_proper_status() {
			if ( $this->get_conversion_post() < 0 )
				return NelioABExperimentStatus::DRAFT;

			return NelioABExperimentStatus::READY;
		}

		public function remove() {
			// 1. We remove the experiment itself
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/globalexp/%s/delete',
				$this->get_id()
			);

			$result = NelioABBackend::remote_post( $url );
		}

		public function discard_changes() {
			// Nothing to be done, here
 		}

		public function start() {
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/globalexp/%s/start',
				$this->get_id()
			);
			$result = NelioABBackend::remote_post( $url );
		}

		public function stop() {
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/globalexp/%s/stop',
				$this->get_id()
			);
			$result = NelioABBackend::remote_post( $url );
		}

	}//NelioABGlobalAlternativeExperiment

}

?>
