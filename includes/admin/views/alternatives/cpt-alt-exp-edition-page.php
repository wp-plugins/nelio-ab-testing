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


if ( !class_exists( 'NelioABCptAltExpEditionPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alt-exp-page.php' );
	class NelioABCptAltExpEditionPage extends NelioABAltExpPage {

		protected $alt_type;
		protected $original_id;
		protected $post_types;
		protected $post_type_name;

		public function __construct( $title, $alt_type = NelioABExperiment::CPT_ALT_EXP ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->set_form_name( 'nelioab_edit_ab_cpt_exp_form' );
			$this->alt_type        = $alt_type;
			$this->original_id     = -1;
			$this->post_type_name  = false;

			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			$this->post_types = NelioABWpHelper::get_custom_post_types();

			// Prepare tabs
			$this->add_tab( 'info', __( 'General', 'nelioab' ), array( $this, 'print_basic_info' ) );
			$this->add_tab( 'alts', __( 'Alternatives', 'nelioab' ), array( $this, 'print_alternatives' ) );
			$this->add_tab( 'goals', __( 'Goals', 'nelioab' ), array( $this, 'print_goals' ) );
		}

		public function set_original_id( $id ) {
			$this->original_id = $id;
		}

		public function set_custom_post_type( $type ) {
			$this->post_type_name = $type;
		}

		public function get_alt_exp_type() {
			return NelioABExperiment::CPT_ALT_EXP;
		}

		/**
		 * Overriding default method, because in post experiments we need the ID
		 * of the post, not the ID of the alternative
		 */
		public function set_alternatives( $alternatives ) {
			$this->alternatives = array();
			foreach ( $alternatives as $alt )
				$this->add_alternative( $alt['id'] . ':' . $alt['value'], $alt['name'] );
		}

		protected function get_save_experiment_name() {
			return _e( 'Save', 'nelioab' );
		}

		protected function get_basic_info_elements() {
			$ori_label = __( 'Original Post', 'nelioab' );

			return array(
				array (
					'label'     => __( 'Name', 'nelioab' ),
					'id'        => 'exp_name',
					'callback'  => array( &$this, 'print_name_field' ),
					'mandatory' => true ),
				array (
					'label'     => __( 'Description', 'nelioab' ),
					'id'        => 'exp_descr',
					'callback'  => array( &$this, 'print_descr_field' ) ),
				array (
					'label'     => __( 'Custom Post Type', 'nelioab' ),
					'id'        => 'exp_cpt',
					'callback'  => array( &$this, 'print_cpt_field' ),
					'mandatory' => true ),
				array (
					'label'     => __( 'Original Custom Post', 'nelioab' ),
					'id'        => 'exp_original',
					'callback'  => array( &$this, 'print_ori_field' ),
					'mandatory' => true ),
				array (
					'label'     => __( 'Finalization Mode', 'nelioab' ),
					'id'        => 'exp_finalization_mode',
					'callback'  => array( &$this, 'print_finalization_mode_field' ),
					'min_plan'  => NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN,
					'mandatory' => true ),
			);
		}


		protected function print_cpt_field( $cpt = false ) {
			require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
			$post_types = $this->post_types;

			?>
			<select
				id="custom_post_types"
				name="custom_post_types"
				style="width:100%;max-width:350px;margin-bottom:0.5em;"
			    <?php if ( $this->post_type_name ) { echo 'disabled'; } ?>
				>
			<?php
			foreach ( $post_types as $post_type ) { ?>
				<option
				value="<?php echo $post_type->name; ?>"
				<?php if ( $this->post_type_name == $post_type->name ) echo 'selected="selected"'; ?>
				><?php echo ( $post_type->labels->singular_name ); ?></option><?php
			} ?>
			</select><?php
			foreach ( $post_types as $post_type ) { ?>
				<span class="description cpt-desc description-<?php echo $post_type->name; ?>" style="display:block;"><?php
					echo $post_type->description;
				?></span><?php
			}
		}


		protected function print_ori_field() {
			require_once( NELIOAB_UTILS_DIR . '/html-generator.php' );

			$change = false;
			if ( !$this->post_type_name ) {
				$post_type = reset( $this->post_types );
				$this->post_type_name = $post_type->name;
				$change = true;
			}
			NelioABHtmlGenerator::print_post_searcher_based_on_type( 'exp_original', $this->original_id, 'no-drafts', array( 'search-' . $this->post_type_name ), true, array( $this->post_type_name ) );
			?>
			<a class="button" style="text-align:center;"
				href="javascript:NelioABEditExperiment.previewOriginal()"><?php _e( 'Preview', 'nelioab' ); ?></a>
			<?php
			foreach ( $this->post_types as $post_type ) { ?>
				<span class="description cpt-desc description-<?php echo $post_type->name; ?>" style="display:block;">
					<?php
					printf( __( 'This is the %1$s for which alternatives will be created.', 'nelioab' ), $post_type->name );
					?>
					<small>
						<a href="http://support.nelioabtesting.com/support/solutions/articles/1000129193" target="_blank">
							<?php _e( 'Help', 'nelioab' ); ?>
						</a>
					</small>
				</span>
			<?php
			}
			?>
			<script type="text/javascript">
				(function($) {
					// Functions
					function switch_custom_post_type( value ) {
						$('span.cpt-desc').hide();
						$('span.description-'+value).show();
						NelioABPostSearcher.changeType($('#exp_original'), value);
					}
					// Events
					$('#custom_post_types').on('change', function() {
						switch_custom_post_type( $('#custom_post_types').attr('value') );
					});

					<?php
					if ( $change ) {
					?>
						switch_custom_post_type( '<?php echo $this->post_type_name; ?>' );<?php
					} ?>
					$('span.cpt-desc').hide();
					$('span.description-'+'<?php echo $this->post_type_name; ?>').show();
				})(jQuery);
			</script><?php
		}


		protected function print_alternatives() { ?>
			<h2><?php

				$explanation = __( 'based on an existing custom post', 'nelioab' );

				printf( '<a onClick="javascript:%1$s" class="add-new-h2" href="javascript:;">%2$s</a>',
					'NelioABAltTable.showNewPageOrPostAltForm(jQuery(\'table#alt-table\'), false);',
					__( 'New Alternative <small>(empty)</small>', 'nelioab' )
				);
				printf( '<a onClick="javascript:%1$s" class="add-new-h2" href="javascript:;">%2$s</a>',
					'NelioABAltTable.showNewPageOrPostAltForm(jQuery(\'table#alt-table\'), true);',
					sprintf( __( 'New Alternative <small>(%s)</small>', 'nelioab' ), $explanation)
				);

			?></h2><?php

			$wp_list_table = new NelioABCptAlternativesTable(
				$this->alternatives,
				$this->alt_type,
				$this->post_type_name );
			$wp_list_table->prepare_items();
			$wp_list_table->display();
		}

	}//NelioABCptAltExpEditionPage



	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alternatives-table.php' );
	class NelioABCptAlternativesTable extends NelioABAlternativesTable {

		private $alt_type;
		private $post_type_name;

		function __construct( $items, $alt_type, $post_type_name ) {
			parent::__construct( $items );
			$this->alt_type          = $alt_type;
			$this->post_type_name    = $post_type_name;
		}

		public function column_name( $alt ){

			//Build row actions
			$actions = array(
				'rename'	=> $this->make_quickedit_button( __( 'Rename', 'nelioab' ) ),

				'edit-content'	=> sprintf(
						'<a style="cursor:pointer;" onClick="javascript:' .
							'NelioABAltTable.editContent(jQuery(this).closest(\'tr\'));' .
							'">%s</a>',
						__( 'Save Experiment & Edit Content' ) ),

				'delete'	=> sprintf(
						'<a style="cursor:pointer;" onClick="javascript:' .
							'NelioABAltTable.remove(jQuery(this).closest(\'tr\'));' .
							'">%s</a>',
						__( 'Delete' ) ),
			);

			//Return the title contents
			return sprintf(
				'<span class="row-title alt-name">%1$s</span>%2$s',
				/*%1$s*/ $alt['name'],
				/*%2$s*/ $this->row_actions( $actions )
			);
		}

		public function extra_tablenav( $which ) {
			if ( 'top' == $which ){
				$text = __( 'Please, <b>add one or more</b> alternatives to the Original Post using the buttons above.', 'nelioab' );
				echo $text;
			}
		}

		public function display_rows_or_placeholder() {
			$this->print_new_alt_form();

			$title = __( 'Original Post', 'nelioab' );
			$expl = __( 'The original post can be considered an alternative that has to be tested.', 'nelioab' );
			?>
			<tr><td>
				<span class="row-title"><?php echo $title; ?></span>
				<div class="row-actions"><?php echo $expl; ?></div>
			</td></tr>
			<?php
			parent::display_rows();
		}

		protected function get_inline_edit_title() {
			return __( 'Rename Alternative', 'nelioab' );
		}

		protected function get_inline_name_field_label() {
			return __( 'Name', 'nelioab' );
		}

		protected function print_additional_info_for_new_alt_form() { ?>
			<label class="copying-content" style="padding-top:0.5em;">
				<span class="title"><?php _e( 'Source', 'nelioab' ); ?> </span>
				<span class="input-text-wrap">
					<?php
					require_once( NELIOAB_UTILS_DIR . '/html-generator.php' );
					NelioABHtmlGenerator::print_post_type_searcher( 'based_on', false, 'show-drafts', $this->post_type_name );
					?>
					<span class="description" style="display:block;"><?php _e( 'The selected post\'s content will be duplicated and used by this alternative.', 'nelioab' ); ?></span>
				</span>
			</label><?php
		}

	}

}

