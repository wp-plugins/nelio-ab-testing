<?php

if ( !class_exists( 'NelioABAboutPage' ) ) {

	class NelioABAboutPage {


		private static $instance;


		private function __construct__() {
			// Nothing to be done
		}


		public static function get_instance() {
			if ( NULL == self::$instance ) {
				self::$instance = new NelioABAboutPage();
				self::$instance->hook_to_wordpress();
			}
			return self::$instance;
		}


		private function hook_to_wordpress() {
			if ( isset( $_GET['page'] ) && 'nelioab-about' == $_GET['page'] ) {
				add_action( 'admin_menu', array( $this, 'create_page' ) );
				add_action( 'admin_head', array( $this, 'admin_head' ) );
			} else {
				$value = get_transient( '_nelioab_welcome_user' );
				delete_transient( '_nelioab_welcome_user' );
				if ( $value ) {
					$url = admin_url( 'index.php?page=nelioab-about' );
					$url = add_query_arg( $value, $value, $url );
					wp_redirect( $url );
				}
			}
		}


		public function create_page() {
			$page = add_dashboard_page(
				__( 'Welcome', 'nelioab' ),
				'nelioab-about',
				'manage_options',
				'nelioab-about',
				array( $this, 'print_page' ) );
		}


		public function admin_head() {
			remove_submenu_page( 'index.php', 'nelioab-about' ); ?>
			<style type="text/css">
				/*<![CDATA[*/
				.nelioab-badge {
					background: #313950;
					border-top: 12px solid #ff7e00;
					-webkit-box-shadow: 0 1px 3px rgba(0,0,0,.2);
					box-shadow: 0 3px 3px rgba(0,0,0,.2);
					color: #a8adbd;
					font-size: 14px;
					font-weight: 600;
					height: 40px;
					margin: 5px 0 0 0;
					padding-top: 150px;
					position: relative;
					text-align: center;
					text-rendering: optimizeLegibility;
					width: 165px;
				}
				.nelioab-badge .logo:before {
					font-family: NelioFont !important;
					content: "\f100";
					color: #fff;
					-webkit-font-smoothing: antialiased;
					-moz-osx-font-smoothing: grayscale;
					font-size: 80px;
					font-weight: normal;
					width: 165px;
					height: 165px;
					line-height: 165px;
					text-align: center;
					position: absolute;
					top: 0;
					<?php echo is_rtl() ? 'right' : 'left'; ?>: 0;
					margin: 0;
					vertical-align: middle;
				}
				.about-wrap .nelioab-badge {
					position: absolute;
					top: 0;
					<?php echo is_rtl() ? 'left' : 'right'; ?>: 0;
				}
			</style>
			<?php
		}


		public function print_page() {
			require_once( NELIOAB_ADMIN_DIR . '/views/about.php' );
		}


	}//NelioABAboutPage

}
