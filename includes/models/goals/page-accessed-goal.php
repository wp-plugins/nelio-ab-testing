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


if( !class_exists( 'NelioABPageAccessedGoal' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/page-description.php' );
	require_once( NELIOAB_MODELS_DIR . '/goal-results/page-accessed-goal-result.php' );

	require_once( NELIOAB_MODELS_DIR . '/goals/goal.php' );
	class NelioABPageAccessedGoal extends NelioABGoal {

		private $pages;

		public function __construct( $exp ) {
			parent::__construct( $exp );
			$this->set_kind( NelioABGoal::PAGE_ACCESSED_GOAL );
			$this->pages = array();
		}

		public function get_pages() {
			return $this->pages;
		}

		public function add_page( $page ) {
			array_push( $this->pages, $page );
		}

		public function includes_internal_page( $post_id ) {
			foreach( $this->pages as $page ) {
				if ( !$page->is_internal() )
					continue;
				if ( $page->get_reference() == $post_id )
					return true;
			}
			return false;
		}

		public function includes_external_page( $url ) {
			foreach( $this->pages as $page ) {
				if ( !$page->is_external() )
					continue;
				if ( $page->get_reference() == $url )
					return true;
			}
			return false;
		}

		public function is_ready() {
			return count( $this->get_pages() ) > 0;
		}

		public static function decode_from_appengine( $exp, $json ) {
			$result = new NelioABPageAccessedGoal( $exp );
			$result->set_id( $json->key->id );
			$result->set_name( $json->name );
			$result->set_as_main_goal( $json->isMainGoal );
			require_once( NELIOAB_MODELS_DIR . '/page-description.php' );
			if ( isset( $json->pages ) )
				foreach ( $json->pages as $p )
					$result->add_page( NelioABPageDescription::decode_from_appengine( $p ) );
			return $result;
		}

		public function get_results() {
			$results    = new NelioABPageAccessedGoalResult();
			$experiment = $this->get_experiment();

			$url = sprintf(
				NELIOAB_BACKEND_URL . '/goal/page-accessed/%s/result',
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

			$alt_res = new NelioABAltStats( true ); // Original
			$alt_res->set_name( __( 'Original', 'nelioab' ) );
			$alt_res->set_alt_id( $json_data['originalStats']['altId'] );
			$alt_res->set_num_of_visitors( $json_data['originalStats']['visitors'] );
			$alt_res->set_num_of_conversions( $json_data['originalStats']['conversions'] );
			$alt_res->set_conversion_rate( $json_data['originalStats']['conversionRate'] );
			if ( isset( $json_data['originalStats']['historyVisitors'] ) )
				$alt_res->set_visitors_history( $json_data['originalStats']['historyVisitors'] );
			if ( isset( $json_data['originalStats']['historyConversions'] ) )
				$alt_res->set_conversions_history( $json_data['originalStats']['historyConversions'] );
			$results->add_alternative_results( $alt_res );

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
				$first_update = $json_data['firstUpdate'];
				foreach ( $json_data['alternativeStats'] as $json_alt ) {
					$alt_res = new NelioABAltStats();

					$alternative = null;
					foreach ( $experiment->get_alternatives() as $alt ) {
						if ( $alt->get_id() == $json_alt['altId'] )
							$alternative = $alt;
					}

					if ( $alternative == null ) {
						foreach ( $experiment->get_alternatives() as $alt ) {
							if ( $alt->get_value() == $json_alt['altId'] )
								$alternative = $alt;
						}
					}

					if ( $alternative == null )
						continue;

					$alt_res->set_name( $alternative->get_name() );
					$alt_res->set_alt_id( $json_alt['altId'] );
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
					$g = new NelioABGTest( $stats['message'], $experiment->get_originals_id() );
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
					foreach( $experiment->get_alternatives() as $alt ) {
						if ( $alt->get_value() == $min_ver )
							$g->set_min_name( $alt->get_name() );
						if ( $alt->get_value() == $max_ver )
							$g->set_max_name( $alt->get_name() );
					}
					if ( $experiment->get_originals_id() == $min_ver )
						$g->set_min_name( __( 'Original', 'nelioab' ) );
					if ( $experiment->get_originals_id() == $max_ver )
						$g->set_max_name( __( 'Original', 'nelioab' ) );

					$results->add_gstat( $g );
				}
			}

			return $results;
		}

	}//NelioABPageAccessedGoal

}

?>
