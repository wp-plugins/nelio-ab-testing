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


if ( !class_exists( 'NelioABAltExpPage' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );
	abstract class NelioABAltExpPage extends NelioABAdminAjaxPage {

		protected $exp;
		protected $wp_pages;
		protected $wp_posts;

		protected $form_name;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
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

		abstract protected function get_alt_exp_type();
		abstract protected function get_basic_info_elements();
		abstract protected function print_alternatives();
		abstract protected function print_validator_js();

		protected function do_render() {?>
			<form id="<?php echo $this->get_form_name(); ?>" method="post">
				<input type="hidden" name="<?php echo $this->get_form_name(); ?>" value="true" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo $this->get_alt_exp_type(); ?>" />
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
					$this->get_basic_info_elements() );
				?>

			<?php
				$this->print_alternatives();
			?>
			</form>
			<?php

			$this->print_validator_js();
			$this->print_custom_js();
		}

		public function print_custom_js() {
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


		public function print_goal_field() {?>
			<select id="exp_goal" style="width:300px;"
				name="exp_goal" class="required" value="<?php echo $this->exp->get_conversion_post(); ?>">
				<option value="-1">-- <?php _e( 'Select one', 'nelioab' ); ?> --</option>
				<?php
				if ( count( $this->wp_pages ) > 0 ) {?>
					<optgroup label="<?php _e( 'Pages' ); ?>">
					<?php
					foreach ( $this->wp_pages as $p ) {?>
						<option
							value="<?php echo $p->ID; ?>" <?php
								if ( $this->exp->get_conversion_post() == $p->ID )
									echo 'selected="selected"';
							?>"><?php echo $p->post_title; ?></option><?php
					}?>
					</optgroup><?php
				}

				if ( count( $this->wp_posts ) > 0 ) {?>
					<optgroup label="<?php _e( 'Posts' ); ?>"><?php
					foreach ( $this->wp_posts as $p ) {?>
						<option
							value="<?php echo $p->ID; ?>" <?php
								if ( $this->exp->get_conversion_post() == $p->ID )
									echo 'selected="selected"';
							?>"><?php echo $p->post_title; ?></option><?php
					}?>
					</optgroup><?php
				}
				?>
			</select>
			<span class="description" style="display:block;"><?php
				_e( 'This is the page (or post) you want your users to end up visiting.', 'nelioab' );
			?> <small><a href="http://wp-abtesting.com/faqs/what-is-the-goal-pagepost-of-an-experiment" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}

		public function set_wp_pages( $wp_pages ) {
			$this->wp_pages = $wp_pages;
		}

		public function set_wp_posts( $wp_posts ) {
			$this->wp_posts = $wp_posts;
		}

	}//NelioABAltExpPage

}

?>
