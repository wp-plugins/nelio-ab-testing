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


if ( !class_exists( 'NelioABPostAltExpCreationPage' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );
	abstract class NelioABAltExpPage extends NelioABAdminAjaxPage {

		private $tabs;

		protected $basic_info;

		protected $alternatives;
		protected $goals;

		protected $form_name;

		protected $is_global;
		protected $tests_a_page;

		protected $finalization_mode;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->tabs = array();
			$this->set_icon( 'icon-nelioab' );
			$this->alternatives = array();
			$this->goals = array();
			$this->basic_info = array(
				'id'          => -1,
				'name'        => '',
				'description' => '',
				'otherNames'  => array() );
			$this->is_global = false;
		}

		protected abstract function get_alt_exp_type();
		protected abstract function print_alternatives();
		protected abstract function get_save_experiment_name();
		protected abstract function get_basic_info_elements();

		protected function set_form_name( $form_name ) {
			$this->form_name = $form_name;
		}

		public function add_another_experiment_name( $name ) {
			array_push( $this->basic_info['otherNames'], $name );
		}

		public function get_form_name() {
			return $this->form_name;
		}

		public function set_basic_info( $id, $name, $description, $fin_mode, $fin_value ) {
			$this->basic_info['id'] = $id;
			$this->basic_info['name'] = $name;
			$this->basic_info['description'] = $description;
			$this->basic_info['finalization_mode'] = $fin_mode;
			$this->basic_info['finalization_value'] = $fin_value;
		}

		public function set_alternatives( $alternatives ) {
			$this->alternatives = array();
			foreach ( $alternatives as $alt )
				$this->add_alternative( $alt['id'], $alt['name'] );
		}

		public function add_alternative( $id, $name, $new = false ) {
			array_push( $this->alternatives,
				array(
					'id' => $id,
					'name' => $name,
					'isNew' => $new,
					'wasDeleted' => false,
				)
			);
		}

		public function add_goal( $goal ) {
			array_push( $this->goals, $goal );
		}

		public function add_tab( $id, $name, $callback ) {
			array_push( $this->tabs, array(
					'id'       => $id,
					'name'     => $name,
					'callback' => $callback,
				) );
		}

		protected function do_render() { ?>
			<script type="text/javascript">
				NelioABHomeUrl = "<?php echo home_url(); ?>";
			</script>
			<form id="<?php echo $this->get_form_name(); ?>" method="post" class="nelio-exp-form">
				<input type="hidden" name="nelioab_save_exp_post" value="true" />
				<input type="hidden" name="<?php echo $this->get_form_name(); ?>" value="true" />
				<input type="hidden" name="exp_id" id="exp_id" value="<?php echo $this->basic_info['id']; ?>" />
				<input type="hidden" name="nelioab_exp_type" value="<?php echo $this->get_alt_exp_type(); ?>" />
				<input type="hidden" name="content_to_edit" id="content_to_edit" />
				<input type="hidden" name="other_names" id="other_names" value="<?php
						$aux = $this->basic_info;
						echo rawurlencode( json_encode( $aux['otherNames'] ) );
					?>" />
				<input type="hidden" name="action" id="action" value="none" />

				<script type="text/javascript">var nelioabBasicInfo = <?php
					echo json_encode( $this->basic_info );
				?>;</script>

				<input type="hidden" name="nelioab_alternatives" id="nelioab_alternatives" value="<?php
						echo rawurlencode( json_encode( $this->alternatives ) );
					?>" />

				<input type="hidden" name="nelioab_goals" id="nelioab_goals" value="<?php
						echo rawurlencode( json_encode( $this->goals ) );
					?>" />

				<h3 id="exp-tabs" class="nav-tab-wrapper" style="margin:0em;padding:0em;padding-left:2em;margin-bottom:2em;"><?php
					$active = ' nav-tab-active';
					foreach ( $this->tabs as $tab ) {
						printf( '<span id="tab-%1$s" class="nav-tab%3$s">%2$s</span>',
							$tab['id'], $tab['name'], $active );
						$active = '';
					}
				?></h3>

				<div>
					<div id="set-of-content-blocks"><?php
						$invisible = '';
						foreach( $this->tabs as $tab ) {
							printf( '<div id="content-%s" style="%s" class="alt-exp-content-block">', $tab['id'], $invisible );
								call_user_func( $tab['callback'] );
							echo '</div>';
							$invisible = 'display:none;';
						} ?>
					</div>

					<div id="controllers" style="height:4em;">
						<a href="javascript:;" class="button previous" style="float:left;"><?php
							_e( 'Previous', 'nelioab' ); ?></a>
						<a href="javascript:;" class="button next" style="float:right;"><?php
							_e( 'Next', 'nelioab' ); ?></a>
						<a href="javascript:;" class="button-primary save" style="float:right;display:none;"><?php
							$this->get_save_experiment_name(); ?></a>
					</div>
				</div>
				<?php
				require_once( NELIOAB_UTILS_DIR . '/html-generator.php' );
				NelioABHtmlGenerator::print_unsaved_changes_control( '#controllers .button-primary.save, .row-actions .edit-content' );
				?>
				<script type="text/javascript" src="<?php echo nelioab_admin_asset_link( '/js/tabbed-experiment-setup.min.js' ); ?>"></script>
				<script type="text/javascript" src="<?php echo nelioab_admin_asset_link( '/js/admin-table.min.js' ); ?>"></script>
				<script type="text/javascript" src="<?php echo nelioab_admin_asset_link( '/js/nelioab-alt-table.min.js' ); ?>"></script>
				<script type="text/javascript">NelioABEditExperiment.useTab(jQuery('#exp-tabs .nav-tab-active').attr('id'));</script>
			</form>

			<?php
		}

		public function print_name_field() { ?>
			<input name="exp_name" type="text" id="exp_name" maxlength="250"
				class="regular-text" />
			<span class="description" style="display:block;"><?php
				_e( 'Set a meaningful, descriptive name for the experiment.', 'nelioab' );
			?> <small><a href="http://wp-abtesting.com/faqs/why-do-i-need-to-name-an-experiment" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}

		public function print_descr_field() { ?>
			<textarea id="exp_descr" style="width:280px;" maxlength="450"
				name="exp_descr" cols="45" rows="3"></textarea>
			<span class="description" style="display:block;"><?php
					_e( 'In a few words, describe what this experiment aims to test.', 'nelioab' );
			?> <small><a href="http://wp-abtesting.com/faqs/what-is-the-description-of-an-experiment-used-for" target="_blank"><?php
				_e( 'Help', 'nelioab' );
			?></a></small></span><?php
		}

		public function print_finalization_mode_field() {
			require_once( NELIOAB_UTILS_DIR . '/html-generator.php' );
			NelioABHtmlGenerator::print_finalization_mode_field(
				$this->basic_info['finalization_mode'],
				$this->basic_info['finalization_value'] );
		}

		protected function print_basic_info() {
			$this->make_section(
				__( 'Basic Information', 'nelioab' ),
				$this->get_basic_info_elements() );
		}

		/**
		 * This function prints the list of goals (a set of cards). It also
		 * prepares the "card" template, which is used by the JavaScript code
		 * for adding new cards.
		 *
		 * Each kind of possible goal (the form for adding it) is also printed
		 * by this function.
		 */
		protected function print_goals() { ?>
			<h2><a class="add-new-h2" href="javascript:;" onClick="javascript:NelioABGoalCards.create();"><?php
				_e( 'Add Additional Goal', 'nelioab' );
			?></a></h2>

			<div id="new-action-templates">
				<?php require_once( NELIOAB_UTILS_DIR . '/html-generator.php' ); ?>
				<div class="action page" style="display:none;">
					<?php
						$options = NelioABHtmlGenerator::get_page_searcher( 'new-action-page-searcher', false, array(), false );
						$this->print_post_or_form_action( 'page', $options, !$this->is_global ); ?>
					<a href="javascript:;" class="delete"><?php _e( 'Delete' ); ?></a>
				</div>
				<div class="action post" style="display:none;">
					<?php
						$options = NelioABHtmlGenerator::get_post_searcher( 'new-action-post-searcher', false, array(), false );
						$this->print_post_or_form_action( 'post', $options, !$this->is_global ); ?>
					<a href="javascript:;" class="delete"><?php _e( 'Delete' ); ?></a>
				</div>
				<div class="action external-page" style="display:none;">
					<?php
						$name = sprintf(
							'<input type="text" class="name" placeholder="%s" style="max-width:120px;">',
							__( 'Name', 'nelioab' ) );

						$url = sprintf(
							'<input type="text" class="url" placeholder="%s" style="max-width:200px;">',
							__( 'URL', 'nelioab' ) );

						$options = '<select class="url_mode">';
						$options .= sprintf( '<option value="exact">%s</option>',
							__( 'whose URL is', 'nelioab' ) );
						$options .= sprintf( '<option value="starts-with">%s</option>',
							__( 'whose URL starts with', 'nelioab' ) );
						$options .= sprintf( '<option value="contains">%s</option>',
							__( 'whose URL contains', 'nelioab' ) );
						$options .= sprintf( '<option value="ends-with">%s</option>',
							__( 'whose URL ends with', 'nelioab' ) );
						$options .= '</select>';

						if ( !$this->is_global ) {
							$indirect  = '<select class="direct">';
							$indirect .= ' <option value="1">' . __( 'from the tested page', 'nelioab' ) . '</option>';
							$indirect .= ' <option value="0">' . __( 'from any page', 'nelioab' ) . '</option>';
							$indirect .= '</select>';
							printf(
								__( 'A visitor accesses %4$s the page %1$s, %2$s %3$s', 'nelioab' ),
								$name, $options, $url, $indirect );
						}
						else {
							printf(
								__( 'A visitor accesses the page %1$s, %2$s %3$s', 'nelioab' ),
								$name, $options, $url );
							printf( '<input type="hidden" class="direct" value="0" />' );
						}
					?>
					<a href="javascript:;" class="delete"><?php _e( 'Delete' ); ?></a>
				</div>
				<div class="action form-submit cf7" style="display:none;"><?php
					$options = NelioABHtmlGenerator::get_form_searcher( 'new-cf7-form-searcher', false, array(), false );
					$this->print_post_or_form_action( 'cf7-submit', $options, !$this->is_global ); ?>
					<a href="javascript:;" class="delete"><?php _e( 'Delete' ); ?></a>
				</div>
				<div class="action form-submit gf" style="display:none;"><?php
					$options = NelioABHtmlGenerator::get_form_searcher( 'new-gf-form-searcher', false, array(), false );
					$this->print_post_or_form_action( 'gf-submit', $options, !$this->is_global ); ?>
					<a href="javascript:;" class="delete"><?php _e( 'Delete' ); ?></a>
				</div>
			</div>

			<div style="display:none;">
				<span id="defaultNameForMainGoal" style="display:none;"><?php _e( 'Default', 'nelioab' ); ?></span>
				<?php
					$this->print_beautiful_box(
						'goal-template',
						'<span class="form" style="font-weight:normal;">' .
							'  <input type="text" class="new-name">' .
							'  <a class="button rename">' . __( 'Save' ) . '</a>' .
							'</span>' .
							'<span class="name" style="display:none;">' .
							'  <span class="value">' . __( 'New Goal', 'nelioab' ) . '</span>' .
							'  <small class="isMain" style="display:none;font-weight:normal;">' . __( '[Main Goal]', 'nelioab' ) . '</small><br>' .
							'  <div class="row-actions">' .
							'    <span class="rename"><a href="javascript:;">' . __( 'Rename' ) . '</a></span>' .
							'    <span class="sep">|</span>' .
							'    <span class="delete"><a href="javascript:;">' . __( 'Delete' ) . '</a></span>' .
							'  <div>' .
							'</span>',
							array( $this, 'print_new_card_content' )
						);
				?>
			</div>
			<div id="goal-list"></div>
			<?php
		}

		private function print_post_or_form_action( $type, $options, $indirect ) {

			$do_print = true;

			// INDIRECT selectors for pages and posts
			$p_indirect_real  = '<select class="direct">';
			if ( $this->tests_a_page ) {
				$p_indirect_real .= ' <option value="1">' . __( 'from the tested page', 'nelioab' ) . '</option>';
				$p_indirect_real .= ' <option value="0">' . __( 'from any page', 'nelioab' ) . '</option>';
			}
			else {
				$p_indirect_real .= ' <option value="1">' . __( 'from the tested post', 'nelioab' ) . '</option>';
				$p_indirect_real .= ' <option value="0">' . __( 'from any post', 'nelioab' ) . '</option>';
			}
			$p_indirect_real .= '</select>';
			$p_indirect_hidden = '<input type="hidden" class="direct" value="0" />';

			// INDIRECT selectors (or ANY_PAGE) for forms
			$f_any_real  = '<select class="any-page">';
			if ( $this->tests_a_page ) {
				$f_any_real .= ' <option value="0">' . __( 'from the tested post', 'nelioab' ) . '</option>';
				$f_any_real .= ' <option value="1">' . __( 'from any post', 'nelioab' ) . '</option>';
			}
			else {
				$f_any_real .= ' <option value="0">' . __( 'from the tested post', 'nelioab' ) . '</option>';
				$f_any_real .= ' <option value="1">' . __( 'from any post', 'nelioab' ) . '</option>';
			}
			$f_any_real .= '</select>';
			$f_any_hidden = '<input type="hidden" class="any-page" value="1" />';

			// INDIRECT selector
			$indirect_selector = false;

			switch( $type ) {

				case 'page':
					if ( $indirect ) {
						$text = __( 'A visitor accesses %2$s the page %1$s', 'nelioab' );
						$indirect_selector = $p_indirect_real;
					}
					else {
						$text = __( 'A visitor accesses page %1$s%2$s', 'nelioab' );
						$indirect_selector = $p_indirect_hidden;
					}
					break;

				case 'post':
					if ( $indirect ) {
						$text = __( 'A visitor accesses %2$s the post %1$s', 'nelioab' );
						$indirect_selector = $p_indirect_real;
					}
					else {
						$text = __( 'A visitor accesses post %1$s%2$s', 'nelioab' );
						$indirect_selector = $p_indirect_hidden;
					}
					break;

				case 'cf7-submit':
					if ( $indirect ) {
						$text = __( 'A visitor submits %2$s the Contact Form 7 %1$s', 'nelioab' );
						$indirect_selector = $f_any_real;
					}
					else {
						$text = __( 'A visitor submits the Contact Form 7 %1$s%2$s', 'nelioab' );
						$indirect_selector = $f_any_hidden;
					}
					break;

				case 'gf-submit':
					if ( $indirect ) {
						$text = __( 'A visitor submits %2$s the Gravity Form %1$s', 'nelioab' );
						$indirect_selector = $f_any_real;
					}
					else {
						$text = __( 'A visitor submits the Gravity Form %1$s%2$s', 'nelioab' );
						$indirect_selector = $f_any_hidden;
					}
					break;

				default:
					$do_print = false;
			}

			if ( $do_print ) {
				printf( $text, $options, $indirect_selector );
			}
		}

		/**
		 * This function prints the contents of a new card. It essentially contains
		 * a small text for an empty card (which is hidden by JS when actions are
		 * added) and the list of actions that contribute to the goal this card
		 * represents.
		 */
		public function print_new_card_content() { ?>
			<p><?php
				_e( 'A conversion is counted for this goal whenever any of the following actions occur:', 'nelioab' );
			?></p>
			<div class="empty">
				<em><?php _e( 'You may add one or more actions using the links below.', 'nelioab' ); ?></em>
			</div>
			<div class="actions" style="display:none;">
			</div>
			<div class="new-actions">
				<div class="wrapper">
					<strong><?php _e( 'New Actions', 'nelioab' ); ?></strong><br>
					<small><?php _e( '(Hover over each action for help)', 'nelioab' ); ?></small><br>
					<?php
					printf( '<a class="%1$s" title="%3$s" href="javascript:;">%2$s</a>',
							'post', __( 'Post', 'nelioab' ),
							esc_html( __( 'A visitor accesses a post in your WordPress site', 'nelioab' ) )
						);

					echo ' | ';
					printf( '<a class="%1$s" title="%3$s" href="javascript:;">%2$s</a>',
							'page', __( 'Page', 'nelioab' ),
							esc_html( __( 'A visitor accesses a page in your WordPress site', 'nelioab' ) )
						);

					echo ' | ';
					printf( '<a class="%1$s" title="%3$s" href="javascript:;">%2$s</a>',
							'external-page', __( 'External Page', 'nelioab' ),
							esc_html( __( 'A visitor tries to access an external page (either by clicking a link or by sutbmitting a form) specified using its URL', 'nelioab' ) )
						);

					$cf7 = is_plugin_active( 'contact-form-7/wp-contact-form-7.php' );
					$gf = is_plugin_active( 'gravityforms/gravityforms.php' );
					$cf7_action_lbl = __( 'Form Submit', 'nelioab' );
					$gf_action_lbl = __( 'Form Submit', 'nelioab' );
					if ( $cf7 && $gf ) {
						$cf7_action_lbl = __( 'Contact Form 7', 'nelioab' );
						$gf_action_lbl = __( 'Gravity Form', 'nelioab' );
					}
					if ( $cf7 ) {
						echo ' | ';
						printf( '<a class="%1$s" title="%3$s" href="javascript:;">%2$s</a>',
								'form-submit cf7', $cf7_action_lbl,
								esc_html( __( 'A visitor successfully submits a «Contact Form 7» form', 'nelioab' ) )
							);
					}
					if ( $gf ) {
						echo ' | ';
						printf( '<a class="%1$s" title="%3$s" href="javascript:;">%2$s</a>',
								'form-submit gf', $gf_action_lbl,
								esc_html( __( 'A visitor successfully submits a «Gravity Forms» form', 'nelioab' ) )
							);
					}
					?>
				</div>
			</div>
			<?php
		}

		/**
		 * This function prints a search box that can be used for searching posts
		 * and/or pages in WordPress. The box is useful for selecting original posts,
		 * goals, the source for duplicating content...
		 */
		protected function print_search_box( $id, $visible_value = '', $real_value = false ) {
			if ( !$real_value )
				$real_value = $visible_value;
			echo '<input type="text" id="' . $id . '_search"' .
				' style="width:280px;" maxlength="250"' .
				' class="regular-text" value="' . $visible_value . '" />';
			echo '<input type="hidden" id="' . $id . '" name="' . $id . '"' .
				' value="' . $real_value . '"/>';
		}

	}//NelioABAltExpPage

}

