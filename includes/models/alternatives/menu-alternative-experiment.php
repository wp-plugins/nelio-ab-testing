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


if ( !class_exists( 'NelioABMenuAlternativeExperiment' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/alternatives/global-alternative-experiment.php' );

	/**
	 * PHPDOC
	 *
	 * @package \NelioABTesting\Models\Experiments\AB
	 * @since PHPDOC
	 */
	class NelioABMenuAlternativeExperiment extends NelioABGlobalAlternativeExperiment {

		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var array
		 */
		private $new_ids;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var array
		 */
		private $original_appspot_menu;


		// @Override
		public function clear() {
			parent::clear();
			$this->set_type( NelioABExperiment::MENU_ALT_EXP );
			$this->new_ids = array();
			$this->original_appspot_menu = $this->create_alternative_menu( 'FakeOriginalMenu' );
		}


		// @Override
		public function get_original() {
			return $this->original_appspot_menu;
		}


		// @Override
		public function get_originals_id() {
			/** @var NelioABAlternative $aux */
			$aux = $this->get_original();
			return $aux->get_id();
		}


		// @Override
		public function set_original_ids( $ae_id, $menu_id ) {
			/** @var NelioABAlternative $aux */
			$aux = $this->get_original();
			$aux->set_id( $ae_id );
			$aux->set_value( $menu_id );
		}


		// @Override
		public function set_appspot_alternatives( $alts ) {
			$aux = array();
			if ( count( $alts ) > 0 ) {
				$this->original_appspot_menu = $alts[0];
				for ( $i = 1; $i < count( $alts ); $i++ )
					array_push( $aux, $alts[$i] );
			}
			parent::set_appspot_alternatives( $aux );
		}

		/**
		 * Returns PHPDOC
		 *
		 * @param string $name PHPDOC
		 *
		 * @return NelioABAlternative PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function create_alternative_menu( $name ) {
			$alts = $this->get_alternatives();
			$fake_post_id = -1;
			foreach ( $alts as $aux ) {
				/** @var NelioABAlternative $aux */
				if ( $aux->get_id() <= $fake_post_id )
					$fake_post_id = $aux->get_id() - 1;
			}
			$alt = new NelioABAlternative();
			$alt->set_id( $fake_post_id );
			$alt->set_name( $name );
			$alt->set_value( '' );
			return $alt;
		}


		// @Override
		protected function determine_proper_status() {
			if ( count( $this->get_alternatives() ) <= 0 )
				return NelioABExperiment::STATUS_DRAFT;
			return parent::determine_proper_status();
		}


		// @Override
		public function save() {
			require_once( NELIOAB_EXP_CONTROLLERS_DIR . '/menu-experiment-controller.php' );
			$controller = NelioABMenuExpAdminController::get_instance();

			// 1. SAVE THE EXPERIMENT AND ITS GOALS
			// -------------------------------------------------------------------------
			$exp_id = parent::save();


			// 2. UPDATE THE ALTERNATIVES
			// -------------------------------------------------------------------------

			// 2.0. FIRST OF ALL, WE CREATE A FAKE ORIGINAL FOR NEW EXPERIMENTS
			/** @var NelioABAlternative $original */
			$original = $this->get_original();
			if ( $original->get_id() < 0 ) {
				$body = array(
					'name'  => $original->get_name(),
					'value' => $original->get_value(),
					'kind'  => $this->get_textual_type(),
				);
				try {
					/** @var int $result */
					$result = NelioABBackend::remote_post(
						sprintf( NELIOAB_BACKEND_URL . '/exp/global/%s/alternative', $exp_id ),
						$body );
					$original->set_id( $result );
				} catch ( Exception $e ) {}
			}
			// AND WE UPDATE THE ORIGINAL VALUE (WHICH MAY HAVE BEEN CHANGED)
			else {
				$body = array( 'value' => $original->get_value() );
				try {
					NelioABBackend::remote_post(
						sprintf( NELIOAB_BACKEND_URL . '/alternative/%s/update',
							$original->get_id() ), $body );
				} catch ( Exception $e ) {}
			}

			// 2.1. UPDATE CHANGES ON ALREADY EXISTING APPSPOT ALTERNATIVES
			foreach ( $this->get_appspot_alternatives() as $alt ) {
				/** @var NelioABAlternative $alt */
				if ( $alt->was_removed() || !$alt->is_dirty() )
					continue;
				$body = array( 'name' => $alt->get_name() );
				try {
					NelioABBackend::remote_post(
						sprintf( NELIOAB_BACKEND_URL . '/alternative/%s/update', $alt->get_id() ),
						$body );
				} catch ( Exception $e ) {}
			}

			// 2.2. REMOVE FROM APPSPOT THE REMOVED ALTERNATIVES
			$controller->begin();
			foreach ( $this->get_appspot_alternatives() as $alt ) {
				/** @var NelioABAlternative $alt */
				if ( !$alt->was_removed() )
					continue;

				$url = sprintf(
					NELIOAB_BACKEND_URL . '/alternative/%s/delete',
					$alt->get_id()
				);

				try {
					NelioABBackend::remote_post( $url );
					$controller->remove_alternative_menu( $alt->get_value() );
				} catch( Exception $e ) {}
			}
			$controller->commit();

			// 2.3. CREATE LOCAL ALTERNATIVES IN APPSPOT
			$this->new_ids = array();
			$controller->begin();
			foreach ( $this->get_local_alternatives() as $alt ) {
				/** @var NelioABAlternative $alt */
				if ( $alt->was_removed() )
					continue;
				if ( $alt->is_based_on_another_element() )
					$menu_id = $controller->duplicate_menu_and_create_alternative(
						$alt->get_base_element(), $this->get_id() );
				else
					$menu_id = $controller->create_alternative_menu( $this->get_id() );
				$alt->set_value( $menu_id );
				$body = array(
					'name'  => $alt->get_name(),
					'value' => $alt->get_value(),
					'kind'  => $this->get_textual_type(),
				);

				try {
					/** @var object|array $result */
					$result = NelioABBackend::remote_post(
						sprintf( NELIOAB_BACKEND_URL . '/exp/global/%s/alternative', $exp_id ),
						$body );
					$result = json_decode( $result['body'] );
					$this->new_ids[$alt->get_id()] = $result->key->id;
					$alt->set_id( $result->key->id );
					$controller->link_menu_to_experiment( $menu_id, $this->get_id(), $alt->get_id() );
				}
				catch ( Exception $e ) {
				}
			}
			$controller->commit();

			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			NelioABExperimentsManager::update_experiment( $this );
		}


		/**
		 * Returns PHPDOC
		 *
		 * @param int $id PHPDOC
		 *
		 * @return int PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_real_id_for_alt( $id ) {
			if ( isset( $this->new_ids[$id] ) )
				return $this->new_ids[$id];
			else
				return $id;
		}

		/**
		 * PHPDOC
		 *
		 * @param int    $alt_id  PHPDOC
		 * @param string $name    PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public static function update_menu_set_alternative( $alt_id, $name ) {
			$body = array(
				'name' => $name,
			);
			NelioABBackend::remote_post(
				sprintf( NELIOAB_BACKEND_URL . '/alternative/%s/update', $alt_id ),
				$body );
		}


		// @Override
		public function remove() {
			require_once( NELIOAB_EXP_CONTROLLERS_DIR . '/menu-experiment-controller.php' );
			$controller = NelioABMenuExpAdminController::get_instance();

			// 1. Remove the local alternatives
			$controller->begin();
			foreach ( $this->get_alternatives() as $alt ) {
				/** @var NelioABAlternative $alt */
				$controller->remove_alternative_menu( $alt->get_value() );
			}
			$controller->commit();

			// 2. We remove the experiment itself
			parent::remove();
		}


		// @Override
		public function duplicate( $new_name ) {
			$id = parent::duplicate( $new_name );
			if ( -1 == $id )
				return $id;

			require_once( NELIOAB_EXP_CONTROLLERS_DIR . '/menu-experiment-controller.php' );
			$controller = NelioABMenuExpAdminController::get_instance();

			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			/** @var NelioABMenuAlternativeExperiment $exp */
			$exp = NelioABExperimentsManager::get_experiment_by_id( $id, $this->get_type() );

			$alts = 0;
			$controller->begin();
			foreach ( $exp->get_alternatives() as $alt ) {
				/** @var NelioABAlternative $alt */
				$menu_id = $controller->duplicate_menu_and_create_alternative(
					$alt->get_value(), $exp->get_id() );
				$body = array( 'value' => $menu_id );
				try {
					NelioABBackend::remote_post(
						sprintf( NELIOAB_BACKEND_URL . '/alternative/%s/update',
							$alt->get_id() ), $body );
					$alts++;
				} catch ( Exception $e ) {}
			}
			$controller->commit();

			if ( 0 == $alts )
				$exp->set_status( NelioABExperiment::STATUS_DRAFT );

			$exp->save();
			return $exp->get_id();
		}


		// @Implements
		public static function load( $id ) {
			$json_data = NelioABBackend::remote_get( NELIOAB_BACKEND_URL . '/exp/global/' . $id );
			$json_data = json_decode( $json_data['body'] );

			$exp = new NelioABMenuAlternativeExperiment( $json_data->key->id );
			$exp->set_type_using_text( $json_data->kind );
			$exp->set_name( $json_data->name );
			if ( isset( $json_data->description ) )
				$exp->set_description( $json_data->description );
			$exp->set_status( $json_data->status );
			$exp->set_finalization_mode( $json_data->finalizationMode );
			if ( isset( $json_data->finalizationModeValue ) )
				$exp->set_finalization_value( $json_data->finalizationModeValue );

			if ( isset( $json_data->goals ) )
				NelioABExperiment::load_goals_from_json( $exp, $json_data->goals );

			$alternatives = array();
			if ( isset( $json_data->alternatives ) ) {
				foreach ( $json_data->alternatives as $json_alt ) {
					$alt = new NelioABAlternative( $json_alt->key->id );
					$alt->set_name( $json_alt->name );
					if ( isset( $json_alt->value ) )
						$alt->set_value( $json_alt->value );
					else
						$alt->set_value( '' );
					array_push ( $alternatives, $alt );
				}
			}
			$exp->set_appspot_alternatives( $alternatives );

			return $exp;
		}

	}//NelioABMenuAlternativeExperiment

}

