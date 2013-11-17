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


if( !class_exists( 'NelioABPostAlternativeExperiment' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/alternatives/alternative-experiment.php' );

	class NelioABPostAlternativeExperiment extends NelioABAlternativeExperiment {

		private $ori;

		public function __construct( $id ) {
			parent::__construct( $id );
			$this->set_type( NelioABExperiment::NO_TYPE_SET );
		}

		public function clear() {
			parent::clear();
			$this->ori = -1;
		}

		public function get_original() {
			return $this->ori;
		}

		public function set_original( $ori ) {
			$this->ori = $ori;

			// Setting type
			$post = get_post( $ori, ARRAY_A );
			if ( isset( $post ) &&
			     ( $this->get_type() == NelioABExperiment::NO_TYPE_SET ||
			       $this->get_type() == NelioABExperiment::UNKNOWN_TYPE )
			   ) {
				if ( $post['post_type'] == 'page' )
					$this->set_type( NelioABExperiment::PAGE_ALT_EXP );
				else
					$this->set_type( NelioABExperiment::POST_ALT_EXP );
			}
		}

		public function create_empty_alternative( $name, $post_type ) {
			if ( $post_type == NelioABExperiment::PAGE_ALT_EXP )
				$post_type = 'page';
			else
				$post_type = 'post';
			$post = array(
				'post_type'    => $post_type,
				'post_title'   => $name,
				'post_content' => '',
				'post_excerpt' => '',
				'post_status'  => 'draft',
				'post_name'    => 'nelioab_' . rand(1, 10),
			);

			$post_id = wp_insert_post( $post, true );
			$aux = get_post( $post_id, ARRAY_A );
			$aux['post_name'] = 'nelioab_' . $post_id;
			wp_update_post( $aux );

			add_post_meta( $post_id, '_is_nelioab_alternative', 'true' );

			$alt = new NelioABAlternative();
			$alt->set_name( $name );
			$alt->set_value( $post_id );

			$this->add_local_alternative( $alt );
		}

		public function create_alternative_copying_content( $name, $src_post_id ) {
			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );

			$src_post = get_post( $src_post_id, ARRAY_A );

			$src_post['ID']          = null;
			$src_post['post_status'] = 'draft';
			$src_post['post_name']   = 'nelioab_' . rand(1, 10);

			$new_post_id = wp_insert_post( $src_post, true );
			$aux = get_post( $new_post_id, ARRAY_A );
			$aux['post_name'] = 'nelioab_' . $new_post_id;
			wp_update_post( $aux );

			add_post_meta( $new_post_id, '_is_nelioab_alternative', 'true' );
			NelioABWpHelper::override( $src_post_id, $new_post_id );

			$alt = new NelioABAlternative();
			$alt->set_name( $name );
			$alt->set_value( $new_post_id );

			$this->add_local_alternative( $alt );
		}

		public function get_results() {
			$results = new NelioABAltExpResult();
			
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/v3/postexp/%s/result',
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
			$alt_res->set_alt_id( $this->get_original() );
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
				foreach ( $json_data['alternativeStats'] as $json_alt ) {
					$alt_res = new NelioABAltStats();

					$alternative = null;
					foreach ( $this->get_alternatives() as $alt )
						if ( $alt->get_value() == $json_alt['altId'] )
							$alternative = $alt;

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
					$g = new NelioABGTest( $stats['message'], $this->get_original() );
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
						if ( $alt->get_value() == $min_ver )
							$g->set_min_name( $alt->get_name() );
						if ( $alt->get_value() == $max_ver )
							$g->set_max_name( $alt->get_name() );
					}
					if ( $this->get_original() == $min_ver )
						$g->set_min_name( __( 'Original', 'nelioab' ) );
					if ( $this->get_original() == $max_ver )
						$g->set_max_name( __( 'Original', 'nelioab' ) );

					$results->add_gstat( $g );
				}
			}

			return $results;
		}

		protected function determine_proper_status() {
			if ( count( $this->get_alternatives() ) <= 0 )
				return NelioABExperimentStatus::DRAFT;

			if ( $this->get_original() < 0 )
				return NelioABExperimentStatus::DRAFT;

			if ( count( $this->get_conversion_posts() ) == 0 )
				return NelioABExperimentStatus::DRAFT;

			return NelioABExperimentStatus::READY;
		}

		public function save() {
			require_once( NELIOAB_MODELS_DIR . '/settings.php' );

			// 1. UPDATE OR CREATE THE EXPERIMENT
			// -------------------------------------------------------------------------

			$url = '';
			if ( $this->get_id() < 0 ) {
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/v3/site/%s/postexp',
					NelioABSettings::get_site_id()
				);
			}
			else {
				$url = sprintf(
					NELIOAB_BACKEND_URL . '/v3/postexp/%s/update',
					$this->get_id()
				);
			}

			if ( $this->get_status() == NelioABExperimentStatus::READY ||
				$this->get_status() == NelioABExperimentStatus::DRAFT ||
				!defined( $this->get_status() ) )
				$this->set_status( $this->determine_proper_status() );

			$body = array(
				'name'           => $this->get_name(),
				'description'    => $this->get_description(),
				'originalPost'   => $this->get_original(),
				'conversionPost' => $this->get_conversion_posts(),
				'status'         => $this->get_status(),
				'kind'           => $this->get_kind_name( $this->get_type() ),
			);

			$result = NelioABBackend::remote_post( $url, $body );

			$exp_id = $this->get_id();
			if ( $exp_id < 0 ) {
				if ( is_wp_error( $result ) )
					return;
				$json = json_decode( $result['body'] );
				$exp_id = $json->key->id;
				$this->id = $exp_id;
			}


			// 2. UPDATE THE ALTERNATIVES
			// -------------------------------------------------------------------------

			// 2.1. UPDATE CHANGES ON ALREADY EXISTING APPSPOT ALTERNATIVES
			foreach ( $this->get_appspot_alternatives() as $alt ) {
				if ( $alt->was_removed() || !$alt->is_dirty() )
					continue;

				$body = array( 'name' => $alt->get_name() );
				$result = NelioABBackend::remote_post(
					sprintf( NELIOAB_BACKEND_URL . '/v3/alternative/%s/update', $alt->get_id() ),
					$body );
			}

			// 2.2. REMOVE FROM APPSPOT THE REMOVED ALTERNATIVES
			foreach ( $this->get_appspot_alternatives() as $alt ) {
				if ( !$alt->was_removed() )
					continue;

				$url = sprintf(
					NELIOAB_BACKEND_URL . '/v2/wp/delete/alternative/%s',
					$alt->get_id()
				);

				$result = NelioABBackend::remote_post( $url );
			}


			// 2.3. CREATE LOCAL ALTERNATIVES IN APPSPOT
			foreach ( $this->get_local_alternatives() as $alt ) {
				if ( $alt->was_removed() )
					continue;

				$body = array(
					'name'  => $alt->get_name(),
					'value' => $alt->get_value(),
					'kind'  => NelioABExperiment::get_kind_name( $this->get_type() ),
				);

				try {
					$result = NelioABBackend::remote_post(
						sprintf( NELIOAB_BACKEND_URL . '/v3/postexp/%s/alternative', $exp_id ),
						$body );
				}
				catch ( Exception $e ) {
					// If I could not add an alternative... remove the associated page
					wp_delete_post( $alt->get_value() );
				}

			}


			// 2.3 REMOVE THE PAGES THAT BELONGED TO AN ALTERNATIVE THAT HAS BEEN DELETED
			$all_alternatives = array_merge(
					$this->get_appspot_alternatives(),
					$this->get_local_alternatives()
				);
			foreach ( $all_alternatives as $alt ) {
				if ( $alt->was_removed() ) {
					// Delete permanently (skipping Trash)
					wp_delete_post( $alt->get_value(), true );
				}
			}

			// 2.4 SET META "_is_nelioab_alternative" WITH THE ID OF THE EXPERIMENT
			foreach ( $this->get_alternatives() as $alt ) {
				$value = $this->get_id() . ',' . $this->get_status();
				update_post_meta( $alt->get_value(), "_is_nelioab_alternative", $value );
			}
		}

		public function remove() {
			// 1. For each alternative, we first remove its associated page
			foreach ( $this->get_alternatives() as $alt )
				wp_delete_post( $alt->get_value(), true );

			// 2. We remove the experiment itself
			$url = sprintf(
				NELIOAB_BACKEND_URL . '/v3/postexp/%s/delete',
				$this->get_id()
			);

			$result = NelioABBackend::remote_post( $url );
		}

		public function discard_changes() {
			foreach ( $this->get_local_alternatives() as $alt ) {
				// Delete permanently (skipping Trash)
				wp_delete_post( $alt->get_value(), true );
			}
 		}

		public function start() {
			$ori = get_post( $this->get_original() );
			if ( $ori ) {
				foreach ( $this->get_alternatives() as $alt ) {
					$alt_post = get_post( $alt->get_value() );
					if ( $alt_post ) {
						$alt_post->comment_status = $ori->comment_status;
						wp_update_post( $alt_post );
					}
				}
			}
			parent::start();
		}

		public function set_status( $status ) {
			parent::set_status( $status );
			foreach ( $this->get_alternatives() as $alt ) {
				$value = $this->get_id() . ',' . $this->get_status();
				update_post_meta( $alt->get_value(), "_is_nelioab_alternative", $value );
			}
		}

	}//NelioABPostAlternativeExperiment

}

?>
