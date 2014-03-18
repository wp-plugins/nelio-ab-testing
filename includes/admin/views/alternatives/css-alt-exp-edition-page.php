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


if ( !class_exists( 'NelioABCssAltExpEditionPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alt-exp-page.php' );
	class NelioABCssAltExpEditionPage extends NelioABAltExpPage {

		protected $alternatives;

		protected $show_new_form;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_form_name( 'nelioab_edit_ab_css_exp_form' );
			$this->show_new_form   = false;
			$this->alternatives    = array();
		}

		public function set_alternatives( $alternatives ) {
			$this->alternatives = $alternatives;
		}

		public function get_alt_exp_type() {
			return NelioABExperiment::CSS_ALT_EXP;
		}

		public function show_quickedit_box() {
			$this->show_new_form   = true;
		}

		protected function get_basic_info_elements() {
			return array(
				array (
					'label'     => 'Name',
					'id'        => 'exp_name',
					'callback'  => array( &$this, 'print_name_field' ),
					'mandatory' => true ),
				array (
					'label'     => 'Description',
					'id'        => 'exp_descr',
					'callback'  => array( &$this, 'print_descr_field' ) ),
				array (
					'label'     => __( 'Goal Pages and Posts', 'nelioab' ),
					'id'        => 'exp_goal',
					'callback'  => array ( &$this, 'print_goal_field' ),
					'mandatory' => true ),
			);
		}

		protected function print_alternatives() { ?>
			<h2 style="padding-top:2em;"><?php

				_e( 'Alternatives', 'nelioab' );
				echo ' ' . $this->make_form_action_link(
					__( 'New Alternative CSS', 'nelioab' ),
					$this->form_name, 'show_quickedit_box'
				);

			?></h2><?php

			$wp_list_table = new NelioABCssAlternativesTable(
				$this->alternatives,
				$this->get_form_name(),
				$this->show_new_form );
			$wp_list_table->prepare_items();
			$wp_list_table->display();
		}

		protected function print_validator_js() { ?>
			<script type="text/javascript">
			jQuery(document).ready(function() {
				var $ = jQuery;

				// Global form
				checkSubmit(jQuery);
				checkNewAlt(jQuery);
				$("#exp_name").bind( "change paste keyup", function() { checkSubmit(jQuery); } );
				$("#exp_goal").bind( "change", function() { checkSubmit(jQuery); } );
				$("#active_goals").bind('NelioABGoalsChanged', function() { checkSubmit(jQuery); } );

				// Alternatives
				$("#new_alt_name").bind( "change paste keyup", function() { checkNewAlt(jQuery); } );
			});

			function checkSubmit($) {
				if ( validateGeneral($) )
					$(".actions > .button-primary").removeClass("button-primary-disabled");
				else
					$(".actions > .button-primary").addClass("button-primary-disabled");
			}

			function checkNewAlt($) {
				if ( validateSubmitAlt($) )
					enableSaveAlt();
				else
					disableSaveAlt();
			}

			function validateGeneral($) {

				try {
					aux = $("#exp_name").attr("value");
					if ( aux == undefined )
						return false;
					aux = aux.trim();
					if ( aux.length == 0 )
						return false;
				} catch ( e ) {}

				try {
					if ( jQuery("#new-alt-form").size() == 1 )
						return false;
				} catch ( e ) {}

				if ( !is_there_one_goal_at_least() )
					return false;

				return true;
			}

			function validateSubmitAlt($) {
				try {
					aux = $("#new_alt_name").attr("value");
					if ( aux == undefined )
						return false;
					aux = aux.trim();
					if ( aux.length == 0 )
						return false;
				} catch ( e ) {}

				return true;
			}

			altSaveFunction = null;
			function disableSaveAlt() {
				try {
					jQuery("#new-alt-form a.save").addClass("button-primary-disabled");
					if (altSaveFunction == null)
						altSaveFunction = jQuery("#new-alt-form a.save")[0].onclick;
					jQuery("#new-alt-form a.save")[0].onclick = null;
				} catch (e) {}
			}

			function enableSaveAlt() {
				try {
					jQuery("#new-alt-form a.save").removeClass("button-primary-disabled");
					if (altSaveFunction != null)
						jQuery("#new-alt-form a.save")[0].onclick = altSaveFunction;
				} catch (e) {}
			}

			function validateAlternative() {
			}

			function submitAndRedirect(action,force) {
				if ( !force ) {
					var primaryEnabled = true;
					jQuery(".nelioab-js-button").each(function() {
						if ( jQuery(this).hasClass("button-primary") &&
						     jQuery(this).hasClass("button-primary-disabled") )
						primaryEnabled = false;
					});
					if ( !primaryEnabled )
						return;
				}
				smoothTransitions();
				jQuery("#action").attr('value', action);
				jQuery.post(
					location.href,
					jQuery("#<?php echo $this->form_name; ?>").serialize()
				).success(function(data) {
					data = data.trim();
					if ( data.indexOf("[SUCCESS]") == 0) {
						location.href = data.replace("[SUCCESS]", "");
					}
					else {
						document.open();
						document.write(data);
						document.close();
					}
				});
			}

			</script>
			<?php
		}

		public function print_page_buttons() {
			echo $this->make_js_button(
					_x( 'Update', 'action', 'nelioab' ),
					'javascript:submitAndRedirect(\'validate\',false)',
					false, true
				);
			echo $this->make_js_button(
					_x( 'Cancel', 'nelioab' ),
					'javascript:submitAndRedirect(\'cancel\',true)'
				);
		}

	}//NelioABCssAltExpEditionPage

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alternatives-table.php' );
	class NelioABCssAlternativesTable extends NelioABAlternativesTable {

		private $show_new_form;

		function __construct( $items, $form_name, $show_new_form = false ){
			parent::__construct( $items, $form_name );
			$this->show_new_form = $show_new_form;
		}

		public function extra_tablenav( $which ) {
			if ( $which == 'top' ){
				$text = __( 'Please, <b>add one or more</b> CSS fragments as alternatives.', 'nelioab' );
				echo $text;
			}
		}

		public function display_rows_or_placeholder() {
			if ( $this->show_new_form )
				$this->print_new_alt_form();

			$title = __( 'Original: Default CSS without any additions', 'nelioab' );
			$expl = __( 'This original version of this experiment is not using any alternative CSS at all.', 'nelioab' );
			?>
			<tr><td>
				<span class="row-title"><?php echo $title; ?></span>
				<div class="row-actions"><?php echo $expl; ?></div>
			</td></tr>
			<?php
			parent::display_rows();
		}

		protected function get_edit_code( $alt ){
			return sprintf(
				'<a style="cursor:pointer;" onClick="javascript:' .
					'jQuery(\'#content_to_edit\').attr(\'value\', %s);' .
					'submitAndRedirect(\'%s\',true)' .
					'">%s</a>',
				$alt->get_id(),
				'edit_alt_content',
				__( 'Save Experiment & Edit CSS', 'nelioab' ) );
		}

		protected function hide_quick_actions() {
 			return $this->show_new_form;
		}

		protected function print_additional_info_for_new_alt_form() {
			// Nothing in here
		}

		protected function print_save_button_for_new_alt_form() { ?>
			<a class="button-primary save alignleft" <?php
				echo $this->make_form_javascript( $this->form_name, 'add_alt' );
				?> style="margin-right:0.4em;"><?php _e( 'Create', 'nelioab' ); ?></a>
			<?php
		}

	}// NelioABCssAlternativesTable

}

?>
