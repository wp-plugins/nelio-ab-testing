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

if ( !class_exists( 'NelioABMenuExpAdminController' ) ) {

	class NelioABMenuExpAdminController {

		/**
		 *
		 */
		private static $instance;


		/**
		 *
		 */
		public $menus_in_exps;


		/**
		 *
		 */
		public function __construct() {
			global $pagenow;
			$instance = false;

			// Making sure we're accessing the proper page with proper params
			if ( 'nav-menus.php' == $pagenow && isset( $_GET['menu'] ) ) {
				if ( isset( $_GET['nelioab_exp'] ) && isset( $_GET['nelioab_alt'] ) ) {
					$key = $_GET['nelioab_exp'] . $_GET['nelioab_alt'] . $_GET['menu'];
					if ( !isset( $_GET['nelioab_check'] ) || $_GET['nelioab_check'] != md5( $key ) )
						wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
					else
						add_action( 'in_admin_footer',
							array( $this, 'add_code_to_hide_menus_when_editing_menu_exp' ) );
				}
				else {
					$this->begin();
					if ( self::is_menu_in_experiment( $_GET['menu'] ) ) {
						$this->rollback();
						wp_die( __( 'Cheatin&#8217; uh?' ), 403 );
					}
					$this->rollback();
				}
			}

			// Adding some relevant hooks
			add_filter( 'wp_get_nav_menus',
				array( &$this, 'hide_alternative_menus' ), 10, 2 );
			add_filter( 'get_user_option_nav_menu_recently_edited',
				array( &$this, 'hide_recently_edited_menu_if_alternative' ) );
		}


		/**
		 *
		 */
		public static function get_instance() {
			if ( !self::$instance )
				self::$instance = new NelioABMenuExpAdminController();
			return self::$instance;
		}


		/**
		 *
		 */
		public function begin() {
			if ( !$this->menus_in_exps )
				$this->menus_in_exps = get_option( 'nelioab_menus_in_experiments', array() );
		}


		/**
		 *
		 */
		public function commit() {
			if ( $this->menus_in_exps )
				update_option( 'nelioab_menus_in_experiments', $this->menus_in_exps );
		}


		/**
		 *
		 */
		public function rollback() {
			$this->menus_in_exps = false;
		}


		/**
		 *
		 */
		private function validate( $arr, $check = 'check' ) {
			if ( !isset( $arr['nelioab_exp'] ) || empty( $arr['nelioab_exp'] ) )
				return false;
			if ( !isset( $arr['nelioab_alt'] ) || empty( $arr['nelioab_alt'] ) )
				return false;
			if ( 'check' === $check && ( !isset( $arr['nelioab_check'] ) || empty( $arr['nelioab_check'] ) ) )
				return false;

			if ( 'check' === $check )
				return ( hash( 'md5', $arr['nelioab_exp'] . $arr['nelioab_alt'] ) == $arr['nelioab_check'] );
			else
				return true;
		}


		/**
		 *
		 */
		public function hide_alternative_menus( $menus, $args = array() ) {
			$this->begin();
			$aux = array();
			foreach ( $menus as $menu )
				if ( !self::is_menu_in_experiment( $menu ) )
					array_push( $aux, $menu );
			$this->rollback();
			return $aux;
		}


		/**
		 *
		 */
		public function hide_recently_edited_menu_if_alternative( $menu_id ) {
			$this->begin();
			if ( self::is_menu_in_experiment( $menu_id ) )
				$menu_id = 0;
			$this->rollback();
			return $menu_id;
		}


		/**
		 * This function adds just one script in the nav-menus.php page. The script
		 * checks if the user is currently editing a menu exp alternative and, if
		 * it is, it'll hide all menus except the alternative's.
		 */
		public function add_code_to_hide_menus_when_editing_menu_exp() {
			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			$colorscheme = NelioABWpHelper::get_current_colorscheme();
			?>
			<div id="nelioab-edit-alt-menus" class="widgets-holder-wrap" style="margin-bottom:1em;display:none;border: 1px solid #e5e5e5;-webkit-box-shadow: 0 1px 1px rgba(0,0,0,.04);box-shadow: 0 1px 1px rgba(0,0,0,.04);">
				<div class="widgets-sortables ui-sortable">
					<div class="sidebar-name" style="padding:0.2em 1em;background-color:<?php echo $colorscheme['focus']; ?>;">
						<div class="sidebar-name-arrow"><br></div>
						<h3 style="color:<?php echo $colorscheme['foreground']; ?>;"><?php _e( 'Alternative Menu', 'nelioab' ); ?></h3>
					</div>
				</div>

				<div class="content" style="padding:0em 1em 1em 1em;">
					<p><strong><?php _e( 'Go back to...', 'nelioab' ); ?></strong></p>
					<ul style="margin-left:1.5em;">
						<?php if ( isset( $_GET['back_to_edit'] ) ):
							$url = admin_url( 'admin.php?page=nelioab-experiments&action=edit&id=' . $_GET['nelioab_exp'] . '&ctab=tab-alts&exp_type=' . NelioABExperiment::MENU_ALT_EXP );
							?>
							<li><a href="<?php echo $url; ?>"><?php _e( 'Editing this experiment', 'nelioab' ); ?></a></li>
						<?php else:
							$url = admin_url( 'admin.php?page=nelioab-experiments&action=progress&id=' . $_GET['nelioab_exp'] . '&exp_type=' . NelioABExperiment::MENU_ALT_EXP );
							?>
							<li><a href="<?php echo $url; ?>"><?php _e( 'The results of the related experiment', 'nelioab' ); ?></a></li>
						<?php endif; ?>
						<?php $url = admin_url( 'admin.php?page=nelioab-experiments' ); ?>
						<li><a href="<?php echo $url; ?>"><?php _e( 'My list of experiments', 'nelioab' ); ?></a></li>
					</ul>
				</div>

			</div>

			<script type="text/javascript">
			(function($) {
				// Hide Top Area
				$('.nav-tab-wrapper').hide();
				$('.manage-menus').hide();
				$('#nav-menus-frame').css('padding-top', '30px');
				// Hide Menu Box
				$('.menu-name-label').hide();
				$('.menu-settings').hide();
				$('.delete-action').hide();
				// Add Alternative Box
				$content = $('#nelioab-edit-alt-menus .content');
				$('#nelioab-edit-alt-menus .sidebar-name').on('click', function() {
					$content.toggle();
				});
				var $aux = $('#nelioab-edit-alt-menus').detach();
				$('#nav-menu-meta').prepend($aux);
				$aux.show();
				var $msg = $('#message');
				$msg.html($msg.html().replace(/Menu[0-9.\s]+(\s)?/, '<?php echo esc_html( __( 'Alternative Menu', 'nelioab' ) ); ?>\1'));
			})(jQuery);
			</script>
			<?php
		}


		public function create_alternative_menu( $exp, $alt = 'pending' ) {
			$menu_id = wp_create_nav_menu( 'Menu' . microtime() );
			if ( !$menu_id )
				return false;
			$this->link_menu_to_experiment( $menu_id, $exp, $alt );
			return $menu_id;
		}


		public function duplicate_menu_and_create_alternative( $ori, $exp, $alt = 'pending' ) {
			$new_id = $this->create_alternative_menu( $exp, $alt );
			if ( !$new_id )
				return false;
			$ori = intval( $ori );
			$this->copy_nav_menu_items( $ori, $new_id );
			return $new_id;
		}

		public function copy_nav_menu_items( $src, $dest ) {
			$source = wp_get_nav_menu_object( $src );
			$source_items = wp_get_nav_menu_items( $src );

			$dest_prev_items = wp_get_nav_menu_items( $dest );
			foreach ( $dest_prev_items as $menu_item )
				wp_delete_post( $menu_item->ID );

			$rel = array();
			$i = 1;
			foreach ( $source_items as $menu_item ) {
				$args = array(
					'menu-item-db-id'       => $menu_item->db_id,
					'menu-item-object-id'   => $menu_item->object_id,
					'menu-item-object'      => $menu_item->object,
					'menu-item-position'    => $i,
					'menu-item-type'        => $menu_item->type,
					'menu-item-title'       => $menu_item->title,
					'menu-item-url'         => $menu_item->url,
					'menu-item-description' => $menu_item->description,
					'menu-item-attr-title'  => $menu_item->attr_title,
					'menu-item-target'      => $menu_item->target,
					'menu-item-classes'     => implode( ' ', $menu_item->classes ),
					'menu-item-xfn'         => $menu_item->xfn,
					'menu-item-status'      => $menu_item->post_status
				);

				$parent_id = wp_update_nav_menu_item( $dest, 0, $args );

				$rel[$menu_item->db_id] = $parent_id;

				// did it have a parent? if so, we need to update with the NEW ID
				if ( $menu_item->menu_item_parent ) {
					$args['menu-item-parent-id'] = $rel[$menu_item->menu_item_parent];
					$parent_id = wp_update_nav_menu_item( $dest, $parent_id, $args );
				}

				$i++;
			}
		}


		public function remove_alternative_menu( $menu_id ) {
			$this->unlink_menu_from_experiment( $menu_id );
			wp_delete_nav_menu( $menu_id );
		}


		/**
		 *
		 */
		private function is_menu_in_experiment( $menu ) {
			if ( is_object( $menu ) )
				$menu = $menu->term_id;
			if ( isset( $this->menus_in_exps[$menu] ) ) {
				return array(
					'exp' => $this->menus_in_exps[$menu]['exp'],
					'alt' => $this->menus_in_exps[$menu]['alt']
				);
			}
			else {
				return false;
			}
		}


		/**
		 *
		 */
		public function link_menu_to_experiment( $menu, $exp, $alt ) {
			if ( is_object( $menu ) )
				$menu = $menu->term_id;
			$this->menus_in_exps[$menu] = array( 'exp' => $exp, 'alt' => $alt );
		}


		/**
		 *
		 */
		private function unlink_menu_from_experiment( $menu ) {
			if ( is_object( $menu ) )
				$menu = $menu->term_id;
			unset( $this->menus_in_exps[$menu] );
		}


		/**
		 * This function removes all alternative menus. Useful when cleaning and
		 * deactivating the plugin.
		 */
		public static function clean_all_alternative_menus() {
			$aux = self::get_instance();
			$aux->begin();
			$alt_menus = array();
			foreach ( $aux->menus_in_exps as $menu => $exp )
				wp_delete_nav_menu( $menu );
			$aux->rollback();
		}


		/**
		 * This function stores all alternative menus somewhere else. Specially useful
		 * during plugin deactivation.
		 */
		public static function backup_alternative_menus() {
			$aux = self::get_instance();
			$aux->begin();
			$alt_menus = array();
			foreach ( $aux->menus_in_exps as $menu => $exp )
				array_push( $alt_menus, $menu );
			$aux->rollback();

			if ( count( $alt_menus ) > 0 ) {
				global $wpdb;
				$query = 'UPDATE ' . $wpdb->term_taxonomy .
					' SET taxonomy = \'nelioab_nav_menu\' ' .
					' WHERE term_id IN (' . implode( ',', $alt_menus ) . ')';
				$aux = $wpdb->query( $query );
			}

		}


		/**
		 * This function stores all alternative menus somewhere else. Specially useful
		 * during plugin deactivation.
		 */
		public static function restore_alternative_menu_backup() {
			global $wpdb;
			$query = 'UPDATE ' . $wpdb->term_taxonomy .
				' SET taxonomy = \'nav_menu\' ' .
				' WHERE taxonomy = \'nelioab_nav_menu\' ';
			$aux = $wpdb->query( $query );
		}

	}//NelioABMenuExpAdminController

}

