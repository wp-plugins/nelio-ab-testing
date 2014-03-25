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

		protected $wp_pages;
		protected $wp_posts;
		protected $force_direct_selector_enabled;
		protected $force_direct;

		protected $encoded_appspot_alternatives;
		protected $encoded_local_alternatives;

		protected $experiment_id;
		protected $experiment_name;
		protected $experiment_descr;

		protected $goal;

		protected $form_name;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->force_direct_selector_enabled = false;
			$this->force_direct = true;
		}

		public function set_experiment_id( $exp_id ) {
			$this->experiment_id = $exp_id;
		}

		public function set_experiment_name( $exp_name ) {
			$this->experiment_name = $exp_name;
		}

		public function set_experiment_descr( $exp_descr ) {
			$this->experiment_descr = $exp_descr;
		}

		public function set_goal( $goal ) {
			$this->goal = $goal;
		}

		public function set_encoded_appspot_alternatives( $encoded_alts ) {
			$this->encoded_appspot_alternatives = $encoded_alts;
		}

		public function set_encoded_local_alternatives( $encoded_alts ) {
			$this->encoded_local_alternatives = $encoded_alts;
		}

		protected function set_form_name( $form_name ) {
			$this->form_name = $form_name;
		}

		public function get_form_name() {
			return $this->form_name;
		}

		public function force_direct( $force_direct ) {
			$this->force_direct = $force_direct;
		}

		abstract protected function get_alt_exp_type();
		abstract protected function get_basic_info_elements();
		abstract protected function print_alternatives();
		abstract protected function print_validator_js();

		protected function do_render() { ?>
			<form id="<?php echo $this->get_form_name(); ?>" method="post">
				<input type="hidden" name="<?php echo $this->get_form_name(); ?>" value="true" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo $this->get_alt_exp_type(); ?>" />
				<input type="hidden" name="action" id="action" value="none" />
				<input type="hidden" name="goal_id" id="goal_id" value="<?php echo $this->goal->get_id(); ?>" />
				<input type="hidden" name="appspot_alternatives" id="appspot_alternatives" value="<?php
					echo $this->encoded_appspot_alternatives;
				?>" />
				<input type="hidden" name="local_alternatives" id="local_alternatives" value="<?php
					echo $this->encoded_local_alternatives;
				?>" />
				<input type="hidden" name="exp_id" id="exp_id" value="<?php echo $this->experiment_id; ?>" />
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

		public function print_name_field() { ?>
			<input name="exp_name" type="text" id="exp_name" maxlength="250"
				class="regular-text" value="<?php echo $this->experiment_name; ?>" />
			<span class="description" style="display:block;"><?php
				_e( 'Set a meaningful, descriptive name for the experiment.', 'nelioab' );
			?> <small><a href="http://wp-abtesting.com/faqs/why-do-i-need-to-name-an-experiment" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}

		public function print_descr_field() { ?>
			<textarea id="exp_descr" style="width:300px;" maxlength="450"
				name="exp_descr" cols="45" rows="3"><?php echo $this->experiment_descr; ?></textarea>
			<span class="description" style="display:block;"><?php
					_e( 'In a few words, describe what this experiment aims to test.', 'nelioab' );
			?> <small><a href="http://wp-abtesting.com/faqs/what-is-the-description-of-an-experiment-used-for" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}


		public function print_goal_field() { ?>
			<script>

				// FUNCTIONS TO ADD A WORDPRESS GOAL

				function add_goal() {
					var id = jQuery("#goal_options").attr('value');
					if ( id == -1 )
						return;
					if ( id == "external-page" ) {
						add_external_goal();
					}
					else {
						add_wordpress_goal();
					}
				}

				function add_wordpress_goal() {
					var post_id = jQuery("#goal_options").attr('value');
					var elem = jQuery("#goal_options option[value='" + post_id + "']");
					var title = elem.attr('title');
					var short_title = elem.text();
					var aux = "<input class=\"wordpress-goal\" type=\"hidden\" name=\"exp_goal[]\" value=\"" + post_id + "\" />";
					aux = create_goal_item( title, short_title, aux );

					jQuery("#no_active_goals").hide();
					jQuery("#active_goals").append(aux);
					remove_option_for_addition(post_id);
					jQuery("#active_goals").trigger('NelioABGoalsChanged');
				}

				function create_goal_item( title, short_title, hidden ) {
					var id = compute_goal_item_id();
					var aux = "\n";
					aux += "<li style=\"margin-bottom:4px;line-height:1.5em;\" id=\"active_goal-" + id + "\">\n";
					aux += "   <span title=\"" + title + "\">" + short_title + "</span>\n";
					aux += "   <small>\n";
					aux += "   <a class=\"remove-goal-link\"\n";
					aux += "      style=\"text-decoration:none;margin-left:0.5em;color:#aa0000;\"\n";
					aux += "      href=\"#\"\n";
					aux += "      onClick=\"javascript:remove_goal_and_make_it_available_again( " + id + " );\">\n";
					aux += "         [<u><?php echo __( 'remove', 'nelioab' ); ?></u>]";
					aux += "   </a>\n";
					aux += "   </small>\n";
					aux += hidden;
					aux += "\n</li>\n";
					return aux;
				}

				function compute_goal_item_id() {
					var id = 1;
					jQuery("#active_goals li").each(function() {
						var aux = jQuery(this).attr('id');
						if ( aux.indexOf("active_goal-") != 0 )
							return;
						aux = aux.split("-");
						if ( aux.length <= 1 )
							return;
						aux = parseInt( aux[1] );
						if ( aux >= id )
							id = aux + 1;
					});
					return id;
				}


				// FUNCTIONS TO ADD EXTERNAL GOALS

				function add_external_goal() {
					jQuery("#goal_options").attr( 'value', -1 );
					jQuery("#goal_options_default_selector").hide();
					jQuery(".remove-goal-link").hide();
					jQuery("#external-page-form").show();
				}

				function cancel_external_goal() {
					jQuery("#goal_options_default_selector").show();
					jQuery(".remove-goal-link").show();
					jQuery("#external-page-form").hide();
				}

				function do_add_external_goal() {
					jQuery("#goal_options_default_selector").show();
					jQuery(".remove-goal-link").show();
					jQuery("#external-page-form").hide();

					var name = jQuery("#external-name").attr("value").replace("\"", "''");
					var url  = jQuery("#external-url").attr("value");
					if ( url.indexOf( 'http://' ) != 0 && url.indexOf( 'https://' ) != 0 )
						url = 'http://' + url;
					jQuery("#external-name").attr("value", "");
					jQuery("#external-url").attr("value", "");
					var value = { name: name, url: url };
					value = encodeURIComponent( JSON.stringify( value ) );

					var shorturl = url;
					if ( shorturl.length > 35 )
						shorturl = shorturl.substr( 0, 35 ) + "...";
					var aux = "";
					aux += "   <span style=\"font-family:courier;font-size:90%;display:block;\">";
					aux += shorturl + "</span>";
					aux += "<input class=\"external-goal\" type=\"hidden\" name=\"exp_goal[]\" value=\"" + value + "\" />";
					aux = create_goal_item( name, name, aux );

					var create_the_goal = true;
					jQuery("input.external-goal").each(function() {
						var existingValue = jQuery(this).attr('value');
						existingValue = JSON.parse( decodeURIComponent( existingValue ) );
						if ( existingValue.url == url ) {
							jQuery(this).attr('value', value);
							var parent = jQuery(this).parent().find("span").first();
							parent.attr( 'title', name );
							parent.text( name );
							create_the_goal = false;
						}
					});

					jQuery("#no_active_goals").hide();
					if ( create_the_goal ) {
						jQuery("#active_goals").append(aux);
						jQuery("#active_goals").trigger('NelioABGoalsChanged');
					}
				}


				// PREVENT ADDING TWO TIMES THE SAME WORDPRESS GOAL

				function remove_option_for_addition( post_id ) {
					var element = jQuery( '#goal_options option[value="' + post_id + '"]' );
					var par_id = element.parent().attr('id');
					var element = element.detach();
					jQuery("#aux-" + par_id ).append(element);
				}


				// GOAL REMOVAL

				function remove_goal( id ) {
					jQuery("#active_goal-" + id).fadeOut(300);
					jQuery("#active_goal-" + id).delay(300, function() {
						jQuery(this).remove();
						jQuery("#active_goals").trigger('NelioABGoalsChanged');
						if ( is_there_one_goal_at_least() )
							jQuery("#no_active_goals").hide();
						else
							jQuery("#no_active_goals").show();
					});
				}

				function remove_goal_and_make_it_available_again( id ) {
					remove_goal(id);
					var hidden_element = jQuery("#active_goal-" + id + " input").first();
					if ( hidden_element.attr('class') == 'wordpress-goal' ) {
						var post_id = hidden_element.attr('value');
						var element = jQuery( '#aux_goal_options option[value=' + post_id + ']' );
						make_option_available_again(element);
					}
				}

				function make_option_available_again(element) {
					var par_id  = element.parent().attr('id').replace('aux-', '');
					var element = element.detach();
					var options = jQuery("#" + par_id ).find( "option" );
					if ( options.length == 0 ) {
						jQuery("#" + par_id ).append(element);
					}
					else {
						var mypos = element.attr('id').split('-')[1];
						options.each( function() {
							if ( element == undefined)
								return;
							var pos = jQuery(this).attr('id').split('-')[1];
							if ( mypos < pos ) {
								jQuery(this).before(element);
								element = undefined;
							}
						});
						if ( element != undefined )
							jQuery("#" + par_id ).append(element);
					}
					jQuery("#goal_options").attr('value', -1);
					jQuery("#select_goal_label").attr("selected", true);
				}


				// HELPING OPERATIONS

				function is_there_one_goal_at_least() {
					return ( jQuery("#active_goals li").length > 1 );
				}

				jQuery(document).ready(function() {
					jQuery("#active_goals input.wordpress-goal").each(function() {
						id = jQuery(this).attr('value');
						try {
							remove_option_for_addition( id );
						}
						catch( e ) {}
					});
				});

			</script>
			<div id="goal_options_default_selector">
				<select id="goal_options" style="width:240px !important;" class="required">
					<option id="select_goal_label" value="-1"><?php _e( 'Select and add goal...', 'nelioab' ); ?></option>
					<optgroup label="<?php _e( 'External Page' ); ?>">
						<option id="external-page" value="external-page"><?php
							_e( 'Select this option to add an external page', 'nelioab' );
						?></option>
					</optgroup>
					<?php
					$counter = 0;
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
								value="<?php echo $p->ID; ?>"
								title="<?php echo $title; ?>"><?php
								echo $short; ?></option><?php
						} ?>
						</optgroup><?php
					}
					?>
				</select>
				<a class="button" style="width:55px;text-align:center;"
					href="javascript:add_goal()"><?php _e( 'Add', 'nelioab' ); ?></a>
			</div>
			<div id="external-page-form" style="display:none;">
				<div>
				<span style="display:inline-block;width:50px;"><?php _e( 'Name', 'nelioab' ); ?></span>
				<input type="text" id="external-name" style="width:250px;" maxlength="100" /><br />
				<span style="display:inline-block;width:50px;"><?php _e( 'URL', 'nelioab' ); ?></span>
				<input type="text" id="external-url" style="width:250px;" maxlength="400" />
				</div>
				<div style="width:300px;backgroud-color:red;text-align:right;margin-top:10px;">
					<a class="button" style="text-align:center;"
						href="javascript:do_add_external_goal()"><?php _e( 'Add', 'nelioab' ); ?></a>
					<a class="button" style="text-align:center;"
						href="javascript:cancel_external_goal()"><?php _e( 'Cancel', 'nelioab' ); ?></a>
				</div>
			</div>

			<select id="aux_goal_options" style="display:none;">
				<optgroup id="aux-page-options"></optgroup>
				<optgroup id="aux-post-options"></optgroup>
			</select>
			<p style="margin-top:1em;margin-bottom:0em;">
				<span style="width:18px;height:18px;margin:0em;display:inline-block;">
					<span id="goals-warning"
						style="display:none;margin-right:8px;">
						<a href="http://wp-abtesting.com/inquiry-subscription-plans/"><span
							class="nelioab-inline-asset nelioab-warning"
							title="<?php
								_e( 'In order to use experiments with more than one goal, you first have to upgrade your subscription plan.', 'nelioab' );
							?>"></span></a>
					</span>
				</span>
				<b><?php _e( 'Selected Goals:', 'nelioab' ); ?></b>
			</p>
			<ul id="active_goals" style="margin-top:5px;list-style-type:disc;margin-left:3em;">
			<?php
			// We now retrieve the internal goals
			$conversion_pages = $this->goal->get_pages();
			echo '<li id="no_active_goals"';
			if ( count( $conversion_pages ) > 0 )
				echo 'style="display:none;"';
			echo '> ' . __( 'None', 'nelioab' ) . '</li>';

			$counter = 0;
			foreach ( $conversion_pages as $page ) {
				++$counter;
				if ( $page->is_internal() ) {
					$post = get_post( $page->get_reference() );
					if ( !$post )
						continue;
					$title = $post->post_title;
				}
				else {
					$title = $page->get_title();
				}
				$short = $title;
				if ( strlen( $short ) > 50 )
					$short = substr( $short, 0, 50 ) . '...';
				$title = str_replace( '"', '\'\'', $title );
				echo "<li style=\"margin-bottom:4px;line-height:1.5em;\" id=\"active_goal-$counter\">\n";
				echo "   <span title=\"$title\">$short</span>\n";
				echo "   <small>\n";
				echo "   <a class=\"remove-goal-link\"\n";
				echo "      style=\"text-decoration:none;margin-left:0.5em;color:#aa0000;\"\n";
				echo "      href=\"#\"\n";
				echo "      onClick=\"javascript:remove_goal_and_make_it_available_again( $counter );\">\n";
				echo "         [<u>" . __( 'remove', 'nelioab' ) . "</u>]";
				echo "   </a>\n";
				echo "   </small>\n";
				if ( $page->is_internal() ) {
					$ref = $page->get_reference();
					echo "<input class=\"wordpress-goal\" type=\"hidden\" name=\"exp_goal[]\" value=\"$ref\" />";
				}
				else {
					$obj       = new stdClass();
					$obj->name = $page->get_title();
					$obj->url  = $page->get_reference();
					$value     = urlencode( json_encode( $obj ) );
					$short_url = $page->get_reference();
					if ( strlen( $short_url ) > 35 )
						$short_url = substr( $short_url, 0, 35 ) . '...';
					echo "   <span style=\"font-family:courier;font-size:90%;display:block;\">$short_url</span>\n";
					echo "<input class=\"external-goal\" type=\"hidden\" name=\"exp_goal[]\" value=\"$value\" />";
				}
				echo "</li>";
			}
			?>
			</ul>
			<span class="description" style="display:block;"><?php
				_e( 'These are the pages (or posts) you want your users to end up visiting.', 'nelioab' );
			?> <small><a href="http://wp-abtesting.com/faqs/what-is-the-goal-pagepost-of-an-experiment" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span>
			<?php
			if ( $this->force_direct_selector_enabled ) { ?>
				<div style="margin-top:1em;">
				<input name="is_force_direct_submitted" value="true" type="hidden" />
				<input name="force_direct" type="checkbox" <?php
						if ( $this->force_direct ) echo 'checked="checked"';
				?>>&nbsp;<?php
					_e( 'Count conversions on direct navigations only.', 'nelioab' );
				?> <small><i><a href="http://wp-abtesting.com/faqs/what-is-the-difference-between-direct-and-indirect-navigations-to-a-goal-page/" target="_blank"><?php
				_e( 'Help', 'nelioab' );
				?></a></i></small></input>
				</div>
			<?php
			}
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
