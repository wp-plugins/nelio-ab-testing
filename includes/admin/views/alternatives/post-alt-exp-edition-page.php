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


if ( !class_exists( 'NelioABPostAltExpEditionPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alt-exp-page.php' );
	class NelioABPostAltExpEditionPage extends NelioABAltExpPage {

		protected $alt_type;

		protected $show_new_form;
		protected $copying_content;

		public function __construct( $title, $alt_type = NelioABExperiment::PAGE_ALT_EXP ) {
			parent::__construct( $title );
			$this->set_form_name( 'nelioab_edit_ab_post_exp_form' );
			$this->show_new_form   = false;
			$this->copying_content = false;
			$this->alt_type        = $alt_type;
		}

		public function get_alt_exp_type() {
			require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
			if ( $this->alt_type == NelioABExperiment::PAGE_ALT_EXP )
				return NelioABExperiment::PAGE_ALT_EXP;
			else
				return NelioABExperiment::POST_ALT_EXP;
		}

		public function show_empty_quickedit_box() {
			$this->show_new_form   = true;
			$this->copying_content = false;
		}

		public function show_copying_content_quickedit_box() {
			$this->show_new_form   = true;
			$this->copying_content = true;
		}

		protected function get_basic_info_elements() {
			$ori_label = __( 'Original Page', 'nelioab' );
			if ( $this->alt_type == NelioABExperiment::POST_ALT_EXP )
				$ori_label = __( 'Original Post', 'nelioab' );

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
					'label'     => $ori_label,
					'id'        => 'exp_original',
					'callback'  => array ( &$this, 'print_ori_field' ),
					'mandatory' => true ),
				array (
					'label'     => __( 'Goal Page / Post', 'nelioab' ),
					'id'        => 'exp_goal',
					'callback'  => array ( &$this, 'print_goal_field' ),
					'mandatory' => true ),
			);
		}

		protected function print_alternatives() {?>
			<h2 style="padding-top:2em;"><?php

				$explanation = __( 'based on an existing page', 'nelioab' );
				if ( $this->alt_type == NelioABExperiment::POST_ALT_EXP )
					$explanation = __( 'based on an existing post', 'nelioab' );

				_e( 'Alternatives', 'nelioab' );
				echo ' ' . $this->make_form_action_link(
					__( 'New Alternative <small>(empty)</small>', 'nelioab' ),
					$this->form_name, 'show_empty_quickedit_box'
				);
				echo ' ' . $this->make_form_action_link(
					sprintf( __( 'New Alternative <small>(%s)</small>', 'nelioab' ), $explanation),
					$this->form_name, 'show_copying_content_quickedit_box'
				);

			?></h2><?php

			$wp_list_table = new NelioABPostAlternativesTable(
				$this->exp->get_alternatives(),
				$this->get_form_name(),
				$this->alt_type,
				$this->show_new_form,
				$this->copying_content );
			if ( $this->copying_content ) {
				if ( $this->alt_type == NelioABExperiment::POST_ALT_EXP )
					$wp_list_table->set_wp_posts_or_pages( $this->wp_posts );
				else
					$wp_list_table->set_wp_posts_or_pages( $this->wp_pages );
			}
			$wp_list_table->prepare_items();
			$wp_list_table->display();
		}

		protected function print_validator_js() {?>
			<script type="text/javascript">
			jQuery(document).ready(function() {
				var $ = jQuery;

				// Global form
				oriChanged(jQuery);
				checkSubmit(jQuery);
				checkNewAlt(jQuery);
				$("#exp_name").bind( "change paste keyup", function() { checkSubmit(jQuery); } );
				$("#exp_original").bind( "change", function() { checkSubmit(jQuery); } );
				$("#exp_original").bind( "change", function() { oriChanged(jQuery); } );
				$("#exp_goal").bind( "change", function() { checkSubmit(jQuery); } );

				// Alternatives
				if ($("#exp_original").attr("value") != -1)
					$("#new_alt_postid").attr("value", $("#exp_original").attr("value"));
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
					if ( $("#exp_original").attr("value") == -1 )
						return false;
				} catch ( e ) {}

				try {
					if ( $("#exp_goal").attr("value") == -1 )
						return false;
				} catch ( e ) {}

				try {
					if ( jQuery("#new-alt-form").size() == 1 )
						return false;
				} catch ( e ) {}

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

			function oriChanged($) {
				// Make all options visible
				$("#exp_goal option").each(function() { $(this).show() });

				// If no option from original was selected, quit
				ori = $("#exp_original").attr("value");
				if (ori == -1)
					return;

				// Else, just make sure it is not the goal
				if ( ori == $("#exp_goal").attr("value") )
					$("#exp_goal").attr("value", -1);

				$("#exp_goal option[value=" + ori + "]").hide();
				checkSubmit(jQuery);
			}

			function validateAlternative() {
			}

			function submitAndRedirect(action) {
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
					'javascript:submitAndRedirect(\'validate\')',
					false, true
				);
			echo $this->make_js_button(
					_x( 'Cancel', 'nelioab' ),
					'javascript:submitAndRedirect(\'cancel\')'
				);
		}


		public function print_ori_field() {?>
			<select id="exp_original" style="width:300px;"
				name="exp_original" class="required" value="<?php echo $this->exp->get_conversion_post(); ?>"><?php
			$aux = $this->wp_pages;
			if ( $this->alt_type == NelioABExperiment::POST_ALT_EXP )
				$aux = $this->wp_posts;
			foreach ( $aux as $p ) {?>
				<option
					value="<?php echo $p->ID; ?>" <?php
						if ( $this->exp->get_original() == $p->ID )
							echo 'selected="selected"';
					?>"><?php echo $p->post_title; ?></option><?php
			}
			?>
			</select>
			<span class="description" style="display:block;"><?php
				if ( $this->alt_type == NelioABExperiment::POST_ALT_EXP )
					_e( 'This is the post for which alternatives will be created.', 'nelioab' );
				else
					_e( 'This is the page for which alternatives will be created.', 'nelioab' );
			?> <small><a href="http://wp-abtesting.com/faqs/what-is-the-original-pagepost-of-an-experiment" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}

	}//NelioABPostAltExpEditionPage

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alternatives-table.php' );
	class NelioABPostAlternativesTable extends NelioABAlternativesTable {

		private $show_new_form;
		private $copying_content;
		private $wp_posts_or_pages;
		private $alt_type;

		function __construct( $items, $form_name, $alt_type, $show_new_form = false, $copying_content = false ){
   	   parent::__construct( $items, $form_name );
			$this->alt_type          = $alt_type;
			$this->show_new_form     = $show_new_form;
			$this->copying_content   = $copying_content;
			$this->wp_posts_or_pages = array();
		}

		public function set_wp_posts_or_pages( $wp_posts_or_pages ) {
			$this->wp_posts_or_pages = $wp_posts_or_pages;
		}
		
		public function extra_tablenav( $which ) {
			if ( $which == 'top' ){
				$text = __( 'Please, <b>add one or more</b> alternatives to the Original Page ' .
					'using the buttons above.',
					'nelioab' );
				if ( $this->alt_type == NelioABExperiment::POST_ALT_EXP )
					$text = __( 'Please, <b>add one or more</b> alternatives to the Original Post ' .
						'using the buttons above.',
						'nelioab' );
				echo $text;
			}
		}

		protected function get_edit_code( $alt ){
			return sprintf(
				'<a style="cursor:pointer;" onClick="javascript:' .
					'jQuery(\'#content_to_edit\').attr(\'value\', %s);' .
					'submitAndRedirect(\'%s\')' .
					'">%s</a>',
				$alt->get_value(),
				'edit_alt_content',
				__( 'Save Experiment & Edit Content', 'nelioab' ) );
		}

		protected function hide_quick_actions() {
 			return $this->show_new_form;
		}

		public function display_rows_or_placeholder() {
			if ( $this->show_new_form )
				$this->print_new_alt_form();

			$title = __( 'Original Page', 'nelioab' );
			if ( $this->alt_type == NelioABExperiment::POST_ALT_EXP )
				$title = __( 'Original Post', 'nelioab' );

			$expl = __( 'The original page can be considered an alternative that has to be tested.', 'nelioab' );
			if ( $this->alt_type == NelioABExperiment::POST_ALT_EXP )
				$expl = __( 'The original post can be considered an alternative that has to be tested.', 'nelioab' );
			?>
			<tr><td>
				<span class="row-title"><?php echo $title; ?></span>
				<div class="row-actions"><?php echo $expl; ?></div>
			</td></tr>
			<?php
			parent::display_rows();
		}

		protected function print_additional_info_for_new_alt_form() {
			if ( $this->copying_content ) {?>
				<label style="padding-top:0.5em;">
					<span class="title"><?php _e( 'Source', 'nelioab' ); ?> </span>
					<span class="input-text-wrap">
						<select id="new_alt_postid" name="new_alt_postid" style="width:300px;">
						<?php
						foreach ($this->wp_posts_or_pages as $p) {?>
							<option value="<?php echo $p->ID; ?>"><?php echo $p->post_title; ?></option><?php
						}?>
						</select>
						<span class="description" style="display:block;"><?php _e( 'The selected page\'s content will be duplicated and used by this alternative.', 'nelioab' ); ?></span>
					</span>
				</label><?php
			}
		}

		protected function print_save_button_for_new_alt_form() {?>
			<a class="button-primary save alignleft" <?php
				if ( $this->copying_content )
					echo $this->make_form_javascript( $this->form_name, 'add_alt_copying_content' );
				else
					echo $this->make_form_javascript( $this->form_name, 'add_empty_alt' );
				?> style="margin-right:0.4em;"><?php _e( 'Create', 'nelioab' ); ?></a>
			<?php
		}

	}// NelioABExperimentsTable

}

?>
