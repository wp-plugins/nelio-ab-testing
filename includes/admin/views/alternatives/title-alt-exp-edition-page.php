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


if ( !class_exists( 'NelioABTitleAltExpEditionPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alt-exp-page.php' );
	class NelioABTitleAltExpEditionPage extends NelioABAltExpPage {

		protected $original_id;
		protected $alternatives;

		protected $show_new_form;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_form_name( 'nelioab_edit_ab_title_exp_form' );
			$this->show_new_form   = false;
			$this->original_id     = -1;
			$this->alternatives    = array();
			// Enabling selector for indirect goals
			$this->force_direct_selector_enabled = true;
		}

		public function set_original_id( $id ) {
			$this->original_id = $id;
		}

		public function set_alternatives( $alternatives ) {
			$this->alternatives = $alternatives;
		}

		public function get_alt_exp_type() {
			return NelioABExperiment::TITLE_ALT_EXP;
		}

		public function show_title_quickedit_box() {
			$this->show_new_form   = true;
		}

		protected function get_basic_info_elements() {
			$ori_label = __( 'Original Page or Post', 'nelioab' );

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
					'label'     => __( 'Goal Pages and Posts', 'nelioab' ),
					'id'        => 'exp_goal',
					'callback'  => array ( &$this, 'print_goal_field' ),
					'mandatory' => true ),
			);
		}

		protected function print_alternatives() {?>
			<h2 style="padding-top:2em;"><?php

				_e( 'Alternatives', 'nelioab' );
				echo ' ' . $this->make_form_action_link(
					__( 'New Alternative Title', 'nelioab' ),
					$this->form_name, 'show_quickedit_box'
				);

			?></h2><?php

			$wp_list_table = new NelioABTitleAlternativesTable(
				$this->alternatives,
				$this->get_form_name(),
				$this->show_new_form );
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
				$("#active_goals").bind('NelioABGoalsChanged', function() { checkSubmit(jQuery); } );

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

			function oriChanged($) {
				// Make the previous origin available for selection by...
				// ------------------------------------------------------------
				// 1. Simulating they are all available again
				jQuery("#aux_goal_options option").each(function() {
					id = jQuery(this).attr('value');
					if ( jQuery("#active_goals #active_goal-" + id).length == 0 )
						make_option_available_again(jQuery(this));
				});
				// 2. Making "already" goals non selectable
				jQuery("#active_goals input.wordpress-goal").each(function() {
					remove_option_for_addition(jQuery(this).attr('value'));
				});


				// Now, make the current option available not selectable
				// ------------------------------------------------------------
				ori = $("#exp_original").attr("value");
				if (ori == -1)
					return;

				if ( jQuery("#active_goals input[value=" + ori + "]").length != 0 ) {
					var aux = jQuery("#active_goals input[value=" + ori + "]");
					aux = aux.parent().attr('id').split('-')[1];
					remove_goal(aux);
				}
				else {
					remove_option_for_addition(ori);
				}

				checkSubmit(jQuery);
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

			function setOriTitleInAltsTable() {
				try {
					var value = jQuery("#exp_original").attr('value');
					var name = jQuery("#exp_original option[value=" + value + "]").text();
					jQuery("#original-title-row").text(name);
				}
				catch ( e ) {
					jQuery("#original-title-row").text("<?php _e( 'Original Title', 'nelioab' ); ?>");
				}
			}

			jQuery(document).ready(function() {
				jQuery("#exp_original").bind( "change", function() {
					setOriTitleInAltsTable();
				});
				setOriTitleInAltsTable();
			});
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


		public function print_ori_field() {?>
			<select id="exp_original" style="width:300px;"
				name="exp_original" class="required" value="<?php echo $this->original_id; ?>"><?php

			echo '<optgroup label="' . __( 'Pages', 'nelioab' ) . '"';
			foreach ( $this->wp_pages as $p ) {?>
				<option
					value="<?php echo $p->ID; ?>"<?php
						if ( $this->original_id == $p->ID )
							echo ' selected="selected"';
					?>><?php
					$title = $p->post_title;
					if ( strlen( $title ) > 50 )
						$title = substr( $title, 0, 50 ) . '...';
					echo $title; ?></option><?php
			}

			echo '<optgroup label="' . __( 'Posts', 'nelioab' ) . '"';
			foreach ( $this->wp_posts as $p ) {?>
				<option
					value="<?php echo $p->ID; ?>"<?php
						if ( $this->original_id == $p->ID )
							echo ' selected="selected"';
					?>><?php
					$title = $p->post_title;
					if ( strlen( $title ) > 50 )
						$title = substr( $title, 0, 50 ) . '...';
					echo $title; ?></option><?php
			}

			?>
			</select>
			<span class="description" style="display:block;"><?php
				_e( 'This is the page (or post) whose title you want to test.', 'nelioab' );
			?> <small><a href="http://wp-abtesting.com/faqs/what-is-the-original-pagepost-of-an-experiment" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}

	}//NelioABTitleAltExpEditionPage

	require_once( NELIOAB_UTILS_DIR . '/admin-table.php' );
	class NelioABTitleAlternativesTable extends NelioABAdminTable {

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
				'name' => __( 'Name', 'nelioab' ),
			);
		}

		public function extra_tablenav( $which ) {
			if ( $which == 'top' ){
				$text = __( 'Please, <b>add one or more</b> title alternatives.',
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
						$this->make_quickedit_button( __( 'Change Title', 'nelioab' ) ),

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
					<h4><?php _e( 'Change Title', 'nelioab' ); ?></h4>
					<label>
						<span class="title"><?php _e( 'Title', 'nelioab' ); ?> </span>
						<span class="input-text-wrap"><input type="text" id="qe_alt_name" name="qe_alt_name" class="ptitle" value="" maxlength="200" /></span>
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

		public function display_rows_or_placeholder() {
			if ( $this->show_new_form )
				$this->print_new_alt_form();

			$title = __( 'Original Title', 'nelioab' );

			$expl = __( 'The original title can be considered an alternative that has to be tested.', 'nelioab' );
			?>
			<tr><td>
				<span id="original-title-row" class="row-title"><?php echo $title; ?></span>
				<div class="row-actions"><?php echo $expl; ?></div>
			</td></tr>
			<?php
			parent::display_rows();
		}

		protected function print_save_button_for_new_alt_form() {?>
			<a class="button-primary save alignleft" <?php
				echo $this->make_form_javascript( $this->form_name, 'add_alt' );
				?> style="margin-right:0.4em;"><?php _e( 'Create', 'nelioab' ); ?></a>
			<?php
		}

		public function print_new_alt_form() {?>
			<tr id="new-alt-form" class="inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page" style="display:visible;">
				<td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">

					<fieldset class="inline-edit-col-left">
						<div class="inline-edit-col">
							<h4><?php _e( 'New Alternative Title', 'nelioab' ); ?></h4>
							<label>
								<span class="title"><?php _e( 'Title', 'nelioab' ); ?> </span>
								<span class="input-text-wrap">
									<input type="text" id="new_alt_name" name="new_alt_name" class="ptitle" value="" style="width:300px;" maxlength="200" />
									<span class="description" style="display:block;"><?php
										_e( 'Set an alternative title for the original page or post.', 'nelioab' );
									?></a></small></span>
								</span>
							</label>
						</div>
					</fieldset>

					<p class="submit inline-edit-save">

						<?php
						$this->print_save_button_for_new_alt_form();
						?>

						<a class="button-secondary cancel alignleft" <?php
							echo $this->make_form_javascript( $this->form_name, 'hide_new_alt_box' );
							?>><?php _e( 'Cancel', 'nelioab' ); ?></a>

						<br class="clear" />
					</p>
				</td>
			</tr><?php

		}

		// TODO: extract this function to an utility (now copied from admin-page.php)
		// Original Name: make_form_javascript
		protected function make_form_javascript( $form_name, $hidden_action ) {
			return sprintf(
				' onClick="javascript:' .
				'jQuery(\'#%1$s > #action\').attr(\'value\', \'%2$s\');' .
				'jQuery(\'#%1$s\').submit();" ',
				$form_name, $hidden_action
			);
		}

	}// NelioABExperimentsTable

}

?>
