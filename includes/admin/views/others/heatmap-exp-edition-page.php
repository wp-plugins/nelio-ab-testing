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


if ( !class_exists( 'NelioABHeatmapExpEditionPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );
	class NelioABHeatmapExpEditionPage extends NelioABAdminAjaxPage {

		protected $basic_info;

		protected $post_id;
		protected $form_name;

		protected $show_latest_posts;
		protected $other_names;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_form_name( 'nelioab_edit_heatmap_exp_form' );
			$this->other_names = array();
		}

		public function set_basic_info( $id, $name, $description, $fin_mode, $fin_value ) {
			$this->basic_info['id'] = $id;
			$this->basic_info['name'] = $name;
			$this->basic_info['description'] = $description;
			$this->basic_info['finalization_mode'] = $fin_mode;
			$this->basic_info['finalization_value'] = $fin_value;
		}

		public function add_another_experiment_name( $name ) {
			array_push( $this->other_names, $name );
		}

		public function get_form_name() {
			return $this->form_name;
		}

		public function set_form_name( $form_name ) {
			$this->form_name = $form_name;
		}

		public function show_latest_posts_option( $latest_posts = true ) {
			$this->show_latest_posts = $latest_posts;
		}

		public function set_post_id( $post_id ) {
			if ( $post_id )
				$this->post_id = $post_id;
			else
				$this->post_id = NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS;
		}

		protected function do_render() { ?>
			<form class="nelio-exp-form" id="<?php echo $this->get_form_name(); ?>" method="post" style="max-width:750px;">
				<input type="hidden" name="nelioab_save_exp_post" value="true" />
				<input type="hidden" name="<?php echo $this->get_form_name(); ?>" value="true" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo NelioABExperiment::HEATMAP_EXP; ?>" />
				<input type="hidden" name="action" id="action" value="none" />
				<input type="hidden" name="exp_id" id="exp_id" value="<?php echo $this->basic_info['id']; ?>" />
				<input type="hidden" name="other_names" id="other_names" value="<?php
					echo rawurlencode( json_encode( $this->other_names ) );
				?>" />
				<?php
				$this->make_section(
					__( 'Basic Information', 'nelioab' ),
					$this->get_basic_info_elements() ); ?>
			</form>
			<?php
			$this->print_validator_js();
			require_once( NELIOAB_UTILS_DIR . '/html-generator.php' );
			NelioABHtmlGenerator::print_unsaved_changes_control( '.actions .nelioab-js-button' );
		}

		protected function print_validator_js() { ?>
			<script type="text/javascript">
				var nelioabBasicInfo = { 'otherNames': <?php echo json_encode( $this->other_names ) ?> };
			</script>
			<script type="text/javascript">
			jQuery(document).ready(function() {
				var $ = jQuery;
				// Global form
				checkSubmit(jQuery);
				$("#exp_name").on( "keyup focusout", function() {
					checkSubmit(jQuery); }
				);
				$("#exp_name").closest("tr").removeClass("error");
			});

			function checkSubmit($) {
				if ( validateGeneral($) )
					$(".actions > .button-primary").removeClass("button-primary-disabled disabled");
				else
					$(".actions > .button-primary").addClass("button-primary-disabled disabled");
			}

			function validateGeneral($) {

				try {
					var allOk = true;
					var aux = $("#exp_name").attr("value");
					if ( aux == undefined )
						allOk = false;
					aux = $.trim( aux );
					if ( aux.length == 0 )
						allOk = false;
					for ( var i = 0; i < nelioabBasicInfo.otherNames.length; ++i ) {
						var otherName = nelioabBasicInfo.otherNames[i];
						if ( otherName.trim() == aux )
							allOk = false;
					}
				} catch ( e ) {}

				if ( !allOk )
					$("#exp_name").closest("tr").addClass("error");
				else
					$("#exp_name").closest("tr").removeClass("error");

				return allOk;
			}

			function submitAndRedirect(action,force) {
				if ( !force ) {
					validateGeneral(jQuery);
					var primaryEnabled = true;
					jQuery(".nelioab-js-button").each(function() {
						if ( jQuery(this).hasClass("button-primary") &&
						     jQuery(this).hasClass("disabled") )
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
					data = jQuery.trim( data );
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

		protected function get_basic_info_elements() {
			$post_label = __( 'Page/Post', 'nelioab' );

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
					'label'     => $post_label,
					'id'        => 'exp_original',
					'callback'  => array ( &$this, 'print_post_field' ),
					'mandatory' => true ),
				array (
					'label'     => __( 'Finalization Mode', 'nelioab' ),
					'id'        => 'exp_finalization_mode',
					'callback'  => array( &$this, 'print_finalization_mode_field' ),
					'min_plan'  => NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN,
					'mandatory' => true ),
			);
		}

		public function print_finalization_mode_field() {
			require_once( NELIOAB_UTILS_DIR . '/html-generator.php' );
			NelioABHtmlGenerator::print_finalization_mode_field(
				$this->basic_info['finalization_mode'],
				$this->basic_info['finalization_value'],
				array(
					NelioABExperiment::FINALIZATION_MANUAL,
					NelioABExperiment::FINALIZATION_AFTER_VIEWS,
					NelioABExperiment::FINALIZATION_AFTER_DATE,
				)
			);
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

		public function print_name_field() { ?>
			<input name="exp_name" type="text" id="exp_name" maxlength="250"
				class="regular-text" value="<?php echo $this->basic_info['name']; ?>" />
			<span class="description" style="display:block;"><?php
				_e( 'Set a meaningful, descriptive name for the experiment.', 'nelioab' );
			?> <small><a href="http://support.nelioabtesting.com/support/solutions/articles/1000129190" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}

		public function print_descr_field() { ?>
			<textarea id="exp_descr" style="width:300px;" maxlength="450"
				name="exp_descr" cols="45" rows="3"><?php echo $this->basic_info['description']; ?></textarea>
			<span class="description" style="display:block;"><?php
					_e( 'In a few words, describe what this experiment aims to test.', 'nelioab' );
			?> <small><a href="http://support.nelioabtesting.com/support/solutions/articles/1000129192" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}


		public function print_post_field() {
			require_once( NELIOAB_UTILS_DIR . '/html-generator.php' );
			NelioABHtmlGenerator::print_full_searcher( 'exp_post_id', $this->post_id, 'show-drafts' );
		}

	}//NelioABHeatmapExpEditionPage

}
