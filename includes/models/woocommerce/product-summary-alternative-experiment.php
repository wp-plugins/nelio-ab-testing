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


if ( !class_exists( 'NelioABProductSummaryAlternativeExperiment' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/alternatives/headline-alternative-experiment.php' );

	/**
	 * PHPDOC
	 *
	 * @package \NelioABTesting\Models\Experiments\AB
	 * @since 4.2.0
	 */
	class NelioABProductSummaryAlternativeExperiment extends NelioABHeadlineAlternativeExperiment {

		// @Override
		public function __construct( $id ) {
			parent::__construct( $id );
			$this->set_type( NelioABExperiment::WC_PRODUCT_SUMMARY_ALT_EXP );
		}


		// @Override
		public static function load( $id ) {
			$json_data = NelioABBackend::remote_get( NELIOAB_BACKEND_URL . '/exp/post/' . $id );
			$json_data = json_decode( $json_data['body'] );

			$exp = new NelioABHeadlineAlternativeExperiment( $json_data->key->id );
			$exp->set_name( $json_data->name );
			if ( isset( $json_data->description ) )
				$exp->set_description( $json_data->description );
			$exp->set_type_using_text( $json_data->kind );
			$exp->set_original( $json_data->originalPost );
			$exp->set_status( $json_data->status );
			$exp->set_finalization_mode( $json_data->finalizationMode );
			if ( isset( $json_data->finalizationModeValue ) )
				$exp->set_finalization_value( $json_data->finalizationModeValue );
			$exp->track_heatmaps( false );
			if ( isset( $json_data->showHeatmap ) && $json_data->showHeatmap  )
				$exp->track_heatmaps( $json_data->showHeatmap );

			if ( isset( $json_data->goals ) )
				NelioABExperiment::load_goals_from_json( $exp, $json_data->goals );

			$alternatives = array();
			if ( isset( $json_data->alternatives ) ) {
				foreach ( $json_data->alternatives as $json_alt ) {
					$alt = new NelioABHeadlineAlternative( $json_alt->key->id );
					$alt->set_name( $json_alt->name );
					$alt->set_value( json_decode( $json_alt->value, true ) );
					array_push ( $alternatives, $alt );
				}
			}
			$exp->set_appspot_alternatives( $alternatives );

			return $exp;
		}

	}

}

