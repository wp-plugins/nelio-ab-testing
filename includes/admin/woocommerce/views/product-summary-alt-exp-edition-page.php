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
 * this program.  If not, see <http://www.gnu.org/licenses/>.
 */


if ( !class_exists( 'NelioABProductSummaryAltExpEditionPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alt-exp-page.php' );
	class NelioABProductSummaryAltExpEditionPage extends NelioABAltExpPage {

		protected $original_id;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->set_form_name( 'nelioab_edit_ab_woocommerce_product_summary_exp_form' );
			$this->original_id = -1;

			// Prepare tabs
			$this->add_tab( 'info', __( 'General', 'nelioab' ), array( $this, 'print_basic_info' ) );
			$this->add_tab( 'alts', __( 'Alternatives', 'nelioab' ), array( $this, 'print_alternatives' ) );
		}

		public function set_original_id( $id ) {
			$this->original_id = $id;
		}

		public function get_alt_exp_type() {
			return NelioABExperiment::WC_PRODUCT_SUMMARY_ALT_EXP;
		}

		public function set_alternatives( $alternatives ) {
			$this->alternatives = $alternatives;
		}

		public function add_alternative( $id, $name, $new = false ) {
			// This function should never be called
			die();
		}

		protected function get_save_experiment_name() {
			return _e( 'Save', 'nelioab' );
		}

		protected function get_basic_info_elements() {
			$ori_label = __( 'Original Product', 'nelioab' );

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
					'label'     => $ori_label,
					'id'        => 'exp_original',
					'callback'  => array ( &$this, 'print_ori_field' ),
					'mandatory' => true ),
				array (
					'label'     => __( 'Finalization Mode', 'nelioab' ),
					'id'        => 'exp_finalization_mode',
					'callback'  => array( &$this, 'print_finalization_mode_field' ),
					'min_plan'  => NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN,
					'mandatory' => true ),
			);
		}

		protected function print_ori_field() {
			require_once( NELIOAB_UTILS_DIR . '/html-generator.php' );
			NelioABHtmlGenerator::print_post_searcher_based_on_type( 'exp_original', $this->original_id, 'show-drafts', array(), true, array( 'product' ) );
			?>

			<a class="button" style="text-align:center;"
				href="javascript:NelioABEditExperiment.previewOriginal()"><?php _e( 'Preview', 'nelioab' ); ?></a>
			<span class="description" style="display:block;"><?php
				_e( 'You will create alternative summaries (name, featured image, and description) for this product.', 'nelioab' );
			?></span><?php
		}

		protected function print_alternatives() { ?>
			<h2><?php
				printf( '<a onClick="javascript:%1$s" class="add-new-h2" href="javascript:;">%2$s</a>',
					'NelioABAltTable.showNewHeadlineForm(jQuery(\'table#alt-table\'));' .
					'jQuery(\'table#alt-table .save\').removeClass(\'disabled\');',
					__( 'New Product Summary', 'nelioab' )
				);
			?></h2><?php

			$wp_list_table = new NelioABProductSummaryAlternativesTable( $this->alternatives );
			$wp_list_table->prepare_items();
			$wp_list_table->display();
		}

		protected function print_params_for_required_scripts() { ?>
			NelioABAltTableParams = { noImageSrc: '<?php
				echo nelioab_admin_asset_link( '/images/feat-image-placeholder.png' )
			?>' };
			<?php
		}

		protected function get_required_scripts() {
			return array(
				nelioab_admin_asset_link( '/js/tabbed-experiment-setup.min.js' ),
				nelioab_admin_asset_link( '/js/admin-table.min.js' ),
				nelioab_admin_asset_link( '/js/alt-table.min.js' ),
				nelioab_admin_asset_link( '/js/alt-table-for-headlines.min.js' )
			);
		}

		protected function print_code_for_setup_alternative_table() { ?>
			// PRINTING THE LIST OF ALTERNATIVES
			var alts = JSON.parse( decodeURIComponent(
					jQuery('#nelioab_alternatives').attr('value')
				) );
			var altsTable = NelioABAltTable.getTable();
			if ( altsTable ) {
				NelioABAltTable.init(alts);
				for ( var i = 0; i < NelioABAltTable.alts.length; ++i ) {
					var newRow = NelioABAltTable.createRow(NelioABAltTable.alts[i].id)
					newRow.find('.row-title').first().text(NelioABAltTable.alts[i].name);
					newRow.find('img.feat-image').css('background-image','url(\''+NelioABAltTable.alts[i].imageSrc+'\')');
					newRow.show();
					altsTable.append(newRow);
				}
				jQuery('#exp_original').trigger('change');
				NelioABAdminTable.repaint( NelioABAltTable.getTable() );
			}
			<?php
		}

	}//NelioABProductSummaryAltExpEditionPage

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alternatives-table.php' );
	class NelioABProductSummaryAlternativesTable extends NelioABAlternativesTable {

		private $form_name;

		function __construct( $items ){
			parent::__construct( $items );
		}

		public function get_columns(){
			return array(
				'headline_image' => '',
				'name'  => __( 'Product Summaries', 'nelioab' ),
			);
		}

		public function column_headline_image( $alt ){
			$src = '';
			$size = array( 48, 48 );
			$product_id = 0;
			if ( is_object( $alt ) )
				$product_id = $alt->get_value();
			if ( function_exists( 'uses_nelioefi' ) && uses_nelioefi( $product_id ) ) {
				$src = nelioab_admin_asset_link( '/images/feat-image-placeholder.png' );
			}
			elseif ( has_post_thumbnail( $product_id ) ) {
				$src = wp_get_attachment_image_src( get_post_thumbnail_id( $product_id ), $size );
			}
			else {
				$src = nelioab_admin_asset_link( '/images/feat-image-placeholder.png' );
			}

			$html = sprintf(
				'<img class="feat-image" ' .
					'src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7" ' .
					'style="background:url(\'%s\') no-repeat center center;' .
					'-webkit-background-size:cover;' .
					'-moz-background-size:cover;' .
					'-o-background-size:cover;' .
					'background-size:cover;' .
					'width:%dpx;height:%dpx;" />',
				$src, $size[0], $size[1] );

			return $html;
		}

		public function column_name( $alt ){

			//Build row actions
			$actions = array(
				'edit'	=> $this->make_quickedit_button( __( 'Edit', 'nelioab' ) ),

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
				$text = __( 'Please, <b>add one or more</b> alternative product summaries.',
					'nelioab' );
				echo $text;
			}
		}

		public function display_rows_or_placeholder() {
			$this->print_new_alt_form();

			$title = __( 'Original Product Summary', 'nelioab' );
			$expl = __( 'Your current Product Summary, defined as a combination of a name, a featured image, and a (short) description.', 'nelioab' );
			?>
			<tr><td>
				<?php echo $this->column_headline_image( false ); ?>
			</td>
			<td>
				<span id="original-headline-row" class="row-title"><?php echo $title; ?></span>
				<div class="row-actions"><?php echo $expl; ?></div>
			</td></tr>
			<?php
			parent::display_rows();
		}

		protected function get_inline_edit_title() {
			// Nothing to do here, for we overwrite the method `print_new_alt_form'
		}
		protected function get_inline_name_field_label() {
			// Nothing to do here, for we overwrite the method `print_new_alt_form'
		}

		public function print_new_alt_form() { ?>
			<tr class="new-alt-form inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page alternate" style="display:none;">
				<td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">
					<?php $this->print_form_for_editing_a_headline( __( 'New Alternative Product Summary', 'nelioab' ) ); ?>
					<p class="submit inline-edit-save">
						<a class="button button-primary save alignleft" style="margin-right:0.4em;"
							onClick="javascript:NelioABAltTable.create();"><?php
							_e( 'Create', 'nelioab' );
						?></a>
						<a class="button button-secondary cancel alignleft"
							onClick="javascript:NelioABAltTable.hideNewAltForm(jQuery(this).closest('table'));"><?php
							_e( 'Cancel', 'nelioab' );
						?></a>
						<br class="clear" />
					</p>
				</td>
			</tr><?php
		}

		protected function print_inline_edit_form() { ?>
			<tr class="nelioab-quick-edit-row inline-edit-row inline-edit-row-page inline-edit-page quick-edit-row quick-edit-row-page inline-edit-page" style="display:none;">
				<td colspan="<?php echo $this->get_column_count(); ?>" class="colspanchange">
					<?php $this->print_form_for_editing_a_headline( __( 'Edit Product Summary', 'nelioab' ) ); ?>
					<p class="submit inline-edit-save">
						<a class="button button-primary save alignleft"
							style="margin-left:0.4em;"
							href="javascript:;"
							onClick="javascript:
							jQuery(this).trigger('pre-clicked');
							if (!jQuery(this).hasClass('disabled')) {
								jQuery(this).trigger('clicked');
								NelioABAdminTable.hideInlineEdit(jQuery(this).closest('table.wp-list-table'));
							}"><?php
							_e( 'Update', 'nelioab' ); ?></a>
						<a class="button button-secondary cancel alignleft"
							style="margin-left:0.4em;"
							href="javascript:;"
							onClick="javascript:
							jQuery(this).trigger('pre-clicked');
							if (!jQuery(this).hasClass('disabled')) {
								jQuery(this).trigger('clicked');
								NelioABAdminTable.hideInlineEdit(jQuery(this).closest('table.wp-list-table') );
							}"><?php
							_e( 'Cancel', 'nelioab' ); ?></a>

						<br class="clear" />
					</p>
				</td>
			</tr><?php
		}

		private function print_form_for_editing_a_headline( $title ) { ?>
			<fieldset style="padding:1em;">
				<h4><?php echo $title; ?></h4>
				<div>
					<div class="headline_image_holder">
						<img class="headline_image"
							src="data:image/gif;base64,R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7"
							style="background:url('<?php echo nelioab_admin_asset_link( '/images/feat-image-placeholder.png' ); ?>') no-repeat center center;
								-webkit-background-size:cover;
								-moz-background-size:cover;
								-o-background-size:cover;
								background-size:cover;
								width:100px;height:100px;" />
						<div class="headline_image_hover"><?php _e( 'Change', 'nelioab' ); ?></div>
					</div>
					<div style="width:60%;display:inline-block;vertical-align:top;">
						<span class="input-text-wrap">
							<div class="nelio-sect"><label style="margin:0px;">
								<input type="text" class="headline_title ptitle" value="" placeholder="<?php echo esc_html( __( 'Title', 'nelioab' ) ); ?>" />
							</label></div>
							<textarea class="headline_excerpt" placeholder="<?php echo __( 'Original Description', 'nelioab' ); ?>"></textarea>
						</span>
					</div>
				</div>
			</fieldset>
			<?php
		}

	}// NelioABExperimentsTable

}

