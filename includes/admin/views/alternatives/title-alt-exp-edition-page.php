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


if ( !class_exists( 'NelioABTitleAltExpEditionPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alt-exp-page.php' );
	class NelioABTitleAltExpEditionPage extends NelioABAltExpPage {

		protected $original_id;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->set_form_name( 'nelioab_edit_ab_title_exp_form' );
			$this->original_id     = -1;

			// Prepare tabs
			$this->add_tab( 'info', __( 'General', 'nelioab' ), array( $this, 'print_basic_info' ) );
			$this->add_tab( 'alts', __( 'Alternatives', 'nelioab' ), array( $this, 'print_alternatives' ) );
		}

		public function set_original_id( $id ) {
			$this->original_id = $id;
		}

		public function get_alt_exp_type() {
			return NelioABExperiment::TITLE_ALT_EXP;
		}

		protected function get_save_experiment_name() {
			return _e( 'Save', 'nelioab' );
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
			);
		}

		protected function print_ori_field() { ?>
			<select
				id="exp_original"
				style="width:280px;"
				name="exp_original"
				class="required"
				value="<?php echo $this->original_id; ?>"><?php

			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			echo '<optgroup label="' . __( 'Pages', 'nelioab' ) . '"';
			NelioABWpHelper::print_selector_for_list_of_posts( $this->wp_pages, $this->original_id );

			echo '<optgroup label="' . __( 'Posts', 'nelioab' ) . '"';
			NelioABWpHelper::print_selector_for_list_of_posts( $this->wp_posts, $this->original_id );
			?>
			</select>

			<a class="button" style="text-align:center;"
				href="javascript:NelioABEditExperiment.previewOriginal()"><?php _e( 'Preview', 'nelioab' ); ?></a>
			<span class="description" style="display:block;"><?php
				if ( $this->alt_type == NelioABExperiment::POST_ALT_EXP )
					_e( 'This is the post for which alternatives will be created.', 'nelioab' );
				else
					_e( 'This is the page for which alternatives will be created.', 'nelioab' );
			?> <small><a href="http://wp-abtesting.com/faqs/what-is-the-original-pagepost-of-an-experiment" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}

		protected function print_alternatives() { ?>
			<h2><?php

				$explanation = __( 'based on an existing page', 'nelioab' );
				if ( $this->alt_type == NelioABExperiment::POST_ALT_EXP )
					$explanation = __( 'based on an existing post', 'nelioab' );

				printf( '<a onClick="javascript:%1$s" class="add-new-h2" href="javascript:;">%2$s</a>',
					'NelioABAltTable.showNewPageOrPostAltForm(jQuery(\'table#alt-table\'), false);',
					__( 'New Alternative Title', 'nelioab' )
				);

			?></h2><?php

			$wp_list_table = new NelioABTitleAlternativesTable( $this->alternatives );
			$wp_list_table->prepare_items();
			$wp_list_table->display();
		}

	}//NelioABTitleAltExpEditionPage

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alternatives-table.php' );
	class NelioABTitleAlternativesTable extends NelioABAlternativesTable {

		private $form_name;

		function __construct( $items ){
			parent::__construct( $items );
		}

		public function column_name( $alt ){

			//Build row actions
			$actions = array(
				'rename'	=> $this->make_quickedit_button( __( 'Rename', 'nelioab' ) ),

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
			if ( $which == 'top' ){
				$text = __( 'Please, <b>add one or more</b> title alternatives.',
					'nelioab' );
				echo $text;
			}
		}

		protected function get_inline_edit_title() {
			return __( 'Change Title', 'nelioab' );
		}

		protected function get_inline_name_field_label() {
			return __( 'Title', 'nelioab' );
		}

		protected function get_default_description_for_new_alternative() {
			return __( 'Define a new title for this page or post.', 'nelioab' );
		}

		public function display_rows_or_placeholder() {
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

	}// NelioABExperimentsTable

}

