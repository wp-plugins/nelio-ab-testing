<?php

if ( !class_exists( 'NelioABWooCommerceSupport' ) ) {

	/**
	 * PHPDOC
	 *
	 * @since 4.2.0
	 */
	class NelioABWooCommerceSupport {

		/**
		 * Contains the instance of this singleton class.
		 *
		 * @since
		 * @var NelioABWooCommerceSupport
		 */
		private static $instance = NULL;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var array
		 */
		private $applied_product_summaries;


		/**
		 * It creates a new instance of this class.
		 *
		 * @return NelioABWooCommerceSupport a new instance of this class.
		 *
		 * @since PHPDOC
		 */
		public function __construct() {
			$this->applied_product_summaries = array();
		}


		/**
		 * PHPDOC
		 *
		 * @return NelioABWooCommerceSupport PHPDOC
		 *
		 * @since 4.2.0
		 */
		public static function get_instance() {
			if ( self::$instance == NULL ) {
				self::$instance = new NelioABWooCommerceSupport();
			}
			return self::$instance;
		}


		/**
		 * PHPDOC
		 *
		 * @return boolean PHPDOC
		 *
		 * @since 4.2.0
		 */
		public static function is_plugin_active() {
			$plugin = 'woocommerce/woocommerce.php';
			return in_array( $plugin, (array) get_option( 'active_plugins', array() ) );
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since 4.2.0
		 */
		public function hook() {
			require_once( NELIOAB_MODELS_DIR . '/visitor.php' );
			add_filter( 'nelioab_get_custom_post_types', array( &$this, 'remove_product_cpt' ) );

			// Replace Product Name in Cart
			add_filter( 'woocommerce_cart_item_name',
				array( &$this, 'replace_product_name_in_cart' ), 10, 2 );

			// Filters for Product Summary experiments
			add_filter( 'the_title',
				array( &$this, 'replace_product_summary_name' ), 10, 2 );
			add_filter( 'woocommerce_short_description',
				array( &$this, 'replace_product_summary_description' ), 10 );
			add_filter( 'get_post_metadata',
				array( &$this, 'fix_product_summary_featured_image' ), 10, 4 );
			add_filter( 'woocommerce_product_gallery_attachment_ids',
				array( &$this, 'fix_product_gallery' ), 10, 2 );

			add_action( 'nelioab_footer',
				array( &$this, 'print_list_of_applied_product_summaries' ) );
			add_filter( 'nelioab_ajax_result',
				array( &$this, 'add_list_of_applied_product_summaries' ) );

			// Controlling cart
			add_filter( 'woocommerce_add_cart_item',
				array( &$this, 'track_action_cart_item_added' ), 10, 1 );

			// Controlling orders
			add_action( 'woocommerce_checkout_order_processed',
				array( &$this, 'save_visitor_information_in_order' ), 10, 1 );
			add_action( 'woocommerce_order_status_completed',
				array( &$this, 'sync_order_completed' ), 10, 1 );

			// add_action( 'wp_enqueue_scripts', array( &$this, 'aux' ), 10 );
		}


		public function aux() {
			if ( is_order_received_page() ) {
				// TODO: Implement this function
			}
		}


		/**
		 * PHPDOC
		 *
		 * @param array $cart_item_data PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function track_action_cart_item_added( $cart_item_data ) {
			return $cart_item_data;
		}


		/**
		 * PHPDOC
		 *
		 * @param int $order_id PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function save_visitor_information_in_order( $order_id ) {

			$field = 'nelioab_userid';
			if ( isset( $_POST[$field] ) ) {
				$value = $_POST[$field];
				update_post_meta( $order_id, "_$field", $value );
			}

			$field = 'nelioab_form_env';
			if ( isset( $_POST[$field] ) ) {
				$value = json_decode( urldecode(  $_POST[$field] ), true );
				update_post_meta( $order_id, "_$field", $value );
			}

		}


		/**
		 * PHPDOC
		 *
		 * @param int $order_id PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function sync_order_completed( $order_id ) {

			$order = wc_get_order( $order_id );
			$items = $order->get_items();
			$user = get_post_meta( $order_id, '_nelioab_userid', true );
			$env = get_post_meta( $order_id, '_nelioab_form_env', true );

			if ( ! $user ) {
				return;
			}

			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
			$running_experiments = NelioABExperimentsManager::get_running_experiments_from_cache();

			// Let's start by selecting the relevant experiments.
			// A relevant experiment is a running experiment that was in the environment
			// that was active during the purchase AND that has at least one conversion
			// action in its goals that tracks an "Order Completed" event.
			$relevant_experiments = array();
			$relevant_conversion_actions = array();
			foreach ( $env as $id => $alt ) {
				foreach ( $running_experiments as $exp ) {
					if ( $exp->get_id() != $id ) {
						continue;
					}
					foreach ( $exp->get_goals() as $goal ) {
						if ( $goal->get_kind() != NelioABGoal::ALTERNATIVE_EXPERIMENT_GOAL ) {
							continue;
						}
						foreach ( $goal->get_actions() as $action ) {
							echo "Considering {$action->get_id()}... ";
							if ( $action->get_type() != NelioABAction::WC_ORDER_COMPLETED ) {
								echo "NO<br>";
								continue;
							}
								echo "YES<br>";
							// Once we've found a Order Complete conversion action, we save:
							//  a) the experiment
							//  b) the product controlled by that conversion action
							if ( !in_array( $exp, $relevant_experiments ) ) {
								array_push( $relevant_experiments, $exp );
							}
							array_push( $relevant_conversion_actions, $action );
						}
					}
				}
			}

			// Now we need to see if products are being tested and, if they are,
			// the IDs of the concrete alternatives the visitor saw.
			$relevant_products = array();
			foreach ( $relevant_experiments as $exp ) {
				if ( $exp->get_type() != NelioABExperiment::WC_PRODUCT_SUMMARY_ALT_EXP ) {
					continue;
				}
				$alt_num = false;
				foreach ( $env as $id => $alt ) {
					if ( $exp->get_id() == $id ) {
						$alt_num = $alt;
						break;
					}
				}
				if ( $alt_num !== false ) {
					if ( 0 == $alt_num ) {
						// We use "-1" because it's the ID we use for the "original alternative" object
						// (which doesn't exist in AE).
						$alt = -1;
					} else {
						$alts = $exp->get_alternatives();
						if ( $alt_num > count( $alts ) ) {
							continue;
						}
						$alt = $alts[$alt_num - 1];
						$alt = $alt->get_id();
					}
					$relevant_products[$exp->get_originals_id()] = $alt;
					break;
				}
			}


			// Once we have the list of PRODUCT_ID => ACTUAL_PRODUCT_ID, we need to
			// include all the other relevant product IDs:
			foreach ( $relevant_conversion_actions as $action ) {
				$product_id = $action->get_product_id();
				if ( !isset( $relevant_products[$product_id] ) ) {
					$relevant_products[$product_id] = $product_id;
				}
			}


			// Now we need to get information about the global experiments:
			$css_alt = false;
			$menu_alt = false;
			$theme_alt = false;
			$widget_alt = false;
			foreach ( $relevant_experiments as $exp ) {
				foreach ( $env as $id => $alt_num ) {
					if ( $exp->get_id() != $id ) {
						continue;
					}
					$alt = false;
					switch ( $exp->get_type() ) {
						case NelioABExperiment::CSS_ALT_EXP:
						case NelioABExperiment::MENU_ALT_EXP:
						case NelioABExperiment::THEME_ALT_EXP:
						case NelioABExperiment::WIDGET_ALT_EXP:
							if ( 0 == $alt_num ) {
								$alt = $exp->get_original();
							} else {
								$alts = $exp->get_alternatives();
								if ( $alt_num > count( $alts ) ) {
									continue;
								}
								$alt = $alts[$alt_num - 1];
							}
							$alt = $alt->get_id();
							break;
					}
					if ( $alt ) {
						switch ( $exp->get_type() ) {
							case NelioABExperiment::CSS_ALT_EXP:
								$css_alt = $alt;
								break;
							case NelioABExperiment::MENU_ALT_EXP:
								$menu_alt = $alt;
								break;
							case NelioABExperiment::THEME_ALT_EXP:
								$theme_alt = $alt;
								break;
							case NelioABExperiment::WIDGET_ALT_EXP:
								$widget_alt = $alt;
								break;
						}
					}
				}
			}



			// Finally, we can create the result object
			$result = array( 'products' => array() );
			foreach ( $relevant_products as $product_id => $actual_product_id ) {
				$product_found = false;
				foreach ( $items as $item ) {
					if ( isset( $item['product_id'] ) && $item['product_id'] == $product_id ) {
							$product_found = true;
						break;
					}
				}
				if ( $product_found ) {
					array_push( $result['products'], $product_id . ':' . $actual_product_id );
				}
			}

			if ( count( $result['products'] ) > 0 ) {
				$result['kind'] = 'OrderComplete';
				$result['products'] = implode( ',', $result['products'] );
				$result['user'] = $user;
				if ( $css_alt ) {
					$result['activeCSS'] = $css_alt;
				}
				if ( $menu_alt ) {
					$result['activeMenu'] = $menu_alt;
				}
				if ( $theme_alt ) {
					$result['activeTheme'] = $theme_alt;
				}
				if ( $widget_alt ) {
					$result['activeWidget'] = $widget_alt;
				}

				$url = sprintf(
					NELIOAB_BACKEND_URL . '/site/%s/productevent',
					NelioABAccountSettings::get_site_id()
				);

				$data = NelioABBackend::build_json_object_with_credentials( $result );
				$data['timeout'] = 50;

				for ( $attemp = 0; $attemp < 5; ++$attemp ) {
					try {
						NelioABBackend::remote_post_raw( $url, $data );
						break;
					}
					catch ( Exception $e ) {
						// If the form submission event could not be sent, it may be that's
						// because there is no more quota available
						if ( $e->getCode() == NelioABErrCodes::NO_MORE_QUOTA ) {
							// If that was the case, simply leave
							break;
						}
						// If there was another error... we just keep trying (attemp) up to 5
						// times.
					}
				}

			}
		}


		/**
		 * PHPDOC
		 *
		 * @param string $name      PHPDOC
		 * @param array  $cart_item PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function replace_product_name_in_cart( $name, $cart_item ) {
			$id = $cart_item['product_id'];
			$summary_data = NelioABVisitor::get_alternative_for_wc_product_summary_alt_exp( $id );
			if ( $summary_data ) {
				$alt = $summary_data['alt'];
				$new_name = $alt->get_name();
				$old_name = strip_tags( $name );
				return str_replace( $old_name, $new_name, $name );
			}
			return $name;
		}


		/**
		 * PHPDOC
		 *
		 * @param string $name PHPDOC
		 * @param int    $id   PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function replace_product_summary_name( $name, $id = NULL ) {
			if ( empty( $id ) )
				return $name;
			$summary_data = NelioABVisitor::get_alternative_for_wc_product_summary_alt_exp( $id );
			if ( $summary_data ) {
				$this->add_active_product_summary_experiment( $summary_data['exp'], $summary_data['alt'] );
				/** @var NelioABAlternative $alt */
				$alt = $summary_data['alt'];
				return $alt->get_name();
			}
			return $name;
		}


		/**
		 * PHPDOC
		 *
		 * @param string $excerpt PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function replace_product_summary_description( $excerpt ) {
			// This function can be tricky, because the global variable
			// post might not be properly set by some plugins...
			global $post;
			if ( !$post )
				return $excerpt;
			/** @var NelioABController $nelioab_controller */
			global $nelioab_controller;
			$summary_data = NelioABVisitor::get_alternative_for_wc_product_summary_alt_exp( $post->ID );
			if ( $summary_data ) {
				$this->add_active_product_summary_experiment( $summary_data['exp'], $summary_data['alt'] );
				/** @var NelioABAlternative $alt */
				$alt = $summary_data['alt'];
				$value = $alt->get_value();
				// This first IF is a safeguard...
				if ( is_array( $value ) && isset( $value['excerpt'] ) && !empty( $value['excerpt'] ) ) {
					return $value['excerpt'];
				}
			}
			return $excerpt;
		}


		/**
		 * PHPDOC
		 *
		 * @param string  $value     Type of object metadata is for (e.g., comment, post, or user)
		 * @param int     $object_id ID of the object metadata is for
		 * @param string  $meta_key  Optional. Metadata key.
		 * @param boolean $single    Optional.
		 *
		 * @return mixed Single metadata value, or array of values
		 *
		 * @since PHPDOC
		 */
		public function fix_product_summary_featured_image( $value, $object_id, $meta_key, $single ) {
			if ( '_thumbnail_id' == $meta_key ) {
				$summary_data = NelioABVisitor::get_alternative_for_wc_product_summary_alt_exp( $object_id );
				if ( $summary_data ) {
					$this->add_active_product_summary_experiment( $summary_data['exp'], $summary_data['alt'] );
					/** @var NelioABAlternative $alt */
					$alt = $summary_data['alt'];
					$value = $alt->get_value();
					// This first IF is a safeguard...
					if ( is_array( $value ) && isset( $value['image_id'] ) ) {
						if ( $single )
							return $value['image_id'];
						else
							return $value['image_id'];
					}
				}
			}
			return $value;
		}


		/**
		 * PHPDOC
		 *
		 * @param array      $ids     PHPDOC
		 * @param WC_Product $product PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function fix_product_gallery( $ids, $product ) {
			remove_filter( 'get_post_metadata',
				array( &$this, 'fix_product_summary_featured_image' ), 10, 4 );
			$original_image = get_post_thumbnail_id( $product->id );
			add_filter( 'get_post_metadata',
				array( &$this, 'fix_product_summary_featured_image' ), 10, 4 );
			$new_image = get_post_thumbnail_id( $product->id );

			$key = array_search( $original_image, $ids );
			if ( $key !== false && !in_array( $new_image, $ids ) ) {
				$ids[$key] = $new_image;
			}

			return $ids;
		}


		/**
		 * PHPDOC
		 *
		 * @param array $post_types PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since 4.2.0
		 */
		public function remove_product_cpt( $post_types ) {
			foreach ( $post_types as $key => $cpt ) {
				if ( 'product' === $cpt->name ) {
					unset( $post_types[$key] );
				}
			}
			return array_values( $post_types );
		}


		/**
		 * PHPDOC
		 *
		 * @param NelioABWooCommerceProductSummaryAlternativeExperiment $exp PHPDOC
		 * @param NelioABWooCommerceProductSummaryAlternative           $alt PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		private function add_active_product_summary_experiment( $exp, $alt ) {
			$exp_id = $exp->get_id();
			$alt_id = $alt->get_id();
			foreach ( $this->applied_product_summaries as $info )
				if ( $info['exp'] == $exp_id )
					return;
			array_push( $this->applied_product_summaries,
				array(
					'exp' => $exp_id,
					'alt' => $alt_id,
				)
			);
		}


		/**
		 * PHPDOC
		 *
		 * @param boolean $ajax PHPDOC
		 *
		 * @return array PHPDOC
		 *
		 * @since PHPDOC
		 */
		public function add_list_of_applied_product_summaries( $ajax = false ) {
			$res = array( 'nelioab' => array() );
			if ( $ajax ) {
				if ( isset( $ajax['nelioab'] ) ) {
					$res = $ajax;
				} else {
					$res['result'] = $ajax;
				}
			}


			$product_summaries = array();
			foreach ( $this->applied_product_summaries as $ps )
				array_push( $product_summaries, implode( ':', $ps ) );
			$product_summaries = implode( ',', $product_summaries );
			$res['nelioab']['productSummaries'] = $product_summaries;

			return $res;
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function print_list_of_applied_product_summaries() {
			if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) &&
			     ! wp_script_is( 'nelioab_tracking_script', 'done' ) )
				return;
			$aux = $this->add_list_of_applied_product_summaries();
			$product_summaries = $aux['nelioab']['productSummaries']; ?>
			<script type="text/javascript">
			/* <![CDATA[ */
			NelioABParams.sync.productSummaries = <?php
				echo json_encode( $product_summaries );
			?>;
			<?php
			if ( defined( 'DOING_AJAX' ) && DOING_AJAX )
				echo "// TODO: print it. NelioAB.helpers.sendNewHeadlineViews();";
			?>
			/* ]]> */
			</script><?php
		}

	}//NelioABWooCommerceSupport


	// Hook to WordPress and Nelio A/B Testing
	if ( NelioABWooCommerceSupport::is_plugin_active() ) {
		add_action( 'init', array( NelioABWooCommerceSupport::get_instance(), 'hook' ) );
	}

}

