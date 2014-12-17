<?php
/**
 * Copyright 2013 Nelio Software S.L.
 * This script is distributed under the terms of the GNU General Public
 * License.
 *
 * This script is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License.
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <http://www.gnu.org/licenses/>.
 */


class NelioABHeatmapExperimentController {

	private $heatmap_info_added;

	public function __construct() {
		$this->heatmap_info_added = false;
	}

	public function hook_to_wordpress() {
		// Nothing to be done
	}

	public function is_relevant( $nav ) {
		$post_id = $nav['currentId'];
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::HEATMAP_EXP &&
			     $exp->get_post_id() == $post_id )
				return true;
		}
		return false;
	}

	public function track_heatmaps_for_post( $post_id ) {
		global $nelioab_controller;
		$post = $nelioab_controller->url_or_front_page_to_actual_postid_considering_alt_exps(
			$nelioab_controller->get_current_url() );

		$res = array();
		$mode = NelioABSettings::get_heatmap_tracking_mode();
		if ( $this->has_post_a_heatmap_experiment( $post ) )
			$res['action'] = $mode;
		elseif ( $this->is_post_in_an_ab_experiment_with_heatmaps( $post ) )
			$res['action'] = $mode;
		else
			$res['action'] = 'DONT_TRACK_HEATMAPS';

		$aux = NelioABAccountSettings::get_customer_id() . NelioABAccountSettings::get_site_id() .
			$post_id .
			NelioABAccountSettings::get_reg_num();
		$res['sec'] = strtolower( md5( $aux ) );

		return $res;
	}

	private function has_post_a_heatmap_experiment( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp )
			if ( $exp->get_type() == NelioABExperiment::HEATMAP_EXP &&
			     $exp->get_post_id() == $post_id )
				return true;
		return false;
	}

	private function is_post_in_an_ab_experiment_with_heatmaps( $post_id ) {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
		foreach ( $running_exps as $exp ) {
			if ( $exp->get_type() == NelioABExperiment::POST_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ||
			     $exp->get_type() == NelioABExperiment::HEADLINE_ALT_EXP ) {
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

}//NelioABHeatmapExperimentController

