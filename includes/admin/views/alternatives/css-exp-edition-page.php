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


if ( !class_exists( 'NelioABCssExpEditionPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );
	class NelioABCssExpEditionPage extends NelioABAdminAjaxPage {

		protected $exp;
		protected $wp_pages;
		protected $wp_posts;

		private $form_name;
		private $show_new_form;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->set_form_name( 'nelioab_edit_ab_css_exp_form' );
			$this->show_new_form   = false;
		}

		public function show_empty_quickedit_box() {
			$this->show_new_form   = true;
		}

		public function set_experiment( $exp ) {
			$this->exp = $exp;
		}

		protected function set_form_name( $form_name ) {
			$this->form_name = $form_name;
		}

		public function get_form_name() {
			return $this->form_name;
		}

		protected function do_render() {?>
			<form id="<?php echo $this->get_form_name(); ?>" method="post">
				<input type="hidden" name="<?php echo $this->get_form_name(); ?>" value="true" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo NelioABExperiment::CSS_ALT_EXP; ?>" />
				<input type="hidden" name="action" id="action" value="none" />
				<input type="hidden" name="appspot_alternatives" id="appspot_alternatives" value="<?php
					echo $this->exp->encode_appspot_alternatives();
				?>" />
				<input type="hidden" name="local_alternatives" id="local_alternatives" value="<?php
					echo $this->exp->encode_local_alternatives();
				?>" />
				<input type="hidden" name="exp_id" id="exp_id" value="<?php echo $this->exp->get_id(); ?>" />
				<input type="hidden" name="alt_to_remove" id="alt_to_remove" value="" />
				<input type="hidden" name="content_to_edit" id="content_to_edit" value="" />
				<?php

				$this->make_section(
					__( 'Basic Information', 'nelioab' ),
					array(
						array (
							'label'     => 'Name',
							'id'        => 'exp_name',
							'callback'  => array( &$this, 'print_name_field' ),
							'mandatory' => true ),
						array (
							'label'     => 'Description',
							'id'        => 'exp_descr',
							'callback'  => array( &$this, 'print_descr_field' ) ),
					) );

				?>

				<h2 style="padding-top:2em;"><?php

					_e( 'Alternatives', 'nelioab' );
					echo ' ' . $this->make_form_action_link(
						__( 'New Alternative', 'nelioab' ),
						$this->form_name, 'show_empty_quickedit_box'
					);

				?></h2><?php

			$wp_list_table = new NelioABAlternativesTable(
				$this->exp->get_alternatives(),
				$this->get_form_name(),
				$this->show_new_form );
			$wp_list_table->prepare_items();
			$wp_list_table->display();
			?>
			</form>

			<script type="text/javascript">
			jQuery(document).ready(function() {
				var $ = jQuery;

				// Global form
				checkSubmit(jQuery);
				checkNewAlt(jQuery);
				$("#exp_name").bind( "change paste keyup", function() { checkSubmit(jQuery); } );

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


		public function print_name_field() {?>
			<input name="exp_name" type="text" id="exp_name"
				class="regular-text" value="<?php echo $this->exp->get_name(); ?>" />
			<span class="description" style="display:block;"><?php
				_e( 'Set a meaningful, descriptive name for the experiment.', 'nelioab' );
			?> <small><a href="http://wp-abtesting.com/faqs/why-do-i-need-to-name-an-experiment" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}



		public function print_descr_field() {?>
			<textarea id="exp_descr" style="width:300px;"
				name="exp_descr" cols="45" rows="3"><?php echo $this->exp->get_description(); ?></textarea>
			<span class="description" style="display:block;"><?php
					_e( 'In a few words, describe what this experiment aims to test.', 'nelioab' );
			?> <small><a href="http://wp-abtesting.com/faqs/what-is-the-description-of-an-experiment-used-for" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}


		public function set_wp_pages( $wp_pages ) {
			$this->wp_pages = $wp_pages;
		}

		public function set_wp_posts( $wp_posts ) {
			$this->wp_posts = $wp_posts;
		}

	}//NelioABCssExpEditionPage


	require_once( NELIOAB_UTILS_DIR . '/admin-table.php' );
	class NelioABAlternativesTable extends NelioABAdminTable {

		private $form_name;
		private $show_new_form;

		function __construct( $items, $form_name, $show_new_form = false ){
   	   parent::__construct( array(
				'singular'  => __( 'alternative', 'nelioab' ),
				'plural'    => __( 'alternatives', 'nelioab' ),
				'ajax'      => false
			)	);
			$this->set_items( $items );
			$this->form_name         = $form_name;
			$this->show_new_form     = $show_new_form;
		}

		public function get_columns(){
			return array(
				'name'        => __( 'Name', 'nelioab' ),
			);
		}

		public function extra_tablenav( $which ) {
			if ( $which == 'top' ){
				$text = __( 'Please, <b>add one or more</b> alternatives to the experiment ' .
					'using the buttons above. Each alternative is a fragment of CSS code that ' .
					'should modify one (or more) aspects of your site.',
					'nelioab' );
				echo $text;
			}
		}

		public function column_name( $alt ){

			//Build row actions
			if ( $this->show_new_form ) {
				$actions = array( 'none' => '&nbsp;' );
			}
			else {
				$actions = array(
					'rename'	=>
						$this->make_quickedit_button( __( 'Rename', 'nelioab' ) ),
	
					'edit-content'	=> sprintf(
						'<a style="cursor:pointer;" onClick="javascript:' .
							'jQuery(\'#content_to_edit\').attr(\'value\', %s);' .
							'submitAndRedirect(\'%s\')' .
							'">%s</a>',
						$alt->get_value(),
						'edit_alt_content',
						__( 'Save Experiment & Edit Content', 'nelioab' ) ),
	
					'delete'	=> sprintf(
						'<a style="cursor:pointer;" onClick="javascript:' .
							'jQuery(\'#action\').attr(\'value\', \'%s\');' .
							'jQuery(\'#alt_to_remove\').attr(\'value\', %s);' .
							'jQuery(\'#%s\').submit();'.
							'">%s</a>',
						'remove_alternative',
						$alt->get_id(),
						$this->form_name,
						__( 'Delete' ) ),
				);
			}
			
			//Return the title contents
			return sprintf(
				'<span class="row-title alt-name">%1$s</span>%2$s<span class="alt-id" style="display:none;">%3$s</span>',
				/*%1$s*/ $alt->get_name(),
				/*%2$s*/ $this->row_actions( $actions ),
				/*%3$s*/ $alt->get_id()
			);
		}

		// TODO document this operation
		public function inline_edit_form() {?>
			<fieldset class="inline-edit-col-left">
				<div class="inline-edit-col">
					<h4><?php _e( 'Rename Alternative', 'nelioab' ); ?></h4>
					<label>
						<span class="title"><?php _e( 'Name', 'nelioab' ); ?> </span>
						<span class="input-text-wrap"><input type="text" id="qe_alt_name" name="qe_alt_name" class="ptitle" value="" /></span>
					</label>
					<input type="hidden" id="qe_alt_id" name="qe_alt_id" value="" />
				</div>
			</fieldset><?php
		}

		public function inline_edit_form_ok_button() {?>
			<a class="button-primary save alignleft" <?php
				echo $this->make_form_javascript( $this->form_name, 'update_alternative_name' );
				?>><?php _e( 'Update', 'nelioab' ); ?></a><?php
		}
	
		public function print_js_body_for_inline_form() {?>
			name = row.find("span.alt-name").first().html();
			id = row.find("span.alt-id").first().html();
			jQuery("#inline-edit").find("#qe_alt_name").first().attr("value", name);
			jQuery("#inline-edit").find("#qe_alt_id").first().attr("value", id);
			<?php
		}

		// TODO: extract this function to an utility (now copied from admin-page.php)
		// Original Name: make_form_javascript
		private function make_form_javascript( $form_name, $hidden_action ) {
			return sprintf(
				' onClick="javascript:' .
				'jQuery(\'#%1$s > #action\').attr(\'value\', \'%2$s\');' .
				'jQuery(\'#%1$s\').submit();" ',
				$form_name, $hidden_action
			);
		}

		public function display_rows_or_placeholder() {
			if ( $this->show_new_form )
				$this->print_new_alt_form();

			$title = __( 'Original Version', 'nelioab' );

			$expl = __( 'When testing different CSS alternatives, the "Original Version" corresponds to no custom CSS at all.', 'nelioab' );
			?>
			<tr><td>
				<span class="row-title"><?php echo $title; ?></span>
				<div class="row-actions"><?php echo $expl; ?></div>
			</td></tr>
			<?php
			parent::display_rows();
		}

		// TODO document this operation
		public function print_new_alt_form() {?>
			<tr id="new-alt-form" class="inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page" style="display:visible;">
				<td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">

					<fieldset class="inline-edit-col-left">
						<div class="inline-edit-col">
							<h4><?php _e( 'New Alternative Creation', 'nelioab' ); ?></h4>
							<label>
								<span class="title"><?php _e( 'Name', 'nelioab' ); ?> </span>
								<span class="input-text-wrap">
									<input type="text" id="new_alt_name" name="new_alt_name" class="ptitle" value="" style="width:300px;" />
									<span class="description" style="display:block;"><?php
										_e( 'Set a descriptive name for the alternative.', 'nelioab' );
									?> <small><a href="http://wp-abtesting.com/faqs/what-is-the-name-of-an-alternative-used-for" target="_blank"><?php
										_e( 'Help', 'nelioab' );
									?></a></small></span>
								</span>
							</label>
						</div>
					</fieldset>

					<p class="submit inline-edit-save">

						<a class="button-primary save alignleft" <?php
							echo $this->make_form_javascript( $this->form_name, 'add_empty_alt' );
							?> style="margin-right:0.4em;"><?php _e( 'Create', 'nelioab' ); ?></a>

						<a class="button-secondary cancel alignleft" <?php
							echo $this->make_form_javascript( $this->form_name, 'hide_new_alt_box' );
							?>><?php _e( 'Cancel', 'nelioab' ); ?></a>

						<br class="clear" />
					</p>
				</td>
			</tr><?php

		}

		protected function prepare_pagination() {
			$total_items = count( $this->items ) + 1;
			$this->set_pagination_args( array(
				'total_items' => $total_items, //WE have to calculate the total number of items
				'per_page'	=> $total_items, //WE have to determine how many items to show on a page
				'total_pages' => 1
			) );
		}
		
		

	}// NelioABExperimentsTable
}

?>
