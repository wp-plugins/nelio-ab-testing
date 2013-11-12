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

			<script>
				function add_goal() {
					id = jQuery("#goal_options").attr('value');
					if ( id == -1 )
						return;
					elem = jQuery("#goal_options option[value='" + id + "']");
					title = elem.attr('title');
					short_title = elem.text();
					aux = "<li style=\"margin-bottom:0em;\" id=\"active_goal-" + id + "\">" +
					" - " + short_title + " " +
					"<small><a style=\"text-decoration:none;margin-left:0.5em;color:#aa0000;\" href=\"#\" onClick=" +
					"\"javascript:remove_goal( " + id + " );\">[<u>" +
					"<?php _e ( 'remove', 'nelioab' ); ?>" +
					"</u>]</a></small>" +
					"<input type=\"hidden\" name=\"exp_goal[]\" value=\"" + id + "\" />" +
					"</li>";
					jQuery("#no_active_goals").hide();
					jQuery("#active_goals").append(aux);
					remove_option_for_addition(id);
				}

				function remove_option_for_addition( id ) {
					var element = jQuery( '#goal_options option[value="' + id + '"]' );
					par_id = element.parent().attr('id');
					element = element.detach();
					jQuery("#aux-" + par_id ).append(element);
					jQuery("#active_goals").trigger('NelioABGoalsChanged');
				}

				function remove_goal( id ) {
					jQuery("#active_goal-" + id).fadeOut(300);
					jQuery("#active_goal-" + id).delay(300, function() {
						$(this).remove();
						if ( is_there_one_goal_at_least() )
							jQuery("#no_active_goals").hide();
						else
							jQuery("#no_active_goals").show();
					});
					var element = jQuery( '#aux_goal_options option[value="' + id + '"]' );
					make_option_available_again(element);
				}

				function make_option_available_again(element) {
					par_id  = element.parent().attr('id').replace('aux-', '');
					element = element.detach();
					options = jQuery("#" + par_id ).find( "option" );
					if ( options.length == 0 ) {
						jQuery("#" + par_id ).append(element);
					}
					else {
						mypos = element.attr('id').split('-')[1];
						options.each( function() {
							if ( element == undefined)
								return;
							pos = jQuery(this).attr('id').split('-')[1];
							if ( mypos < pos ) {
								jQuery(this).before(element);
								element = undefined;
							}
						});
						if ( element != undefined )
							jQuery("#" + par_id ).append(element);
					}
					jQuery("#select_goal_label").attr("selected",true);
					jQuery("#active_goals").trigger('NelioABGoalsChanged');
				}

				function is_there_one_goal_at_least() {
					return ( jQuery("#active_goals li").length > 1 );
				}

				function show_hidden_goal_options() {
					jQuery("#aux-page-options option").each(function() {
						id = jQuery(this).attr('value');
						if ( jQuery("#active_goals #active_goal-" + id).length == 0 )
							make_option_available_again(jQuery(this));
					});
				}

				jQuery(document).ready(function() {
					$ = jQuery;
					$("#active_goals li").each(function() {
						id = $(this).attr('id').split('-')[1];
						try {
							remove_option_for_addition( id );
						}
						catch( e ) {}
					});
				});

			</script>
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


		public function print_goal_field() {
			$conversion_posts = $this->exp->get_conversion_posts();
			?>
			<select id="goal_options" style="width:240px;" class="required">
				<option id="select_goal_label" value="-1">-- <?php _e( 'Select and add goal', 'nelioab' ); ?> --</option>
				<?php
				$counter = 0;
				if ( count( $this->wp_pages ) > 0 ) {?>
					<optgroup id="page-options" label="<?php _e( 'Pages' ); ?>">
					<?php
					foreach ( $this->wp_pages as $p ) {
						$title = $p->post_title;
						$short = $title;
						if ( strlen( $short ) > 50 )
							$short = substr( $short, 0, 50 ) . '...';
						$title = str_replace( '"', '\'\'', $title );?>
						<option
							id="goal-<?php echo $counter; ++$counter; ?>"
							value="<?php echo $p->ID; ?>"
							title="<?php echo $title; ?>"><?php
							echo $short; ?></option><?php
					}?>
					</optgroup><?php
				}

				if ( count( $this->wp_posts ) > 0 ) {?>
					<optgroup id="post-options" label="<?php _e( 'Posts' ); ?>"><?php
					foreach ( $this->wp_posts as $p ) {
						$title = $p->post_title;
						$short = $title;
						if ( strlen( $short ) > 50 )
							$short = substr( $short, 0, 50 ) . '...';
						$title = str_replace( '"', '\'\'', $title );?>
						<option
							id="goal-<?php echo $counter; ++$counter; ?>"
							value="<?php echo $p->ID; ?>"
							title="<?php echo $title; ?>"><?php
							echo $short; ?></option><?php
					}?>
					</optgroup><?php
				}
				?>
			</select>
			<a class="button" style="width:55px;text-align:center;"
				href="javascript:add_goal()"><?php _e( 'Add', 'nelioab' ); ?></a>
			<select id="aux_goal_options" style="display:none;">
				<optgroup id="aux-page-options"></optgroup>
				<optgroup id="aux-post-options"></optgroup>
			</select>
			<p style="margin-top:2em;margin-bottom:0em;"><b><?php _e( 'Selected Goals:', 'nelioab' ); ?></b></p>
			<ul style="margin-top:5px;" id="active_goals">
			<?php
				echo '<li id="no_active_goals"';
				if ( count( $conversion_posts ) > 0 )
					echo 'style="display:none;"';
				echo '> - ' . __( 'None', 'nelioab' ) . '</li>';

				foreach ( $conversion_posts as $cp ) {
					$post  = get_post( $cp );
					if ( !$post )
						continue;
					$title = $post->post_title;
					$short = $title;
					if ( strlen( $short ) > 50 )
						$short = substr( $short, 0, 50 ) . '...';
					$title = str_replace( '"', '\'\'', $title );
					echo "<li style=\"margin-bottom:0em;\" id=\"active_goal-$cp\">";
					echo " - $short ";
					echo "<small><a style=\"text-decoration:none;margin-left:0.5em;color:#aa0000;\" href=\"#\" onClick=";
					echo "\"javascript:remove_goal( $cp );\">[<u>";
					_e ( 'remove', 'nelioab' );
					echo "</u>]</a></small>";
					echo "<input type=\"hidden\" name=\"exp_goal[]\" value=\"$cp\" />";
					echo "</li>";
				}
			?>
			</ul>
			<span class="description" style="display:block;"><?php
				_e( 'These are the pages (or posts) you want your users to end up visiting.', 'nelioab' );
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
