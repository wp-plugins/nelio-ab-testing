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


if ( !class_exists( 'NelioABFormSubmissionAction' ) ) {

	/**
	 * Class representing a "form submission" conversion action.
	 *
	 * @package \NelioABTesting\Models\Goals\Actions
	 * @since PHPDOC
	 */
	class NelioABFormSubmissionAction extends NelioABAction {

		/**
		 * Constant PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		const SUBMIT_KIND = 'submit';


		/**
		 * Constant PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		const CONTACT_FORM_7_PLUGIN = 'contact-form-7';


		/**
		 * Constant PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		const GRAVITY_FORM_PLUGIN = 'gravity-form';


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var int
		 */
		private $form_id;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var boolean
		 */
		private $indirect;


		/**
		 * Creates a new instance of this class.
		 *
		 * @param string $type    PHPDOC
		 * @param string $form_id PHPDOC
		 *
		 * @return NelioABFormSubmissionAction a new instance of this class.
		 *
		 * @since PHPDOC
		 */
		public function __construct( $type, $form_id ) {
			parent::__construct( $type );
			$this->form_id  = $form_id;
			$this->indirect = false;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @return int PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function get_form_id() {
			return $this->form_id;
		}


		/**
		 * Returns whether submitting this form from a page that is not the tested page should be counted as a conversion or not.
		 *
		 * @return boolean whether submitting this form from a page that is not the tested page should be counted as a conversion or not.
		 *
		 * @since PHPDOC
		 */
		public function accepts_submissions_from_any_page() {
			return $this->indirect;
		}


		/**
		 * Enables or disables whether conversions should be counted when the form is submitted from a page that is not the tested page.
		 *
		 * @param boolean $indirect It specifies whether conversion should be counted from any page or from the tested page only.
		 *                If true, conversions are counted whenever the form is
		 *                submitted, regardless of the page from which the
		 *                submission was triggered. If false, only submissions
		 *                from the tested page will convert.
		 *
		 * @since PHPDOC
		 */
		public function accept_sumissions_from_any_page( $indirect = true ) {
			$this->indirect = $indirect;
		}


		/**
		 * Returns PHPDOC
		 *
		 * @param string $type PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
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
		 * Returns PHPDOC
		 *
		 * @param string $kind   PHPDOC
		 * @param string $plugin PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
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


		// @Implements
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
		 * Returns a new action object built using the information described in $action.
		 *
		 * @param object $json a JSON action returned by AppEngine.
		 *
		 * @return NelioABFormSubmissionAction the new action containing all the information in `$action`.
		 *
		 * @since PHPDOC
		 * @Override
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


		// @Implements
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
		 * Returns a new action object built using the information described in $action.
		 *
		 * @param object $json a JSON action as used in the admin pages of our plugin.
		 *
		 * @return NelioABFormSubmissionAction the new action containing all the information in `$action`.
		 *
		 * @since PHPDOC
		 * @Override
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

