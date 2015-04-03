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


if( !class_exists( 'NelioABFormSubmissionAction' ) ) {

	class NelioABFormSubmissionAction extends NelioABAction {

		const SUBMIT_KIND           = 'submit';
		const CONTACT_FORM_7_PLUGIN = 'contact-form-7';
		const GRAVITY_FORM_PLUGIN   = 'gravity-form';

		private $form_id;
		private $indirect;

		/**
		 * Constructor of this class. By default, a Page does not accept
		 * indirect navigations, is internal to WordPress, and its ID is -1.
		 *
		 * @param form_id the POST_ID or the PAGE_URL that uniquely
		 *        identifies a page.
		 */
		public function __construct( $type, $form_id ) {
			parent::__construct( $type );
			$this->form_id  = $form_id;
			$this->indirect = false;
		}

		/**
		 * @return this form's ID
		 */
		public function get_form_id() {
			return $this->form_id;
		}

		/**
		 * Returns whether submitting this form is to be counted as a
		 * conversion this page is to be counted as a conversion, even
		 * if the submission is not performed in the tested page.
		 *
		 * @return whether submitting this form is to be counted as a
		 *         conversion this page is to be counted as a conversion,
		 *         even if the submission is not performed in the tested
		 *         page.
		 */
		public function accepts_submissions_from_any_page() {
			return $this->indirect;
		}

		/**
		 * This function is used to enable or disable whether a conversion
		 * should be counted when the form is submitted from a page that is
		 * not the tested page.
		 *
		 * @param indirect if false, then a conversion is counted only if the
		 *        submission is performed from the tested page. Otherwise, it
		 *        is counted from any page.
		 */
		public function accept_sumissions_from_any_page( $indirect = true ) {
			$this->indirect = $indirect;
		}

		/**
		 *
		 */
		public static function type_to_kind_and_plugin( $type ) {
			switch ( $type ) {

				case NelioABAction::SUBMIT_CF7_FORM:
					$kind   = self::SUBMIT_KIND;
					$plugin = self::CONTACT_FORM_7_PLUGIN;
					break;

				case NelioABAction::SUBMIT_GRAVITY_FORM:
					$kind   = self::SUBMIT_KIND;
					$plugin = self::GRAVITY_FORM_PLUGIN;
					break;

				default:
					// This is not possible...
					return false;
			}

			return array( 'kind' => $kind, 'plugin' => $plugin );
		}

		/**
		 *
		 */
		public static function kind_and_plugin_to_type( $kind, $plugin ) {
			$type = false;
			switch ( $kind ) {

				case self::SUBMIT_KIND:
					if ( self::CONTACT_FORM_7_PLUGIN == $plugin )
						$type = NelioABAction::SUBMIT_CF7_FORM;
					elseif ( self::GRAVITY_FORM_PLUGIN == $plugin )
						$type = NelioABAction::SUBMIT_GRAVITY_FORM;
					break;

				default:
					// This is not possible...
			}
			return $type;
		}

		/**
		 * Returns an array of values, ready to be JSON-codified and
		 * prepared for AppEngine
		 *
		 * @return the JSON array for AppEngine
		 */
		public function encode_for_appengine() {
			$action = false;
			$kap = self::type_to_kind_and_plugin( $this->get_type() );
			if ( $kap ) {
				$action = array(
					'kind'    => $kap['kind'],
					'form'    => $this->get_form_id(),
					'plugin'  => $kap['plugin'],
					'anyPage' => $this->accepts_submissions_from_any_page(),
				);
			}
			return $action;
		}

		/**
		 * Creates a Form Submission Action using the information obtained
		 * from the JSON parameter.
		 *
		 * @param json the JSON array from AppEngine
		 *
		 * @return a Form Submission Action with the values obtained from
		 *         the json
		 */
		public static function decode_from_appengine( $json ) {
			$action = false;
			$type = self::kind_and_plugin_to_type( $json->kind, $json->plugin );
			if ( $type ) {
				$action = new NelioABFormSubmissionAction( $type, $json->form );
				$action->accept_sumissions_from_any_page( isset( $json->anyPage ) && $json->anyPage );
			}
			if ( isset( $json->key->id ) )
				$action->set_id( $json->key->id );
			return $action;
		}

		/**
		 * @implements NelioABAction::json4js();
		 */
		public function json4js() {
			$action = array(
					'type'     => 'form-submit',
					'form_id'  => $this->get_form_id(),
					'any_page' => $this->accepts_submissions_from_any_page(),
				);
			if ( $this->get_type() == NelioABAction::SUBMIT_CF7_FORM )
				$action['form_type'] = 'cf7';
			else
				$action['form_type'] = 'gf';
			return $action;
		}

		/**
		 *
		 */
		public static function build_action_using_json4js( $json ) {
			if ( 'cf7' == $json->form_type )
				$type = NelioABAction::SUBMIT_CF7_FORM;
			else
				$type = NelioABAction::SUBMIT_GRAVITY_FORM;
			$action = new NelioABFormSubmissionAction( $type, $json->form_id );
			$action->accept_sumissions_from_any_page( $json->any_page );
			return $action;
		}

	}//NelioABFormSubmissionAction

}

