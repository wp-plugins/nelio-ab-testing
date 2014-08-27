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

		protected $wp_pages;
		protected $wp_posts;
		protected $basic_info;

		protected $alternatives;
		protected $goals;

		protected $form_name;

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

		public function set_basic_info( $id, $name, $description ) {
			$this->basic_info['id'] = $id;
			$this->basic_info['name'] = $name;
			$this->basic_info['description'] = $description;
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
				<script type="text/javascript" src="<?php echo nelioab_admin_asset_link( '/js/tabbed-experiment-setup.js' ); ?>"></script>
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

		private function print_page_post_action( $text, $options ) {
			$direct  = '<select class="direct">';
			$direct .= ' <option value="1">' . __( 'directly', 'nelioab' ) . '</option>';
			$direct .= ' <option value="0">' . __( 'directly or indirectly', 'nelioab' ) . '</option>';
			$direct .= '</select>';
			printf( $text, $direct, $options );
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
			<div class="new-action-templates">
				<?php require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' ); ?>
				<div class="new-page-action action page" style="display:none;">
					<?php
						$options  = '<select class="page">';
						$options .= NelioABWpHelper::get_selector_for_list_of_posts( $this->wp_pages );
						$options .= '</select>';
						$this->print_page_post_action(
							__( 'A visitor %s accesses page %s', 'nelioab' ),
							$options ); ?>
					<a href="javascript:;" class="delete"><?php _e( 'Delete' ); ?></a>
				</div>
				<div class="new-post-action action post" style="display:none;">
					<?php
						$options  = '<select class="post">';
						$options .= NelioABWpHelper::get_selector_for_list_of_posts( $this->wp_posts );
						$options .= '</select>';
						$this->print_page_post_action(
							__( 'A visitor %s accesses post %s', 'nelioab' ),
							$options ); ?>
					<a href="javascript:;" class="delete"><?php _e( 'Delete' ); ?></a>
				</div>
				<div class="new-external-page-action action external-page" style="display:none;">
					<?php
						$name = sprintf(
							'<input type="text" class="name" placeholder="%s" style="max-width:120px;">',
							__( 'Name', 'nelioab' ) );

						$url = sprintf(
							'<input type="text" class="url" placeholder="%s" style="max-width:200px;">',
							__( 'URL', 'nelioab' ) );

						printf(
							__( 'A visitor accesses external page %s with URL is %s', 'nelioab' ),
							$name, $url );
					?>
					<a href="javascript:;" class="delete"><?php _e( 'Delete' ); ?></a>
				</div>

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
							esc_html( __( 'A visitor accesses an external page specified using its URL', 'nelioab' ) )
						);
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

		public function set_wp_posts( $wp_posts ) {
			$this->wp_posts = $wp_posts;
		}

		public function set_wp_pages( $wp_pages ) {
			$this->wp_pages = $wp_pages;
		}

	}//NelioABAltExpPage

}

