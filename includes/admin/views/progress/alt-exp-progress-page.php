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

if ( !class_exists( 'NelioABAltExpProgressPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );

	abstract class NelioABAltExpProgressPage extends NelioABAdminAjaxPage {

		const NO_WINNER = -999999;

		protected $exp;
		protected $results;
		protected $winner_label;
		protected $goals;
		protected $goal;
		protected $colorscheme;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->exp          = null;
			$this->goal         = null;
			$this->results      = null;

			require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			$this->colorscheme = NelioABWpHelper::get_current_colorscheme();
		}

		public function set_experiment( $exp ) {
			$this->exp = $exp;
		}

		public function set_goals( $goals ) {
			$sorted = array();
			$aux    = array();
			foreach ( $goals as $goal ) {
				if ( $goal->is_main_goal() )
					array_push( $sorted, $goal );
				else
					array_push( $aux, $goal );
			}

			// Sort aux alphabetically...
			usort( $aux, array( 'NelioABAltExpProgressPage', 'sort_by_name' ) );
			usort( $aux, array( 'NelioABAltExpProgressPage', 'sort_by_id' ) );

			// And add them in sorted
			foreach ( $aux as $goal )
				array_push( $sorted, $goal );
			$this->goals = $sorted;

			// Autoset names are only used by pre-3.0 experiments. For those,
			// the only possible actions where PageAccessedActions, and that's
			// why I assume $action[0] is a $page.
			$are_all_undefined = true;
			foreach ( $this->goals as $goal )
				if ( $goal->get_name() != __( 'Undefined', 'nelioab' ) )
					$are_all_undefined = false;
			if ( $are_all_undefined )
				foreach ( $this->goals as $goal )
					$this->autoset_goal_name( $goal );

			// Finally, we select one by default...
			$this->results = null;
		}

		private function autoset_goal_name( $goal ) {
			if ( $goal->is_main_goal() ) {
				$goal->set_name( __( 'Aggregated Info', 'nelioab' ) );
				return;
			}
			$action = $goal->get_actions();
			$page = $action[0];
			if ( $page->is_external() ) {
				$goal->set_name( $page->get_title() );
			}
			else {
				$name = __( 'Unnamed', 'nelioab' );
				$post = get_post( $page->get_reference() );
				if ( $post ) {
					$name = strip_tags( $post->post_title );
					if ( strlen( $name ) > 30 )
						$name = substr( $name, 0, 30 ) . '...';
				}
				$goal->set_name( $name );
			}
		}

		public static function sort_by_id( $a, $b ) {
			return $a->get_id() - $b->get_id();
		}

		public static function sort_by_name( $a, $b ) {
			return strcmp( $a->get_name(), $b->get_name() );
		}

		public function set_current_selected_goal( $id ) {
			$this->goal = false;

			foreach ( $this->goals as $goal )
				if ( $goal->get_id() == $id )
					$this->goal = $goal;

			if ( !$this->goal ) {
				foreach ( $this->goals as $goal )
					if ( $goal->is_main_goal() )
						$this->goal = $goal;
			}

			// If there's only one goal, but it's not set as the main goal (weird),
			// I use it by default. It should not happen, but sometimes it does. This
			// fragment resolves the issue.
			if ( !$this->goal )
				if ( count( $this->goals ) == 1 )
					$this->goal = $this->goals[0];

			if ( !$this->goal )
				return;

			try {
				$this->results = $this->goal->get_results();
			}
			catch ( Exception $e ) {
				require_once( NELIOAB_UTILS_DIR . '/backend.php' );
				if ( $e->getCode() == NelioABErrCodes::RESULTS_NOT_AVAILABLE_YET ) {
					$this->results = null;
				}
				else {
					require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
					NelioABErrorController::build( $e );
				}
			}
		}

		protected abstract function get_original_name();
		protected abstract function get_original_value();
		protected abstract function print_js_function_for_post_data_overwriting();

		protected function print_actions_info() {
			$actions = $this->goal->get_actions();
			if ( count( $actions ) <= 0 ) {
				$message = __( 'There are no actions in this goal.', 'nelioab' );
				echo "<h3>" . $message . "</h3>";
				return;
			}

			// PAGE_ACCESSED
			$page_accessed_actions = array_filter( $actions, array( $this, 'select_page_accessed_actions' ) );

			// POST_ACCESSED
			$post_accessed_actions = array_filter( $actions, array( $this, 'select_post_accessed_actions' ) );

			// EXTERNAL_PAGE_ACCESSED
			$external_page_accessed_actions = array_filter( $actions, array( $this, 'select_external_page_accessed_actions' ) );

			// SUBMIT_CF7_FORM & SUBMIT_GRAVITY_FORM
			$submit_form_actions = array_filter( $actions, array( $this, 'select_submit_form_actions' ) );

			// CLICK_ELEMENT
			$click_element_actions = array_filter( $actions, array( $this, 'select_click_element_actions' ) );

			// CLICK_ELEMENT
			$order_completed_actions = array_filter( $actions, array( $this, 'select_order_completed_actions' ) );

			if ( count( $page_accessed_actions )>0 )
				$this->print_page_accessed_actions_box( $page_accessed_actions );

			if ( count( $post_accessed_actions )>0 )
				$this->print_post_accessed_actions_box( $post_accessed_actions );

			if ( count( $external_page_accessed_actions )>0 )
				$this->print_external_page_accessed_actions_box( $external_page_accessed_actions );

			if ( count( $submit_form_actions )>0 )
				$this->print_submit_form_actions_box( $submit_form_actions );

			if ( count( $click_element_actions )>0 )
				$this->print_click_element_actions_box( $click_element_actions );

			if ( count( $order_completed_actions )>0 )
				$this->print_order_completed_actions_box( $order_completed_actions );
		}

		/* ACTION SELECTION FUNCTIONS */

		private function select_page_accessed_actions( $action ) {
			return $this->select_action( $action, NelioABAction::PAGE_ACCESSED );
		}

		private function select_post_accessed_actions( $action ) {
			return $this->select_action( $action, NelioABAction::POST_ACCESSED );
		}

		private function select_external_page_accessed_actions( $action ) {
			return $this->select_action( $action, NelioABAction::EXTERNAL_PAGE_ACCESSED );
		}

		private function select_submit_form_actions( $action ) {
			return $this->select_action( $action, NelioABAction::SUBMIT_CF7_FORM ) ||
			       $this->select_action( $action, NelioABAction::SUBMIT_GRAVITY_FORM );
		}

		private function select_click_element_actions( $action ) {
			return $this->select_action( $action, NelioABAction::CLICK_ELEMENT );
		}

		private function select_order_completed_actions( $action ) {
			return $this->select_action( $action, NelioABAction::WC_ORDER_COMPLETED );
		}

		private function select_action( $action, $action_type ) {
			/* @var NelioABAction $action */
			if ( $action->get_type() == $action_type )
				return true;
			else return false;
		}

		/* ACTION INFO PRINTING FUNCTIONS */

		protected function print_page_accessed_actions_box( $page_accessed_actions ) {
			$this->print_beautiful_box(
				'nelio-page-accessed-actions',
				$this->get_action_heading( NelioABAction::PAGE_ACCESSED ),
				array( &$this, 'print_page_accessed_actions_content', array( $page_accessed_actions ) ) );
		}

		protected function print_post_accessed_actions_box( $post_accessed_actions ) {
			$this->print_beautiful_box(
				'nelio-post-accessed-actions',
				$this->get_action_heading( NelioABAction::POST_ACCESSED ),
				array( &$this, 'print_post_accessed_actions_content', array( $post_accessed_actions ) ) );
		}

		protected function print_page_accessed_actions_content( $actions ) {

			foreach ( $actions as $action ) {
				$indirect = $action->accepts_indirect_navigations();

				if ( $this->exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ) {
					if ( !$indirect ) {
						$label = __( 'The following page is accessed from the tested page:', 'nelioab' );
					} else {
						$label = __( 'The following page is accessed:', 'nelioab' );
					}
				} elseif ( $this->exp->get_type() == NelioABExperiment::POST_ALT_EXP ) {
					if ( !$indirect ) {
						$label = __( 'The following page is accessed from the tested post:', 'nelioab' );
					} else {
						$label = __( 'The following page is accessed:', 'nelioab' );
					}
				} else {
					if ( !$indirect ) {
						$label = __( 'The following page is accessed from the tested page:', 'nelioab' );
					} else {
						$label = __( 'The following page is accessed:', 'nelioab' );
					}
				}

				$post = get_post( $action->get_reference() );
				if ( $post ) {
					$name = trim( strip_tags( $post->post_title ) );
					if ( strlen( $name ) == 0 )
						$name = __( 'no title', 'nelioab' );
					$link = get_permalink( $post );
					if ( strlen( $name ) == 0 )
						$name = $post->ID;
					$link = sprintf( '<a class="button" href="%s" target="_blank">%s <i class="fa fa-eye"></i></a>', $link, $name );
				} else {
					$label = __( 'A visitor accessed a page that, unfortunately, does no longer exist', 'nelioab' );
				}

				$html = <<<HTML
				<div class="nelio-page-accessed-action nelio-action-item">
					<i class="fa fa-file"></i>
					<span class="page-info">$label</span>
					<span class="page-value">$link</span>
				</div>
HTML;
				echo $html;
			}
		}


		protected function print_post_accessed_actions_content( $actions ) {

			foreach ( $actions as $action ) {
				$indirect = $action->accepts_indirect_navigations();

				if ( $this->exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ) {
					if ( !$indirect ) {
						$label = __( 'The following post is accessed from the tested page:', 'nelioab' );
					} else {
						$label = __( 'The following post is accessed:', 'nelioab' );
					}
				} elseif ( $this->exp->get_type() == NelioABExperiment::POST_ALT_EXP ) {
					if ( !$indirect ) {
						$label = __( 'The following post is accessed from the tested post:', 'nelioab' );
					} else {
						$label = __( 'The following post is accessed:', 'nelioab' );
					}
				} else {
					if ( !$indirect ) {
						$label = __( 'The following post is accessed from the tested page:', 'nelioab' );
					} else {
						$label = __( 'The following post is accessed:', 'nelioab' );
					}
				}

				$post = get_post( $action->get_reference() );
				if ( $post ) {
					$name = trim( strip_tags( $post->post_title ) );
					if ( strlen( $name ) == 0 )
						$name = __( 'no title', 'nelioab' );
					$link = get_permalink( $post );
					if ( strlen( $name ) == 0 )
						$name = $post->ID;
					$link = sprintf( '<a class="button" href="%s" target="_blank">%s <i class="fa fa-eye"></i></a>', $link, $name );
				} else {
					$label = __( 'A visitor accessed a post that, unfortunately, does no longer exist', 'nelioab' );
				}

				$html = <<<HTML
				<div class="nelio-page-accessed-action nelio-action-item">
					<i class="fa fa-thumb-tack"></i>
					<span class="page-info">$label</span>
					<span class="page-value">$link</span>
				</div>
HTML;
				echo $html;
			}
		}

		protected function print_external_page_accessed_actions_box( $external_page_accessed_actions ) {
			$this->print_beautiful_box(
				"nelio-external-page-accessed-actions",
				$this->get_action_heading( NelioABAction::EXTERNAL_PAGE_ACCESSED ),
				array( &$this, 'print_external_page_accessed_actions_content', array( $external_page_accessed_actions ) ) );
		}

		protected function print_external_page_accessed_actions_content( $external_page_accessed_actions ) {

			foreach ( $external_page_accessed_actions as $action ) {

				$indirect = $action->accepts_indirect_navigations();

				if ( $this->exp->get_type() == NelioABExperiment::PAGE_ALT_EXP ) {
					if ( !$indirect ) {
						$label = __( 'A visitor is about to leave your site from the tested page and go to:', 'nelioab' );
					} else {
						$label = __( 'A visitor is about to leave your site and go to:', 'nelioab' );
					}
				} elseif ( $this->exp->get_type() == NelioABExperiment::POST_ALT_EXP ) {
					if ( !$indirect ) {
						$label = __( 'A visitor is about to leave your site from the tested post and go to:', 'nelioab' );
					} else {
						$label = __( 'A visitor is about to leave your site and go to:', 'nelioab' );
					}
				} else {
					if ( !$indirect ) {
						$label = __( 'A visitor is about to leave your site from the tested page and go to:', 'nelioab' );
					} else {
						$label = __( 'A visitor is about to leave your site and go to:', 'nelioab' );
					}
				}

				$name = $action->get_title();
				$fake_link = '%1$s (<span style="text-decoration:underline;" title="%3$s">%2$s</span>)';
				$real_link = '<a href="%2$s" target="_blank">%1$s</a>';
				$real_link = '<a class="button" href="%2$s" target="_blank">%1$s <i class="fa fa-eye"></i></a>';
				switch ( $action->get_regex_mode() ) {
					case 'starts-with':
						$text = esc_html( sprintf( __( 'URL starts with "%s"', 'nelioab' ), $action->get_clean_reference() ) );
						$link = sprintf( $fake_link, $name, $action->get_clean_reference(), $text );
						break;
					case 'ends-with':
						$text = esc_html( sprintf( __( 'URL ends with "%s"', 'nelioab' ), $action->get_clean_reference() ) );
						$link = sprintf( $fake_link, $name, $action->get_clean_reference(), $text );
						break;
					case 'contains':
						$text = esc_html( sprintf( __( 'URL contains "%s"', 'nelioab' ), $action->get_clean_reference() ) );
						$link = sprintf( $fake_link, $name, $action->get_clean_reference(), $text );
						break;
					default:
						$link = sprintf( $real_link, $name, $action->get_reference() );
				}

				$html = <<<HTML
				<div class="nelio-external-page-accessed-action nelio-action-item">
					<i class="fa fa-paper-plane"></i>
					<span class="external-page-info">$label</span>
					<span class="external-page-value">$link</span>
				</div>
HTML;
				echo $html;
			}
		}

		protected function print_submit_form_actions_box( $submit_form_actions ) {
			$this->print_beautiful_box(
				"nelio-submit-form-actions",
				$this->get_action_heading( NelioABAction::FORM_SUBMIT ),
				array( &$this, 'print_submit_form_actions_content', array( $submit_form_actions ) ) );
		}

		protected function print_submit_form_actions_content( $submit_form_actions ) {

			$cf7  = is_plugin_active( 'contact-form-7/wp-contact-form-7.php' );
			$gf   = is_plugin_active( 'gravityforms/gravityforms.php' );

			foreach ( $submit_form_actions as $action ) {

				$form_id = $action->get_form_id();
				$type    = "";
				$name    = "";
				$link    = "";
				$mode    = "";
				$form    = "";
				$icon    = "";

				switch ( $action->get_type() ) {

					case NelioABAction::SUBMIT_CF7_FORM:
						$icon = "fa-check-square";
						$type = "Contact Form 7";
						if ( $cf7 ) {
							$aux = WPCF7_ContactForm::find( array( 'p' => $form_id ) );
							if ( count( $aux ) > 0 ) {
								$form = $aux[0];
								$name = $form->title();
								$link = admin_url( 'admin.php?page=wpcf7&action=edit&post=' . $form_id );
							}
						}
						$mode = __( 'from the tested page', 'nelioab' );
						if ( $action->accepts_submissions_from_any_page() )
							$mode = __( 'from any page', 'nelioab' );
						break;

					case NelioABAction::SUBMIT_GRAVITY_FORM:
						$icon = "fa-check-square-o";
						$type = "Gravity Forms";
						$mode = __( 'from the tested page', 'nelioab' );
						if ( $action->accepts_submissions_from_any_page() )
							$mode = __( 'from any page', 'nelioab' );
						if ( $gf ) {
							$form = GFAPI::get_form( $form_id );
							if ( $form ) {
								$name = $form['title'];
								$link = admin_url( 'admin.php?page=gf_edit_forms&id=' . $form_id );
							}
						}
						break;
				}

				if ( !$name ) {
					$name = __( "Unknown Form", "nelioab" );
				}
				$submission = sprintf(
					__( '%1$s submission', 'nelioab' ),	$type );

				$html = <<<HTML
				<div class="nelio-form-submission-action nelio-action-item">
					<i class="fa $icon"></i>
					<span class="form-info">$submission ($mode):</span>
					<span class="form-value">
						<a class="button" href="$link" target="_blank">
							$name
							<i class="fa fa-pencil"></i>
						</a>
					</span>
				</div>
HTML;
				echo $html;
			}
		}

		protected function print_click_element_actions_box( $click_element_actions ) {
			$this->print_beautiful_box(
				"nelio-click-element-actions",
				$this->get_action_heading( NelioABAction::CLICK_ELEMENT ),
				array( &$this, 'print_click_element_actions_content', array( $click_element_actions ) ) );
		}

		protected function print_click_element_actions_content( $click_element_actions ) {
			foreach ( $click_element_actions as $action ) {
				switch ( $action->get_mode() ) {
					case NelioABClickElementAction::ID_MODE:
						$icon      = "fa-code";
						$condition = __( 'Click on an element whose HTML identifier is:', 'nelioab' );
						$value     = $action->get_value();
						break;
					case NelioABClickElementAction::CSS_MODE:
						$icon      = "fa-pencil";
						$condition = __( 'Click on an element whose CSS path is:', 'nelioab' );
						$value     = $action->get_value();
						break;
					case NelioABClickElementAction::TEXT_MODE:
						$icon      = "fa-font";
						$condition = __( 'Click on an element whose text is:', 'nelioab' );
						$value     = $action->get_value();
						break;
					default:
						continue;
				}
				$html = <<<HTML
				<div class="nelio-click-element-action nelio-action-item">
					<i class="fa $icon"></i>
					<span class="condition">$condition</span>
					<span class="value">$value</span>
				</div>
HTML;
				echo $html;
			}
		}

		protected function print_order_completed_actions_box( $order_completed_actions ) {
			$this->print_beautiful_box(
				'nelio-order-completed-actions',
				$this->get_action_heading( NelioABAction::WC_ORDER_COMPLETED ),
				array( &$this, 'print_order_completed_actions_content', array( $order_completed_actions ) ) );
		}

		protected function print_order_completed_actions_content( $order_completed_actions ) {

			foreach ( $order_completed_actions as $action ) {

				$post = get_post( $action->get_product_id() );
				if ( $post ) {
					$name = trim( strip_tags( $post->post_title ) );
					if ( strlen( $name ) == 0 )
						$name = __( 'no title', 'nelioab' );
					$link = get_permalink( $post );
					if ( strlen( $name ) == 0 )
						$name = $post->ID;
					$link = sprintf( '<a class="button" href="%s" target="_blank">%s <i class="fa fa-eye"></i></a>', $link, $name );
				}
				else {
					$link = __( 'The product does not exist', 'nelioab' );
				}

				$message = __( 'An order that contains the following product is completed:', 'nelioab' );

				$html = <<<HTML
				<div class="nelio-page-accessed-action nelio-action-item">
					<i class="fa fa-shopping-cart"></i>
					<span class="page-info">$message</span>
					<span class="page-value">$link</span>
				</div>
HTML;
				echo $html;
			}
		}

		protected function get_action_heading( $type ) {
			switch ( $type ) {
				case NelioABAction::PAGE_ACCESSED:
					$icon = nelioab_admin_asset_link( '/images/tab-type-page.png' );
					$alt = __( 'A visitor accesses a page.', 'nelioab' );
					$title = __( 'Visit a Page', 'nelioab' );
					break;
				case NelioABAction::POST_ACCESSED:
					$icon = nelioab_admin_asset_link( '/images/tab-type-post.png' );
					$alt = __( 'A visitor accesses a post.', 'nelioab' );
					$title = __( 'Visit a Post', 'nelioab' );
					break;
				case NelioABAction::EXTERNAL_PAGE_ACCESSED:
					$icon = nelioab_admin_asset_link( '/images/tab-type-external.png' );
					$alt = __( 'A visitor leaves your site and accesses an external page.', 'nelioab' );
					$title = __( 'Visit an External Page', 'nelioab' );
					break;
				case NelioABAction::FORM_SUBMIT:
					$icon = nelioab_admin_asset_link( '/images/tab-type-form.png' );
					$alt = __( 'A visitor submits a form.', 'nelioab' );
					$title = __( 'Form Submissions', 'nelioab' );
					break;
				case NelioABAction::CLICK_ELEMENT:
					$icon = nelioab_admin_asset_link( '/images/tab-type-click.png' );
					$alt = __( 'A visitor clicks an element.', 'nelioab' );
					$title = __( 'Click Actions', 'nelioab' );
					break;
				case NelioABAction::WC_ORDER_COMPLETED:
					$icon = nelioab_admin_asset_link( '/images/tab-type-wc-product-summary.png' );
					$alt = __( 'An order with a certain product is completed.', 'nelioab' );
					$title = __( 'Order Completed', 'nelioab' );
					break;
			}

			$html = <<< HTML
			<div class="nelio-action-heading">
				<img src="$icon" alt="$alt"/>
				<span>$title</span>
			</div>
HTML;
			return $html;
		}


		protected function do_render() {
			require_once( NELIOAB_UTILS_DIR . '/formatter.php' );

			// SOME VARIABLES
			$exp  = $this->exp;
			$res  = $this->results;

			// Description of the experiment
			$descr = trim( $exp->get_description() );
			if ( empty( $descr ) )
				$descr = '-';

			// Original title
			$ori = $this->get_original_name();

			// Statistics
			$total_visitors    = 0;
			$total_conversions = 0;
			$conversion_rate   = '&mdash;';
			$originals_conversion_rate = '&mdash;';
			if ( $res ) {
				$total_visitors    = number_format_i18n( $res->get_total_visitors() );
				$total_conversions = number_format_i18n( $res->get_total_conversions() );
				$conversion_rate   = number_format_i18n( $res->get_total_conversion_rate(), 2 );
				$aux = $res->get_alternative_results();
				$originals_conversion_rate = number_format_i18n( $aux[0]->get_conversion_rate(), 2 );
				if ( $aux[0]->get_num_of_visitors() == 0 ) {
					$originals_conversion_rate = '&mdash;';
				}
			}

			// Winner (if any) details
			$the_winner            = $this->who_wins();
			$the_winner_confidence = $this->get_winning_confidence();

			$best_alt = $this->get_best_alt();

			$best_alt_improvement_factor = $this->get_best_alt_improvement_factor( $best_alt );
			if ( !is_double( $best_alt_improvement_factor ) )
				$best_alt_improvement_factor = '';
			else
				$best_alt_improvement_factor = number_format_i18n( $best_alt_improvement_factor, 2 );

			$best_alt_conversion_rate = $this->get_best_alt_conversion_rate();
			if ( $best_alt_conversion_rate < 0 )
				$best_alt_conversion_rate = '&mdash;';
			else
				$best_alt_conversion_rate = number_format_i18n( $best_alt_conversion_rate, 2 );

			$this->winner_label = sprintf( '" %s title="%s"',
				sprintf( 'style="color:%s;background:%s;"', $this->colorscheme['foreground'], $this->colorscheme['focus'] ),
				sprintf( __( 'Wins with a %s%% confidence', 'nelioab'), $the_winner_confidence )
			);

			// PRINTING RESULTS
			// ----------------------------------------------------------------
			?>

			<script type="text/javascript">
				var timelineGraphic;
				var visitsGraphic;
				var improvFactorGraphic;
				var convRateGraphic;
			</script>

			<div id="nelio-upper-progress-bar">
				<div id="nelio-progress-status"><?php
					if ( $exp->get_status() == NelioABExperiment::STATUS_RUNNING ) { ?>
						<div id="nelio-progress-status-running">
							<i class="fa fa-play faa-pulse animated faa-slow"></i>
							<span><?php _e( 'Running', 'nelioab' ) ?></span>
						</div>
						<div id="nelio-progress-status-stop" class="faa-parent animated-hover">
						<i class="fa fa-stop faa-pulse"></i>
						<span><?php _e( 'Stop', 'nelioab' ) ?></span>
						</div><?php
					} else { ?>
						<div id="nelio-progress-status-finish">
						<i class="fa fa-stop"></i>
						<span><?php _e( 'Finished', 'nelioab' ) ?></span>
						</div><?php
					} ?>
				</div>
				<div id="nelio-export-progress">
					<i title="<?php _e( 'Export experiment results', 'nelioab' ) ?>" class="fa fa-download fa-2x"></i>
				</div>
				<?php
				if ( count( $this->goals ) > 1 ) { ?>
					<div id="nelio-goal-selector">
					<i title="<?php _e( 'Select a goal', 'nelioab' ) ?>" class="fa fa-dot-circle-o fa-2x"></i>
					<i class="fa fa-caret-down"></i>
					<ul class="nelio-goals"><?php
						$this_goal_id = $this->goal->get_id();
						foreach ( $this->goals as $goal ) { ?>
							<li><?php
							$name   = $goal->get_name();
							$params = array( 'goal' => $goal->get_id() );
							$link   = esc_url( add_query_arg( $params, $_SERVER['HTTP_REFERER'] ) );
							if ( $goal->get_id() == $this_goal_id )
								echo "<span href=\"$link\" class=\"nelio-goal-active\">$name</span>";
							else
								echo "<a href='". $link . "'>" . $name . "</a>";?>
							</li><?php
						} ?>
					</ul>
					</div><?php
				} ?>
			</div>

			<h3 id="exp-tabs" class="nav-tab-wrapper" style="padding: 0em 0em 0em 1em;margin: 0em 0em 2em;">
				<span id="tab-info" class="nav-tab nelio-tab-progress nav-tab-active"><?php _e( 'General', 'nelioab'); ?></span>
				<span id="tab-alts" class="nav-tab nelio-tab-progress"><?php _e( 'Alternatives', 'nelioab'); ?></span>
				<span id="tab-actions" class="nav-tab nelio-tab-progress"><?php _e( 'Conversion Actions', 'nelioab'); ?></span>
			</h3>

			<!-- FRONT INFO BAR -->
			<div id="nelio-container-tab-info" class="nelio-tab-container" style="display:block;">
				<div class="row">
					<div class="fixed-width">
						<div id="nelio-exp-status" class="postbox nelio-card">
							<?php $this->print_experiment_status( $exp, $res, $the_winner, $the_winner_confidence,
								$originals_conversion_rate,	$best_alt, $best_alt_conversion_rate, $best_alt_improvement_factor ); ?>
						</div>
					</div>

					<div class="fluid">
						<div id="nelio-exp-timeline" class="postbox nelio-card">
							<div id="nelioab-timeline" class="nelioab-timeline-graphic"></div>
							<div id="nelioab-timeline-info">
								<div id="nelioab-timeline-info-text">
									<span class="nelio-pageviews-text"><?php _e( 'Pageviews', 'nelioab' ); ?></span>
									<span class="nelio-pageviews-value"><?php echo $total_visitors; ?></span>
									<span class="nelio-conversions-text"><?php _e( 'Conversions', 'nelioab' ); ?></span>
									<span class="nelio-conversions-value"><?php echo $total_conversions; ?></span>
									<span class="nelio-conversion-rate-value">(<?php echo $conversion_rate . '%'; ?>)</span>
								</div>
							</div>
							<?php
							if ( isset( $this->results ) && !$this->results->has_historic_info() )
								$this->print_timeline_js();
							else
								$this->print_timeline_for_alternatives_js();
							?>
						</div>
					</div>
				</div>

				<div class="row">
					<div class="fixed-width">
						<div id="nelio-exp-info" class="postbox nelio-card">
							<?php $this->print_experiment_information( $exp, $descr, $res ); ?>
						</div>
					</div>

					<?php
					// If results are available, print them.
					if ( $res != null ) { ?>

						<div class="fluid">
							<div id="nelio-exp-stats" class="postbox nelio-card">
								<div id="nelioab-conversion-rate" class="nelioab-summary-graphic"></div>
								<?php $this->print_conversion_rate_js(); ?>
							</div>
						</div>

						<div class="fluid">
							<div id="nelio-exp-config" class="postbox nelio-card">
								<div id="nelioab-improvement-factor" class="nelioab-summary-graphic"></div>
								<?php $this->print_improvement_factor_js(); ?>
							</div>
						</div>

						<?php

						// Otherwise, show a message stating that no data is available yet
					} else {
						if ( $exp->get_status() == NelioABExperiment::STATUS_RUNNING ) {
							$src = nelioab_admin_asset_link( '/images/collecting-results.png' );
							$message = __( 'Please be patient while we process the first results', 'nelioab' );
							$main_message = __( 'Collecting Data...', 'nelioab' );
							$status_message = __( 'There are no results available yet. Please, be patient until we collect more data. It might take up to half an hour to get your first results.', 'nelioab' );
							?>
							<div class="fluid">
								<div id="no-results" class="postbox nelio-card">
									<div class="content">
									<span class="main-message"><?php echo $main_message; ?></span>
									<img src="<?php echo $src; ?>" title="<?php echo $message; ?>" alt="<?php echo $message; ?>" class="masterTooltip animated flipInY"/>
									<span class="additional-message"><?php echo $status_message; ?></span>
									</div>
								</div>
							</div>
						<?php
						}
						else {
							$src = nelioab_admin_asset_link( '/images/cloud-data.png' );
							$message = __( 'No data was collected before stopping the experiment', 'nelioab' );
							$main_message = __( 'No Data Available', 'nelioab' );
							$status_message = __( 'The experiment has no results, probably because it was stopped before Nelio A/B Testing could collect any data.', 'nelioab' );
							?>
							<div class="fluid">
								<div id="no-results" class="postbox nelio-card">
									<div class="content">
									<span class="main-message"><?php echo $main_message; ?></span>
									<img src="<?php echo $src; ?>" title="<?php echo $message; ?>" alt="<?php echo $message; ?>" class="masterTooltip animated flipInY"/>
									<span class="additional-message"><?php echo $status_message; ?></span>
										</div>
								</div>
							</div>
						<?php
						}
					}?>

				</div>
				<script type="text/javascript">
					setTimeout( function() {
						jQuery(window).trigger( 'resize' );
						nelioabShowCurrentGraphics( 'tab-info', 200 );
					}, 1000 );
				</script>
			</div>

			<div id="nelio-container-tab-alts" class="nelio-tab-container">

				<?php
				$this->print_alternatives_boxes();

				if ( $exp->get_status() == NelioABExperiment::STATUS_FINISHED ) { ?>
					<script>
						<?php
						$this->print_js_function_for_post_data_overwriting();
						?>

						(function($) {
							$('#dialog-modal').dialog({
								title: '<?php echo esc_html( __( 'Overwrite Original', 'nelioab' ) ); ?>',
								dialogClass   : 'wp-dialog',
								modal         : true,
								autoOpen      : false,
								closeOnEscape : true,
								buttons: [
									{
										text: "<?php echo esc_html( __( 'Cancel', 'nelioab' ) ); ?>",
										click: function() {
											$(this).dialog('close');
										}
									},
									{
										text: "<?php echo esc_html( __( 'Overwrite', 'nelioab' ) ); ?>",
										'class': 'button-primary',
										click: function() {
											$(this).dialog('close');
											var id = $(this).data('overwrite-with');
											nelioab_do_overwrite(id);
										}
									}
								]
							});
						})(jQuery);

						function nelioab_show_the_dialog_for_overwriting(id) {
							var aux = jQuery("#dialog-modal");
							aux.data('overwrite-with', id);
							aux.dialog('open');
						}

						function nelioab_do_overwrite(id) {
							jQuery(".apply-link").each(function() {
								var aux = jQuery(this);
								aux.addClass('disabled');
								aux.attr('href','javascript:;');
							});
							jQuery("#loading-" + id).parent().removeClass('disabled');
							jQuery("#loading-" + id).delay(120).fadeIn();

							jQuery.ajax({
								url: jQuery("#apply_alternative").attr("action"),
								type: 'post',
								data: jQuery('#apply_alternative').serialize(),
								success: function(data) {
									jQuery("#loading-" + id).delay(250).fadeOut(250);
									jQuery("#loading-" + id).parent().addClass('disabled');
									jQuery("#loading-" + id).parent().text('<?php _e( 'Done!', 'nelioab' ); ?>');
									jQuery("#success-" + id).delay(1000).fadeIn(200).delay(10000).fadeOut(200);
								}
							});
						}
					</script>
				<?php
				}
				?>
			</div>

			<div id="nelio-container-tab-actions" class="nelio-tab-container">
				<div id="exp-info-goal-actions">
					<?php
					$this->print_actions_info();
					?>
				</div>
			</div>


			<p style="text-align:right;color:gray;"><?php
				if ( $res != null ) {
					if ( $exp->get_status() == NelioABExperiment::STATUS_RUNNING ) { ?>
						<i class="fa fa-clock-o"
						   style="font-size: 1.5em;vertical-align: middle;"></i>&nbsp;<?php
						printf( __( 'Last Update: %s', 'nelioab' ),
							NelioABFormatter::format_date( $res->get_last_update() ) );
					} else {
						printf( __( 'Last Update: %s', 'nelioab' ),
							NelioABFormatter::format_date( $res->get_last_update() ) );
					}
				}
				?></p>

			<?php
			if ( $exp->get_status() == NelioABExperiment::STATUS_RUNNING ) { ?>
				<script type="text/javascript">
					(function ($) {
						$('#dialog-modal').dialog({
							dialogClass: 'wp-dialog',
							modal: true,
							autoOpen: false,
							closeOnEscape: true,
							buttons: [
								{
									text: "<?php echo esc_html( __( 'Cancel', 'nelioab' ) ); ?>",
									click: function () {
										$(this).dialog('close');
									}
								},
								{
									text: "<?php echo esc_html( __( 'OK', 'nelioab' ) ); ?>",
									'class': 'button-primary',
									click: function () {
										$(this).dialog('close');
										nelioabAcceptDialog($(this));
									}
								}
							]
						});
					})(jQuery);

					function nelioabAcceptDialog(dialog) {
						var action = dialog.data('action');
						if ('stop' == action)
							nelioabForceStop();
						else if ('edit' == action)
							nelioabConfirmEditing(dialog.data('href'));
					}

					function nelioabConfirmEditing(href, dialog) {
						if ('dialog' == dialog) {<?php
						$title = __( 'Edit Alternative', 'nelioab' );
						$title = str_replace( '"', '\\"', $title );
						$msg = __( 'Editing an alternative while the experiment is running may invalidate the results of the experiment. Do you really want to continue?', 'nelioab' );
						$msg = str_replace( '"', '\\"', $msg ); ?>
							var $dialog = jQuery('#dialog-modal');
							jQuery('#dialog-content').html("<?php echo $msg; ?>");
							$dialog.dialog('option', 'title', "<?php echo $title; ?>");
							$dialog.parent().find('.button-primary .ui-button-text').text("<?php _e( 'Edit' ); ?>");
							$dialog.data('action', 'edit');
							$dialog.data('href', href);
							$dialog.dialog('open');
							return;
						}
						window.location.href = href;
					}

					function nelioabForceStop(dialog) {
						if ('dialog' == dialog) {<?php
						$title = __( 'Stop Experiment', 'nelioab' );
						$title = str_replace( '"', '\\"', $title );
						$msg = __( 'You are about to stop an experiment. Once the experiment is stopped, you cannot resume it. Are you sure you want to stop the experiment?', 'nelioab' );
						$msg = str_replace( '"', '\\"', $msg ); ?>
							var $dialog = jQuery('#dialog-modal');
							jQuery('#dialog-content').html("<?php echo $msg; ?>");
							$dialog.dialog('option', 'title', "<?php echo $title; ?>");
							$dialog.parent().find('.button-primary .ui-button-text').text("<?php _e( 'Stop', 'nelioab' ); ?>");
							$dialog.data('action', 'stop');
							$dialog.dialog('open');
							return;
						}
						smoothTransitions();
						jQuery.get(
							"<?php
							echo admin_url( sprintf(
								'admin.php?page=nelioab-experiments&action=progress&id=%s&exp_type=%s&forcestop=true',
								$this->exp->get_id(), $this->exp->get_type() )
							); ?>",
							function (data) {
								data = jQuery.trim(data);
								if (data.indexOf("[SUCCESS]") == 0) {
									location.href = data.replace("[SUCCESS]", "");
								}
								else {
									document.open();
									document.write(data);
									document.close();
								}
							});
					}
				</script><?php
			} ?>

			<script type="text/javascript">
				(function($) {
					$( document ).ready(function() {

						var timeouts = [];

						// Navigation Tabs
						$(".nav-tab").click(function () {
							if ( $(this).hasClass("nav-tab-active") )
								return;

							for ( var i = 0; i < timeouts.length; ++i ) {
								clearTimeout( timeouts[i] );
							}
							timeouts = [];

							$(".nav-tab").removeClass("nav-tab-active");
							$(".nelio-tab-container").hide();
							$("#poststuff .highcharts-container").parent().css('visibility', 'hidden');
							$("#poststuff .highcharts-container").parent().show();

							$(this).addClass("nav-tab-active");
							var id = $(this).attr('id');
							$("#nelio-container-" + id).fadeIn(600);

							var aux;
							aux = setTimeout( function() {
								$(window).trigger('resize');
							}, 700 );
							timeouts.push( aux );

							var aux = nelioabShowCurrentGraphics( id, 1000 );
							for ( var i = 0; i < aux.length; ++i ) {
								timeouts.push(aux[i]);
							}

							if (id == 'tab-actions') {
								// Masonry for Conversion Actions
								var container = $('#exp-info-goal-actions');
								container.masonry();
							}
						});

						// Upper Bar
						$("#nelio-upper-progress-bar").fadeIn(600);
						$("#nelio-progress-status-stop").click(function() {
							javascript:nelioabForceStop('dialog');
						});
						$("#nelio-export-progress").click(function() {
							alert("This functionality is under development.");
						});
						$("#nelio-goal-selector").click(function() {
							$("ul.nelio-goals").fadeIn(100);
						});
						$('ul.nelio-goals').on('mouseleave',function(){ // When losing focus
							$(this).fadeOut(100);
						});

						// Tooltip Image Status
						$('.masterTooltip').hover(function() {
							// Hover over code
							var title = $(this).attr('title');
							$(this).data('tipText', title).removeAttr('title');
							$('<p class="tooltip"></p>')
								.text(title)
								.appendTo('body')
								.fadeIn('slow');
						}, function() {
							// Hover out code
							$(this).attr('title', $(this).data('tipText'));
							$('.tooltip').remove();
						}).mousemove(function(e) {
							var mousex = e.pageX + 20; //Get X coordinates
							var mousey = e.pageY + 10; //Get Y coordinates
							$('.tooltip')
								.css({ top: mousey, left: mousex })
						});
					});
				})(jQuery);
			</script>
			<?php
		}

		protected function print_experiment_status( $exp, $res, $the_winner, $the_winner_confidence,
			$originals_conversion_rate, $best_alt, $best_alt_conversion_rate, $best_alt_improvement_factor ) {
			if ( $res )
				$message = NelioABGTest::generate_status_message( $res->get_summary_status() );
			else
				$message = NelioABGTest::generate_status_message( false );

			$src = nelioab_admin_asset_link( '/images/progress-no.png' );

			if ( $best_alt > 0 )
				$best_alt = '(' . __( 'Alternative', 'nelioab' ) . ' ' . $best_alt . ')';
			else
				$best_alt = '';

			$arrow = '';
			$stats_color = 'auto';
			$gain = '';

			if ( self::NO_WINNER == $the_winner ) {
				$main_message = __( 'Testing...', 'nelioab' );

				if ( NelioABExperiment::STATUS_RUNNING == $exp->get_status() )
					$status_message = __( 'No alternative is better than the rest', 'nelioab' );
				else
					$status_message = __( 'No alternative was better than the rest', 'nelioab' );
			}
			else {
				$main_message = __( '¡Winner!', 'nelioab' );
				if ( $the_winner == 0 ) {
					if ( $the_winner_confidence >= NelioABSettings::get_min_confidence_for_significance() )
						$status_message = sprintf( __( 'Original wins with a %1$s%% confidence', 'nelioab' ),
							$the_winner_confidence );
					else
						$status_message = sprintf( __( 'Original wins with just a %1$s%% confidence', 'nelioab' ),
							$the_winner_confidence );
				} else {
					if ( $the_winner_confidence >= NelioABSettings::get_min_confidence_for_significance() )
						$status_message = sprintf( __( 'Alternative %1$s wins with a %2$s%% confidence', 'nelioab' ),
							$the_winner, $the_winner_confidence );
					else
						$status_message = sprintf( __( 'Alternative %1$s wins with just a %2$s%% confidence', 'nelioab' ),
							$the_winner, $the_winner_confidence );
				}

				if ( $the_winner_confidence >= NelioABSettings::get_min_confidence_for_significance() )
					$src = nelioab_admin_asset_link( '/images/progress-yes.png' );
				else
					$src = nelioab_admin_asset_link( '/images/progress-yes-no.png' );
			}

			$print_improvement = false;

			if ( is_numeric( $best_alt_improvement_factor ) ) {

				// gain
				$alt_results = $this->results->get_alternative_results();
				$ori_conversions = $alt_results[0]->get_num_of_conversions();
				$aux = ( $ori_conversions * $this->goal->get_benefit() * $best_alt_improvement_factor )/100;

				$print_improvement = true;
				// format improvement factor
				if ( $best_alt_improvement_factor < 0 ) {
					$arrow                       = 'fa-arrow-down';
					$stats_color                 = 'red';
					$best_alt_improvement_factor = $best_alt_improvement_factor * - 1;
				} else if ( $best_alt_improvement_factor > 0 ) {
					$arrow = 'fa-arrow-up';
					$stats_color = 'green';
				} else {
					$print_improvement = false;
					$arrow = 'fa-arrow-none';
					$stats_color = 'black';
				}

				if ( $aux > 0 ) {
					$gain = sprintf( __( '%1$s%2$s', 'nelioab', 'money' ),
						NelioABSettings::get_conv_unit(),
						number_format_i18n( $aux, 2 )
					);
				} else {
					$gain = sprintf( __( '%1$s%2$s', 'nelioab', 'money' ),
						NelioABSettings::get_conv_unit(),
						number_format_i18n( $aux * -1, 2 )
					);
				}
			}

			?>

			<div id="info-status">
				<span class="main-message"><?php echo $main_message; ?></span>
				<img src="<?php echo $src; ?>" title="<?php echo $message; ?>" alt="<?php echo $message; ?>" class="masterTooltip animated flipInY"/>
				<span class="additional-message"><?php echo $status_message; ?></span>
			</div>
			<div id="ori-status">
				<span class="ori-name"><?php _e( 'Original', 'nelioab' ); ?></span>
				<div id="ori-cr">
					<span class="ori-cr-title"><?php _e( 'Conversion Rate', 'nelioab' ); ?></span>
					<span class="ori-cr-value"><?php printf( '%s %%', $originals_conversion_rate ); ?></span>
				</div>
			</div>
			<div id="alt-status">
				<span class="alt-name"><?php _e( 'Best Alternative', 'nelioab' ); ?> <?php echo $best_alt; ?></span>
				<div id="alt-cr">
					<span class="alt-cr-title"><?php _e( 'Conversion Rate', 'nelioab' ); ?></span>
					<span class="alt-cr"><?php printf( '%s %%', $best_alt_conversion_rate ); ?></span>
				</div>
				<div id="alt-stats" style="color:<?php echo $stats_color; ?>;">
					<span class="alt-if"><i class="fa <?php echo $arrow; ?>" style="vertical-align: top;"></i><?php if ( $print_improvement ) printf( '%s%%', $best_alt_improvement_factor ); ?></span>
					<span class="alt-ii"><i class="fa <?php echo $arrow; ?>" style="vertical-align: top;"></i><?php if ( $print_improvement ) echo $gain; ?></span>
				</div>
			</div>
		<?php
		}

		protected function get_experiment_icon( $exp ){
			$img = '<div class="tab-type tab-type-%1$s" alt="%2$s" title="%2$s"></div>';

			switch( $exp->get_type() ) {
				case NelioABExperiment::PAGE_ALT_EXP:
					$page_on_front = get_option( 'page_on_front' );
					$aux = $exp->get_original();
					if ( $page_on_front == $aux->get_value() )
						return sprintf( $img, 'landing-page', __( 'Landing Page', 'nelioab' ) );
					else
						return sprintf( $img, 'page', __( 'Page', 'nelioab' ) );

				case NelioABExperiment::POST_ALT_EXP:
					return sprintf( $img, 'post', __( 'Post', 'nelioab' ) );

				case NelioABExperiment::CPT_ALT_EXP:
					return sprintf( $img, 'cpt', __( 'Custom Post Type', 'nelioab' ) );

				case NelioABExperiment::HEADLINE_ALT_EXP:
					return sprintf( $img, 'title', __( 'Headline', 'nelioab' ) );

				case NelioABExperiment::THEME_ALT_EXP:
					return sprintf( $img, 'theme', __( 'Theme', 'nelioab' ) );

				case NelioABExperiment::CSS_ALT_EXP:
					return sprintf( $img, 'css', __( 'CSS', 'nelioab' ) );

				case NelioABExperiment::HEATMAP_EXP:
					return sprintf( $img, 'heatmap', __( 'Heatmap', 'nelioab' ) );

				case NelioABExperiment::WIDGET_ALT_EXP:
					return sprintf( $img, 'widget', __( 'Widget', 'nelioab' ) );

				case NelioABExperiment::MENU_ALT_EXP:
					return sprintf( $img, 'menu', __( 'Menu', 'nelioab' ) );

				case NelioABExperiment::WC_PRODUCT_SUMMARY_ALT_EXP:
					return sprintf( $img, 'wc-product-summary', __( 'WooCommerce Product Summary', 'nelioab' ) );

				default:
					return '';
			}
		}

		protected function get_winner_icon( $exp ){
			$img = '<div class="tab-type tab-type-winner" alt="%1$s" title="%1$s"></div>';

			switch( $exp->get_type() ) {
				case NelioABExperiment::PAGE_ALT_EXP:
					$page_on_front = get_option( 'page_on_front' );
					$aux = $exp->get_original();
					if ( $page_on_front == $aux->get_value() )
						return sprintf( $img, __( 'Landing Page', 'nelioab' ) );
					else
						return sprintf( $img, __( 'Page', 'nelioab' ) );

				case NelioABExperiment::POST_ALT_EXP:
					return sprintf( $img, __( 'Post', 'nelioab' ) );

				case NelioABExperiment::CPT_ALT_EXP:
					return sprintf( $img, __( 'Custom Post Type', 'nelioab' ) );

				case NelioABExperiment::HEADLINE_ALT_EXP:
					return sprintf( $img, __( 'Headline', 'nelioab' ) );

				case NelioABExperiment::THEME_ALT_EXP:
					return sprintf( $img, __( 'Theme', 'nelioab' ) );

				case NelioABExperiment::CSS_ALT_EXP:
					return sprintf( $img, __( 'CSS', 'nelioab' ) );

				case NelioABExperiment::HEATMAP_EXP:
					return sprintf( $img, __( 'Heatmap', 'nelioab' ) );

				case NelioABExperiment::WIDGET_ALT_EXP:
					return sprintf( $img, __( 'Widget', 'nelioab' ) );

				case NelioABExperiment::MENU_ALT_EXP:
					return sprintf( $img, __( 'Menu', 'nelioab' ) );

				default:
					return '';
			}
		}

		protected function print_experiment_information( $exp, $descr, $res ) { ?>
			<div id="exp-info-header">
				<?php echo $this->get_experiment_icon( $exp ); ?>
				<span class="exp-title"><?php echo $exp->get_name(); ?></span>
			</div>

			<?php
			$startDate = NelioABFormatter::format_date( $exp->get_start_date() );
			$end   = $exp->get_end_date();
			if ( empty( $end ) ) {
				$running = __( 'Started on', 'nelioab' ) . ' ' . $startDate;
			} else {
				$endDate = NelioABFormatter::format_date( $end );
				$running = $startDate . '&mdash;' . $endDate;
			}

			if ( $res == null && $exp->get_status() == NelioABExperiment::STATUS_FINISHED ) {
				$duration = NelioABFormatter::get_timelapse( $exp->get_start_date(), $exp->get_end_date() );
			} else if ( $res == null ) {
				$duration = __( 'Not available', 'nelioab' );
			} else
				$duration = NelioABFormatter::get_timelapse( $exp->get_start_date(), $res->get_last_update() );
			?>

			<div id="exp-info-running-time">
				<span class="exp-info-header"><?php _e( 'Duration', 'nelioab' ) ?></span>
				<span class="exp-info-duration"><?php echo $duration; ?></span>
				<span class="exp-info-running-values"><?php echo $running; ?></span>
			</div>

			<?php
			$end_mode = __( 'The experiment can only be stopped manually', 'nelioab' );

			if ( $exp->get_status() == NelioABExperiment::STATUS_RUNNING &&
			     NelioABAccountSettings::get_subscription_plan() >= NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN ) {

				switch ( $exp->get_finalization_mode() ) {
					case NelioABExperiment::FINALIZATION_MANUAL:
						$end_mode = __( 'The experiment can only be stopped manually', 'nelioab' );
						break;

					case NelioABExperiment::FINALIZATION_AFTER_DATE:
						$raw_fin_value = $exp->get_finalization_value();
						$fin_value     = __( '24 hours', 'nelioab' );
						if ( $raw_fin_value >= 2 ) {
							$fin_value = __( '48 hours', 'nelioab' );
						}
						if ( $raw_fin_value >= 5 ) {
							$fin_value = __( '5 days', 'nelioab' );
						}
						if ( $raw_fin_value >= 7 ) {
							$fin_value = __( '1 week', 'nelioab' );
						}
						if ( $raw_fin_value >= 14 ) {
							$fin_value = __( '2 weeks', 'nelioab' );
						}
						if ( $raw_fin_value >= 30 ) {
							$fin_value = __( '1 month', 'nelioab' );
						}
						if ( $raw_fin_value >= 60 ) {
							$fin_value = __( '2 months', 'nelioab' );
						}
						$end_mode = sprintf(
							__( 'The experiment will be automatically stopped %s after it was started.', 'nelioab' ),
							$fin_value
						);
						break;

					case NelioABExperiment::FINALIZATION_AFTER_VIEWS:
						$end_mode = sprintf(
							__( 'The experiment will be automatically stopped when the tested page (along with its alternatives) has been seen over %s times.', 'nelioab' ),
							$exp->get_finalization_value()
						);
						break;

					case NelioABExperiment::FINALIZATION_AFTER_CONFIDENCE:
						$end_mode = sprintf(
							__( 'The experiment will be automatically stopped when confidence reaches %s%%.', 'nelioab' ),
							$exp->get_finalization_value()
						);
						break;
				}
			} ?>

			<div id="exp-info-end-mode">
				<span><?php _e( 'Finalization Mode', 'nelioab' ); ?></span>
				<span class="exp-end-mode"><?php echo $end_mode; ?></span>
			</div>

			<?php
			if ( empty( $descr ) )
				$descr = __( 'No description provided', 'nelioab' );
			?>

			<div id="exp-info-descr">
				<span><?php _e( 'Description', 'nelioab' ) ?></span>
				<span><?php echo $descr; ?></span>
			</div>

		<?php
		}

		protected function print_alternatives_block() {
			echo '<ul>';
			$this->print_the_original_alternative();
			$this->print_the_real_alternatives();
			echo '</ul>';
		}

		protected function print_alternatives_boxes() { ?>
			<div id="nelio-progress-alternatives">
			<?php $this->print_the_original_alternative(); ?>
			<?php $this->print_the_real_alternatives(); ?>
			</div><?php
		}

		abstract protected function print_the_original_alternative();
		abstract protected function print_the_real_alternatives();

		protected function trunk( $in ) {
			return strlen( $in ) > 50 ? substr( $in, 0, 50 ) . '...' : $in;
		}

		protected function get_best_alt() {
			$res = $this->results;
			if ( $res == null )
				return -1;
			$best = 0;
			$best_name = '';
			$alts = $res->get_alternative_results();
			for ( $i = 1; $i < count( $alts ); ++$i ) {
				$alt_result = $alts[$i];
				$conv = $alt_result->get_conversion_rate();
				if ( $best < $conv ) {
					$best = $conv;
					$best_name = $i;
				}
			}
			return $best_name;
		}

		protected function get_best_alt_conversion_rate() {
			$res = $this->results;
			if ( $res == null )
				return self::NO_WINNER;
			$best = -1;
			$alts = $res->get_alternative_results();
			$page_views = 0;
			for ( $i = 1; $i < count( $alts ); ++$i ) {
				$alt_result = $alts[$i];
				$conv = $alt_result->get_conversion_rate();
				if ( $best < $conv ) {
					$best = $conv;
					$page_views = $alt_result->get_num_of_visitors();
				}
			}
			if ( 0 == $page_views ) {
				return self::NO_WINNER;
			}
			return $best;
		}

		protected function get_best_alt_improvement_factor( $i ) {
			$res = $this->results;
			if ( $res == null )
				return '';
			if ( $i <= 0 )
				return '';

			$alts = $res->get_alternative_results();
			$alt_result = $alts[$i];
			$best = $alt_result->get_improvement_factor();

			return $best;
		}

		protected function get_winning_confidence() {
			$bestg = $this->get_winning_gtest();
			if ( !$bestg )
				return self::NO_WINNER;
			return number_format_i18n( $bestg->get_certainty(), 2 );
		}

		protected function get_winning_gtest() {
			$res = $this->results;
			if ( $res == null )
				return false;

			$gtests = $res->get_gtests();

			if ( count( $gtests ) == 0 )
				return false;

			/** @var NelioABGTest $bestg */
			$bestg = $gtests[count( $gtests ) - 1];

			if ( $bestg->is_original_the_best() ) {
				if ( $bestg->get_type() == NelioABGTest::WINNER )
					return $bestg;
			}
			else {
				$aux = null;
				foreach ( $gtests as $gtest )
					if ( $gtest->get_min() == $this->get_original_value() )
						$aux = $gtest;
				if ( $aux )
					if ( $aux->get_type() == NelioABGTest::WINNER ||
					     $aux->get_type() == NelioABGTest::DROP_VERSION )
						return $aux;
			}

			return false;
		}

		protected function is_winner( $id ) {
			$winner = $this->who_wins_real_id();

			if ( self::NO_WINNER == $winner )
				return false;
			else
				return $id == $winner;
		}

		protected function who_wins() {
			$exp = $this->exp;
			$winner_id = $this->who_wins_real_id();
			if ( $winner_id == $exp->get_originals_id() )
				return 0;
			$i = 1;
			foreach ( $exp->get_alternatives() as $alt ) {
				if ( $winner_id == $alt->get_value() )
					return $i;
				$i++;
			}
			return self::NO_WINNER;
		}

		protected function who_wins_real_id() {
			$res = $this->results;
			if ( $res == null )
				return self::NO_WINNER;

			$gtests = $res->get_gtests();
			if ( count( $gtests ) == 0 )
				return self::NO_WINNER;

			$aux = false;
			foreach ( $gtests as $gtest ) {
				if ( $gtest->get_type() == NelioABGTest::WINNER ||
				     $gtest->get_type() == NelioABGTest::DROP_VERSION )
					$aux = $gtest->get_max();
			}

			if ( $aux )
				return $aux;
			else
				return self::NO_WINNER;
		}

		/**
		 * @deprecated
		 */
		protected function print_timeline_js() {

			$res = $this->results;

			// Start date
			// -------------------------------------------
			$first_update = time();
			if ( is_object( $res ) )
				$first_update = strtotime( $res->get_first_update() ); // This has to be a unixtimestamp...
			$timestamp    = mktime( 0, 0, 0,
				date( 'n', $first_update ),
				date( 'j', $first_update ),
				date( 'Y', $first_update )
			); // M, D, Y

			// Build data
			// -------------------------------------------
			$visitors    = array();
			$conversions = array();
			if ( is_object( $res ) ) {
				$visitors    = $res->get_visitors_history();
				$conversions = $res->get_conversions_history();
			}

			$the_count = count( $visitors );
			for( $i = 0; $i < ( 7 - $the_count ); ++$i ) {
				array_unshift( $visitors, 0 );
				array_unshift( $conversions, 0 );
				$timestamp = $timestamp - 86400; // substract one day
			}
			$year  = date( 'Y', $timestamp );
			$month = intval( date( 'n', $timestamp ) ) - 1;
			$day   = date( 'j', $timestamp );
			$date = sprintf( 'Date.UTC(%s, %s, %s)', $year, $month, $day );

			// Building labels (i18n)
			// -------------------------------------------
			$labels = array();
			$labels['title']       = __( 'Evolution of the Experiment', 'nelioab' );
			$labels['subtitle1']   = __( 'Click and drag in the plot area to zoom in', 'nelioab' );
			$labels['subtitle2']   = __( 'Pinch the chart to zoom in', 'nelioab' );
			$labels['yaxis']       = __( 'Page Views and Conversions', 'nelioab' );
			$labels['visitors']    = __( 'Page Views', 'nelioab' );
			$labels['conversions'] = __( 'Conversions', 'nelioab' );
			?>
			<script type="text/javascript">
				(function($) {
					var visitors    = <?php echo json_encode( $visitors ); ?>;
					var conversions = <?php echo json_encode( $conversions ); ?>;
					var labels      = <?php echo json_encode( $labels ); ?>;
					var startDate   = <?php echo $date; ?>;

					$("#nelioab-timeline").css('visibility', 'hidden');
					$("#nelioab-timeline").show();
					timelineGraphic = makeTimelineGraphic("nelioab-timeline", labels, visitors, conversions, startDate);
				})(jQuery);
			</script>
		<?php
		}

		private function array_division( $arr_numerator, $arr_divisor ) {
			$len = count( $arr_numerator );
			$aux = count( $arr_divisor );
			if ( $aux < $len )
				$len = $aux;

			$result = array();
			for ( $i = 0; $i < $len; ++$i ) {
				$num = $arr_numerator[$i];
				$div = $arr_divisor[$i];
				if ( $div < 1 )
					$aux = 0;
				elseif ( $num < $div )
					$aux = round( ($num / $div) * 100, 1 );
				else
					$aux = 100;
				array_push( $result, $aux );
			}

			return $result;
		}

		/**
		 *
		 *
		 */
		protected function print_timeline_for_alternatives_js() {

			$res = $this->results;

			// Start date
			// -------------------------------------------
			$first_update = time();
			if ( is_object( $res ) )
				$first_update = strtotime( $res->get_first_update() ); // This has to be a unixtimestamp...
			$timestamp    = mktime( 0, 0, 0,
				date( 'n', $first_update ),
				date( 'j', $first_update ),
				date( 'Y', $first_update )
			); // M, D, Y

			// Build data
			// -------------------------------------------
			$average      = array();
			$alternatives = array();
			if ( is_object( $res ) ) {
				$average = $this->array_division(
					$res->get_conversions_history(), $res->get_visitors_history() );

				$alternatives = array();
				foreach( $res->get_alternative_results() as $alt_res ) {
					array_push( $alternatives, $this->array_division(
						$alt_res->get_conversions_history(), $alt_res->get_visitors_history() ) );
				}
			}

			// Computing max value
			$max = 5;
			foreach ( $alternatives as $values )
				foreach ( $values as $val )
					if ( $val > $max )
						$max = $val;
			if ( $max > 100 )
				$max = 100;

			$the_count = count( $average );
			for( $i = 0; $i < ( 7 - $the_count ); ++$i ) {
				array_unshift( $average, 0 );
				$aux = array();
				foreach( $alternatives as $alt ) {
					array_unshift( $alt, 0 );
					array_push( $aux, $alt );
				}
				$alternatives = $aux;
				$timestamp = $timestamp - 86400; // substract one day
			}
			$year  = date( 'Y', $timestamp );
			$month = intval( date( 'n', $timestamp ) ) - 1;
			$day   = date( 'j', $timestamp );
			$date  = sprintf( 'Date.UTC(%s, %s, %s)', $year, $month, $day );

			// Building labels (i18n)
			// -------------------------------------------
			$labels = array();
			$labels['title']       = __( 'Evolution of the Experiment', 'nelioab' );
			$labels['subtitle1']   = __( 'Click and drag in the plot area to zoom in', 'nelioab' );
			$labels['subtitle2']   = __( 'Pinch the chart to zoom in', 'nelioab' );
			$labels['yaxis']       = __( 'Conversion Rate (%)', 'nelioab' );
			$labels['original']    = __( 'Original', 'nelioab' );
			$labels['alternative'] = __( 'Alternative %s', 'nelioab' );
			?>
			<script type="text/javascript">
				(function($) {
					var alternatives = <?php echo json_encode( $alternatives ); ?>;
					var labels       = <?php echo json_encode( $labels ); ?>;
					var startDate    = <?php echo $date; ?>;

					$("#nelioab-timeline").css('visibility', 'hidden');
					$("#nelioab-timeline").show();
					timelineGraphic = makeTimelinePerAlternativeGraphic("nelioab-timeline", labels, alternatives, startDate, <?php echo $max; ?>);
				})(jQuery);
			</script>
		<?php
		}

		abstract protected function get_labels_for_conversion_rate_js();
		protected function print_conversion_rate_js() {
			$alt_results = $this->results->get_alternative_results();

			// Build categories
			// -------------------------------------------
			$categories = array();
			$the_size = count( $alt_results );
			if ( $the_size > 0 ) {
				array_push( $categories, $alt_results[0]->get_name() );
				if ( $the_size > 3 ) {
					for ( $i = 1; $i < count( $alt_results ); $i++ )
						array_push( $categories, sprintf( __( 'Alt %s', 'nelioab' ), $i ) );
				}
				else {
					for ( $i = 1; $i < count( $alt_results ); $i++ )
						array_push( $categories, sprintf( __( 'Alternative %s', 'nelioab' ), $i ) );
				}
			}

			// Build data
			// -------------------------------------------
			$max_value = 0;
			$unique    = true;

			// Find the max conversion rate (if any)
			foreach( $alt_results as $aux ) {
				$rate = $aux->get_conversion_rate();
				if ( $rate > $max_value ) {
					$max_value = $rate;
					$unique    = true;
				}
				else if ( $rate == $max_value ) {
					$unique = false;
				}
			}

			// (if one or more alternatives have the same max value, none
			// has to be highlighted)
			if ( !$unique )
				$max_value = 105;

			// Retrieve the results of each alternative, highlighting the
			// one whose conversion rate equals $max_value
			$data = array();
			foreach( $alt_results as $aux ) {
				$rate = number_format( $aux->get_conversion_rate(), 2 );
				$color = 'color:"' . $this->colorscheme['primary'] . '"';
				if ( $rate == $max_value )
					$color = 'color:"#b0d66f"';
				$str = "{ y:$rate, $color }";
				array_push( $data, $str );
			}

			// Building labels (i18n)
			// -------------------------------------------
			$labels = $this->get_labels_for_conversion_rate_js();
			?>
			<script type="text/javascript">
				(function($) {
					var categories  = <?php echo json_encode( $categories ); ?>;
					var data        = [ <?php echo implode( ',', $data ); ?> ];
					var labels      = <?php echo json_encode( $labels ); ?>;

					$("#nelioab-conversion-rate").css('visibility', 'hidden');
					$("#nelioab-conversion-rate").show();
					convRateGraphic = makeConversionRateGraphic("nelioab-conversion-rate", labels, categories, data);
				})(jQuery);
			</script>
		<?php
		}

		abstract protected function get_labels_for_improvement_factor_js();
		protected function print_improvement_factor_js() {
			$alt_results = $this->results->get_alternative_results();

			// For the improvement factor, the original alternative is NOT used
			$alt_results = array_slice( $alt_results, 1 );

			// Build categories
			// -------------------------------------------
			$categories = array();
			$the_size = count( $alt_results );
			if ( $the_size > 0 ) {
				if ( $the_size > 2 ) {
					for ( $i = 0; $i < count( $alt_results ); $i++ )
						array_push( $categories, sprintf( __( 'Alt %s', 'nelioab' ), $i+1 ) );
				}
				else {
					for ( $i = 0; $i < count( $alt_results ); $i++ )
						array_push( $categories, sprintf( __( 'Alternative %s', 'nelioab' ), $i+1 ) );
				}
			}

			// Build data
			// -------------------------------------------
			$max_value = 0;
			$unique    = true;

			// Find the max improvement factor (if any)
			foreach( $alt_results as $aux ) {
				$factor = $aux->get_improvement_factor();
				if ( $factor > $max_value ) {
					$max_value = $factor;
					$unique    = true;
				}
				else if ( $factor == $max_value ) {
					$unique = false;
				}
			}

			// (if one or more alternatives have the same max value, none
			// has to be highlighted)
			if ( !$unique )
				$max_value = 105;

			// Retrieve the results of each alternative, highlighting the
			// one whose improvement factor equals $max_value
			$data = array();
			foreach( $alt_results as $aux ) {
				$factor = number_format( $aux->get_improvement_factor(), 2 );
				$color = 'color:"' . $this->colorscheme['primary'] . '"';
				if ( $factor == $max_value )
					$color = 'color:"#b0d66f"';
				if ( $factor < 0 )
					$color = 'color:"#cf4944"';
				$str = "{ y:$factor, $color }";
				array_push( $data, $str );
			}

			// Building labels (i18n)
			// -------------------------------------------
			$labels = $this->get_labels_for_improvement_factor_js();
			?>
			<script type="text/javascript">
				(function($) {
					var categories      = <?php echo json_encode( $categories ); ?>;
					var data            = [ <?php echo implode( ',', $data ); ?> ];
					var labels          = <?php echo json_encode( $labels ); ?>;

					$("#nelioab-improvement-factor").css('visibility', 'hidden');
					$("#nelioab-improvement-factor").show();
					improvFactorGraphic = makeImprovementFactorGraphic("nelioab-improvement-factor", labels, categories, data);
				})(jQuery);
			</script>
		<?php
		}

		abstract protected function get_labels_for_visitors_js();
		protected function print_visitors_js() {
			$alt_results = $this->results->get_alternative_results();

			// Build categories
			// -------------------------------------------
			$categories = array();
			$the_size = count( $alt_results );
			if ( $the_size > 0 ) {
				array_push( $categories, $alt_results[0]->get_name() );
				if ( $the_size > 2 ) {
					for ( $i = 1; $i < count( $alt_results ); $i++ )
						array_push( $categories, sprintf( __( 'Alt %s', 'nelioab' ), $i ) );
				}
				else {
					for ( $i = 1; $i < count( $alt_results ); $i++ )
						array_push( $categories, sprintf( __( 'Alternative %s', 'nelioab' ), $i ) );
				}
			}


			// Build data
			// -------------------------------------------
			$visitors    = array();
			$conversions = array();
			foreach( $alt_results as $aux ) {
				array_push( $visitors, $aux->get_num_of_visitors() );
				array_push( $conversions, $aux->get_num_of_conversions() );
			}

			// Building labels (i18n)
			// -------------------------------------------
			$labels = $this->get_labels_for_visitors_js();
			?>
			<script type="text/javascript">
				(function($) {
					var categories  = <?php echo json_encode( $categories ); ?>;
					var visitors    = <?php echo json_encode( $visitors ); ?>;
					var conversions = <?php echo json_encode( $conversions ); ?>;
					var labels      = <?php echo json_encode( $labels ); ?>;
					var colors      = <?php echo json_encode( array( $this->colorscheme['secondary'], $this->colorscheme['primary'] ) ); ?>;

					$("#nelioab-visitors").show();
					$("#nelioab-visitors").css('visibility', 'hidden');
					visitsGraphic   = makeVisitorsGraphic("nelioab-visitors", labels, categories, visitors, conversions, colors);
				})(jQuery);
			</script>

		<?php
		}

	}//NelioABAltExpProgressPage
}
