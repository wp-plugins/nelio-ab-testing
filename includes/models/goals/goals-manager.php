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


if( !class_exists( 'NelioABGoalsManager' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/goals/goal.php' );
	require_once( NELIOAB_MODELS_DIR . '/goals/actions/action.php' );

	/**
	 * A class containing some useful functions for managing goals.
	 *
	 * @package \NelioABTesting\Models\Goals
	 * @since 1.4.0
	 */
	abstract class NelioABGoalsManager {


		/**
		 * Given a $json goal, creates a new instance of NelioABGoal and adds it to $exp.
		 *
		 * @param NelioABExperiment $exp  The experiment to which the goal has to be added.
		 * @param object            $json An AppEngine JSON goal.
		 *
		 * @return void
		 *
		 * @since 1.4.0
		 */
		public static function load_goal_from_json( $exp, $json ) {

			if ( isset( $json->kind ) ) {
				switch ( $json->kind ) {

					case NelioABGoal::ALTERNATIVE_EXPERIMENT_GOAL_STR:
					require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );
					$goal = NelioABAltExpGoal::decode_from_appengine( $exp, $json );
					$exp->add_goal( $goal );
					break;

				}
			}

		}

	}//NelioABGoalsManager

}

