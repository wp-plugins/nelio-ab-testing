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


if( !class_exists( 'NelioABGoalsManager' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/goals/goal.php' );
	require_once( NELIOAB_MODELS_DIR . '/goals/actions/action.php' );

	abstract class NelioABGoalsManager {

		public static function load_goal_from_json( $exp, $json ) {

			switch ( $json->kind ) {

				case NelioABGoal::ALTERNATIVE_EXPERIMENT_GOAL_STR:
				require_once( NELIOAB_MODELS_DIR . '/goals/alternative-experiment-goal.php' );
				$goal = NelioABAltExpGoal::decode_from_appengine( $exp, $json );
				$exp->add_goal( $goal );
				break;

			}

		}

		public static function encode_goal_for_appengine( $goal ) {
			$res = array(
				'name'       => $goal->get_name(),
				'kind'       => $goal->get_textual_kind(),
				'isMainGoal' => $goal->is_main_goal()
			);
			switch ( $goal->get_kind() ) {

				case NelioABGoal::ALTERNATIVE_EXPERIMENT_GOAL:
					$page_accessed_actions = array();
					$form_actions = array();
					$order = 0;
					foreach ( $goal->get_actions() as $action ) {
						$encoded_action = $action->encode_for_appengine();
						switch( $action->get_type() ) {

							case NelioABAction::PAGE_ACCESSED:
							case NelioABAction::POST_ACCESSED:
							case NelioABAction::EXTERNAL_PAGE_ACCESSED:
								$order++;
								$encoded_action['order'] = $order;
								array_push( $page_accessed_actions, $encoded_action );
								break;

							case NelioABAction::SUBMIT_CF7_FORM:
							case NelioABAction::SUBMIT_GRAVITY_FORM:
								$order++;
								$encoded_action['order'] = $order;
								array_push( $form_actions, $encoded_action );
								break;

						}
					}

					$res['pageAccessedActions'] = $page_accessed_actions;
					$res['formActions'] = $form_actions;
					break;

			}
			return $res;
		}

	}//NelioABGoalsManager

}

