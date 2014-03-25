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


class NelioABHeatmapExperimentController {

	private $heatmap_info_added;

	public function __construct() {
		$this->heatmap_info_added = false;
	}

	public function is_relevant( $nav ) {
		$post_id = $nav['actualDestination'];
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			// Prepare COOKIES for PAGE/POST ALT EXPS
			if ( $exp->get_type() == NelioABExperiment::HEATMAP_EXP &&
			     $exp->get_post_id() == $post_id )
				return true;
		}
		return false;
	}

	public function hook_to_wordpress() {
		wp_enqueue_script( 'jquery' );
		add_action( 'wp_enqueue_scripts', array( &$this, 'load_nelioab_scripts' ) );
	}

	public function load_nelioab_scripts() {
		wp_enqueue_script( 'nelioab_sync_heatmaps',
			NELIOAB_ASSETS_URL . '/js/nelioab-sync-heatmaps.min.js?' . NELIOAB_PLUGIN_VERSION );
		if ( $this->has_post_a_heatmap_experiment() ||
		     $this->is_post_in_an_ab_experiment_with_heatmaps() ) {
			global $nelioab_controller;
			$post_id = $nelioab_controller->url_or_front_page_to_actual_postid_considering_alt_exps( $nelioab_controller->get_current_url() );
			?><script>var nelioab__hm_post_id = "<?php echo $post_id; ?>";</script><?php echo "\n";
			wp_enqueue_script( 'nelioab_track_heatmaps',
				NELIOAB_ASSETS_URL . '/js/nelioab-heatmap-tracker.min.js?' . NELIOAB_PLUGIN_VERSION );
		}
	}

	private function has_post_a_heatmap_experiment() {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		global $nelioab_controller;
		$post_id = $nelioab_controller->url_or_front_page_to_postid( $nelioab_controller->get_current_url() );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp )
			if ( $exp->get_type() == NelioABExperiment::HEATMAP_EXP &&
			     $exp->get_post_id() == $post_id )
				return true;
		return false;
	}

	private function is_post_in_an_ab_experiment_with_heatmaps() {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		global $nelioab_controller;
		$post_id = $nelioab_controller->url_or_front_page_to_postid( $nelioab_controller->get_current_url() );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::TITLE_ALT_EXP ) {
				if ( !$exp->are_heatmaps_tracked() )
					continue;
				if ( $exp->get_originals_id() == $post_id )
					return true;
				foreach ( $exp->get_alternatives() as $alt )
					if ( $alt->get_value() == $post_id )
						return true;
			}
		}
		return false;
	}

	public function save_heatmap_info_into_cache() {
		$post_id = '' . $_POST['hm-post-id'];

		$reg_data   = array();
		$click_data = array();

		$reg_data['phone']   = json_decode( $_POST['phone-data'] );
		$reg_data['tablet']  = json_decode( $_POST['tablet-data'] );
		$reg_data['desktop'] = json_decode( $_POST['desktop-data'] );
		$reg_data['hd']      = json_decode( $_POST['hd-data'] );

		$click_data['phone']   = json_decode( $_POST['phone-data-click'] );
		$click_data['tablet']  = json_decode( $_POST['tablet-data-click'] );
		$click_data['desktop'] = json_decode( $_POST['desktop-data-click'] );
		$click_data['hd']      = json_decode( $_POST['hd-data-click'] );

		$data = array( 'post_id' => $post_id, 'normal' => $reg_data, 'click' => $click_data, 'timestamp' => time() ); // BORRAR TIMESTAMP

		$heatmaps_cache = get_option( 'nelioab_heatmaps_cache', array() );
		array_push( $heatmaps_cache, $data );
		update_option( 'nelioab_heatmaps_cache', $heatmaps_cache );
		die();
	}

	public function send_heatmap_info_if_required() {
		$heatmaps_cache = get_option( 'nelioab_heatmaps_cache', array() );
		update_option( 'nelioab_heatmaps_cache', array() );

		// Preparing data for sending
		require_once( NELIOAB_UTILS_DIR . '/backend.php' );
		$credential = NelioABBackend::make_credential();

		require_once( NELIOAB_MODELS_DIR . '/settings.php' );
		$url = sprintf( NELIOAB_BACKEND_URL . '/site/%s/hm',
			NelioABSettings::get_site_id() );

		foreach ( $heatmaps_cache as $data ) {

			$post_id    = $data['post_id'];
			$reg_data   = $data['normal'];
			$click_data = $data['click'];
			$timestamp  = $data['timestamp']; // BORRAR TIMESTAMP

			foreach ( $reg_data as $res => $val ) {
				if ( $val->max <= 0 ) continue;
				$object = array(
					'value'      => json_encode( $val ),
					'resolution' => $res, 
					'post'       => $post_id,
					'isClick'    => false,
					'timestamp'  => $timestamp, // BORRAR TIMESTAMP
				);
				$wrapped_params = array();
				$wrapped_params['credential'] = $credential;
				$wrapped_params['object'] = $object;
				$app_engine_data = array(
					'headers'  => array( 'Content-Type' => 'application/json' ),
					'body'     => json_encode( $wrapped_params ),
					'blocking' => false,
				);
				try {
					NelioABBackend::remote_post_raw( $url, $app_engine_data );
				}
				catch ( Exception $e ) {}
			}

			foreach ( $click_data as $res => $val ) {
				if ( $val->max <= 0 ) continue;
				$object = array(
					'value'      => json_encode( $val ),
					'resolution' => $res, 
					'post'       => $post_id,
					'isClick'    => true,
					'timestamp'  => $timestamp, // BORRAR TIMESTAMP
				);
				$wrapped_params = array();
				$wrapped_params['credential'] = $credential;
				$wrapped_params['object'] = $object;
				$app_engine_data = array(
					'headers'  => array( 'Content-Type' => 'application/json' ),
					'body'     => json_encode( $wrapped_params ),
					'blocking' => false,
				);
				try {
					NelioABBackend::remote_post_raw( $url, $app_engine_data );
				}
				catch ( Exception $e ) {}
			}
		}

		die();
	}

}//NelioABHeatmapExperimentController

?>