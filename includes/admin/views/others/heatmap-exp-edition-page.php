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


if ( !class_exists( 'NelioABHeatmapExpEditionPage' ) ) {

	include_once( NELIOAB_MODELS_DIR . '/account-settings.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );
	class NelioABHeatmapExpEditionPage extends NelioABAdminAjaxPage {

		protected $exp_id;
		protected $exp_name;
		protected $exp_descr;

		protected $post_id;
		protected $wp_pages;
		protected $wp_posts;
		protected $form_name;

		protected $show_latest_posts;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_form_name( 'nelioab_edit_heatmap_exp_form' );
		}

		public function get_form_name() {
			return $this->form_name;
		}

		public function set_form_name( $form_name ) {
			$this->form_name = $form_name;
		}

		public function set_experiment_id( $exp_id ) {
			$this->exp_id = $exp_id;
		}

		public function set_experiment_name( $exp_name ) {
			$this->exp_name = $exp_name;
		}

		public function set_experiment_descr( $exp_descr ) {
			$this->exp_descr = $exp_descr;
		}

		public function show_latest_posts_option( $latest_posts = true ) {
			$this->show_latest_posts = $latest_posts;
		}

		public function set_wp_pages( $wp_pages ) {
			$this->wp_pages = $wp_pages;
		}

		public function set_wp_posts( $wp_posts ) {
			$this->wp_posts = $wp_posts;
		}

		public function set_post_id( $post_id ) {
			if ( $post_id )
				$this->post_id = $post_id;
			else
				$this->post_id = -1;
		}

		protected function do_render() { ?>
			<form id="<?php echo $this->get_form_name(); ?>" method="post">
				<input type="hidden" name="<?php echo $this->get_form_name(); ?>" value="true" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo NelioABExperiment::HEATMAP_EXP; ?>" />
				<input type="hidden" name="action" id="action" value="none" />
				<input type="hidden" name="exp_id" id="exp_id" value="<?php echo $this->exp_id; ?>" />
				<?php
				$this->make_section(
					__( 'Basic Information', 'nelioab' ),
					$this->get_basic_info_elements() ); ?>
			</form>
			<?php
			$this->print_validator_js();
		}

		protected function print_validator_js() { ?>
			<script type="text/javascript">
			jQuery(document).ready(function() {
				var $ = jQuery;
				// Global form
				checkSubmit(jQuery);
				$("#exp_name").bind( "change paste keyup", function() { checkSubmit(jQuery); } );
			});

			function checkSubmit($) {
				if ( validateGeneral($) )
					$(".actions > .button-primary").removeClass("button-primary-disabled");
				else
					$(".actions > .button-primary").addClass("button-primary-disabled");
			}

			function validateGeneral($) {

				try {
					aux = $("#exp_name").attr("value");
					if ( aux == undefined )
						return false;
					aux = $.trim( aux );
					if ( aux.length == 0 )
						return false;
				} catch ( e ) {}

				return true;
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
					'label'     => 'Name',
					'id'        => 'exp_name',
					'callback'  => array( &$this, 'print_name_field' ),
					'mandatory' => true ),
				array (
					'label'     => 'Description',
					'id'        => 'exp_descr',
					'callback'  => array( &$this, 'print_descr_field' ) ),
				array (
					'label'     => $post_label,
					'id'        => 'exp_original',
					'callback'  => array ( &$this, 'print_post_field' ),
					'mandatory' => true ),
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
				class="regular-text" value="<?php echo $this->exp_name; ?>" />
			<span class="description" style="display:block;"><?php
				_e( 'Set a meaningful, descriptive name for the experiment.', 'nelioab' );
			?> <small><a href="http://wp-abtesting.com/faqs/why-do-i-need-to-name-an-experiment" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}

		public function print_descr_field() { ?>
			<textarea id="exp_descr" style="width:300px;" maxlength="450"
				name="exp_descr" cols="45" rows="3"><?php echo $this->exp_descr; ?></textarea>
			<span class="description" style="display:block;"><?php
					_e( 'In a few words, describe what this experiment aims to test.', 'nelioab' );
			?> <small><a href="http://wp-abtesting.com/faqs/what-is-the-description-of-an-experiment-used-for" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}


		public function print_post_field() { ?>
			<select id="exp_post_id" style="width:300px;"
				name="exp_post_id" class="required" value="<?php echo $this->post_id; ?>">
				<option id="select_goal_label" value="-1"><?php _e( 'Select a page or post...', 'nelioab' ); ?></option>
				<?php
				$selected = 'selected="selected"';
				if ( $this->show_latest_posts ) { ?>
					<optgroup id="latest-posts" label="<?php _e( 'Dynamic Front Page', 'nelioab' ); ?>">
						<option
							id="goal-0"
							<?php if ( NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS == $this->post_id )
								echo $selected; ?>
							value="<?php echo NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS; ?>"
							title="<?php _e( 'Your latest posts' ); ?>"><?php
							_e( 'Your latest posts' ); ?></option>
					</optgroup><?php
				}
				$counter  = 1;
				if ( count( $this->wp_pages ) > 0 ) { ?>
					<optgroup id="page-options" label="<?php _e( 'WordPress Pages' ); ?>">
					<?php
					foreach ( $this->wp_pages as $p ) {
						$title = $p->post_title;
						$short = $title;
						if ( strlen( $short ) > 50 )
							$short = substr( $short, 0, 50 ) . '...';
						$title = str_replace( '"', '\'\'', $title ); ?>
						<option
							id="goal-<?php echo $counter; ++$counter; ?>"
							<?php if ( $p->ID == $this->post_id ) echo $selected; ?>
							value="<?php echo $p->ID; ?>"
							title="<?php echo $title; ?>"><?php
							echo $short; ?></option><?php
					} ?>
					</optgroup><?php
				}

				if ( count( $this->wp_posts ) > 0 ) { ?>
					<optgroup id="post-options" label="<?php _e( 'WordPress Posts' ); ?>"><?php
					foreach ( $this->wp_posts as $p ) {
						$title = $p->post_title;
						$short = $title;
						if ( strlen( $short ) > 50 )
							$short = substr( $short, 0, 50 ) . '...';
						$title = str_replace( '"', '\'\'', $title ); ?>
						<option
							id="goal-<?php echo $counter; ++$counter; ?>"
							<?php if ( $p->ID == $this->post_id ) echo $selected; ?>
							value="<?php echo $p->ID; ?>"
							title="<?php echo $title; ?>"><?php
							echo $short; ?></option><?php
					} ?>
					</optgroup><?php
				} ?>
			</select><?php
		}

	}//NelioABHeatmapExpEditionPage

}

?>
