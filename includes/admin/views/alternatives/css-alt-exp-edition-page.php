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


if ( !class_exists( 'NelioABCssAltExpEditionPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alt-exp-page.php' );
	class NelioABCssAltExpEditionPage extends NelioABAltExpPage {

		protected $alternatives;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->set_form_name( 'nelioab_edit_ab_css_exp_form' );
			$this->show_new_form   = false;
			$this->alternatives    = array();

			$this->is_global = true;

			// Prepare tabs
			$this->add_tab( 'info', __( 'General', 'nelioab' ), array( $this, 'print_basic_info' ) );
			$this->add_tab( 'alts', __( 'Alternatives', 'nelioab' ), array( $this, 'print_alternatives' ) );
			$this->add_tab( 'goals', __( 'Goals', 'nelioab' ), array( $this, 'print_goals' ) );
		}

		public function set_alternatives( $alternatives ) {
			$this->alternatives = $alternatives;
		}

		public function get_alt_exp_type() {
			return NelioABExperiment::CSS_ALT_EXP;
		}

		protected function get_save_experiment_name() {
			return _e( 'Save', 'nelioab' );
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
			);
		}

		protected function print_alternatives() { ?>
			<h2><?php

				$explanation = __( 'based on an existing page', 'nelioab' );
				if ( $this->alt_type == NelioABExperiment::POST_ALT_EXP )
					$explanation = __( 'based on an existing post', 'nelioab' );

				printf( '<a onClick="javascript:%1$s" class="add-new-h2" href="javascript:;">%2$s</a>',
					'NelioABAltTable.showNewPageOrPostAltForm(jQuery(\'table#alt-table\'), false);',
					__( 'New Alternative CSS', 'nelioab' )
				);

			?></h2><?php

			$wp_list_table = new NelioABPostAlternativesTable(
				$this->alternatives,
				$this->alt_type );
			$wp_list_table->prepare_items();
			$wp_list_table->display();
		}

	}//NelioABCssAltExpEditionPage

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alternatives-table.php' );
	class NelioABCssAlternativesTable extends NelioABAlternativesTable {

		function __construct( $items ){
			parent::__construct( $items );
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

		protected function get_inline_edit_title() {
			return __( 'Rename CSS Alternative', 'nelioab' );
		}

		protected function get_inline_name_field_label() {
			return __( 'Name', 'nelioab' );
		}

	}// NelioABCssAlternativesTable

}

