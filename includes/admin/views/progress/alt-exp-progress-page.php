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

		protected $exp;
		protected $results;
		protected $winner_label;
		protected $goals;
		protected $goal;
		protected $graphic_delay;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->exp          = null;
			$this->goal         = null;
			$this->results      = null;
			$this->graphic_delay = 500;
		}

		private function print_graphic_delay() {
			echo $this->graphic_delay;
			$this->graphic_delay += 500;
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
					$name = $post->post_title;
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
		protected abstract function print_js_function_for_post_data_overriding();

		private function print_actions_info() {
			$aux  = $this->goal->get_actions();
			if ( count( $aux ) <= 0 )
				return;
			?>
			<h3><?php _e( 'Conversion Actions', 'nelioab' ); ?></h3>
			<ul style="margin-left:2em;"><?php
			$actions = $this->goal->get_actions();
			foreach ( $actions as $action ) {
				switch ( $action->get_type() ) {
					case NelioABAction::PAGE_ACCESSED:
					case NelioABAction::POST_ACCESSED:
					case NelioABAction::EXTERNAL_PAGE_ACCESSED:
						$label = $this->print_page_accessed_action( $action );
						if ( $label )
							echo "<li>- $label</li>";
						continue;
					case NelioABAction::SUBMIT_CF7_FORM:
					case NelioABAction::SUBMIT_GRAVITY_FORM:
						$label = $this->print_form_submission_action( $action );
						if ( $label )
							echo "<li>- $label</li>";
						continue;
				}
			}
			?></ul><?php
		}

		private function print_page_accessed_action( $action ) {
			$indirect = $action->accepts_indirect_navigations();
			$result  = false;

			if ( $action->is_internal() ) {
				$post = get_post( $action->get_reference() );
				if ( $post ) {
					$name = trim( $post->post_title );
					$link = get_permalink( $post );
					if ( strlen( $name ) == 0 )
						$name = $post->ID;
					$link = sprintf( '<a href="%s" target="_blank">%s</a>', $link, $name );

					if ( $post->post_type == 'page' && $indirect )
						$result = sprintf(
							__( 'Indirect access to page %s', 'nelioab' ),
							$link );
					elseif ( $post->post_type == 'page' && !$indirect )
						$result = sprintf(
							__( 'Direct access to page %s', 'nelioab' ),
							$link );
					elseif ( $post->post_type == 'post' && $indirect )
						$result = sprintf(
							__( 'Indirect access to post %s', 'nelioab' ),
							$link );
					elseif ( $post->post_type == 'post' && !$indirect )
						$result = sprintf(
							__( 'Direct access to post %s', 'nelioab' ),
							$link );
				}
				else {
					if ( $indirect )
						$result = __( 'Indirect access to a page or post that does no longer exist', 'nelioab' );
					else
						$result = __( 'Direct access to a page or post that does no longer exist', 'nelioab' );
				}
			}
			else {
				$name = $action->get_title();
				$fake_link = '<span style="text-decoration:underline;" title="%2$s">%1$s</span>';
				$real_link = '<a href="%2$s" target="_blank">%1$s</a>';
				$special_case = false;
				switch ( $action->get_regex_mode() ) {
					case 'starts-with':
						$name = sprintf( $fake_link, $name, '%s' );
						$special_case = esc_html( sprintf( __( 'URL starts with "%s"', 'nelioab' ),
							$action->get_clean_reference() ) );
						break;
					case 'ends-with':
						$name = sprintf( $fake_link, $name, '%s' );
						$special_case = esc_html( sprintf( __( 'URL ends with "%s"', 'nelioab' ),
							$action->get_clean_reference() ) );
						break;
					case 'contains':
						$name = sprintf( $fake_link, $name, '%s' );
						$special_case = esc_html( sprintf( __( 'URL contains "%s"', 'nelioab' ),
							$action->get_clean_reference() ) );
						break;
					default:
						$name = sprintf( $real_link, $name, $action->get_reference() );
				}

				if ( $indirect ) {
					$result = sprintf(
						__( 'Indirect access to external page %1$s', 'nelioab' ),
						$name );
				}
				else {
					$result = sprintf(
						__( 'Direct access to external page %1$s', 'nelioab' ),
						$name );
				}

				if ( $special_case ) {
					$result = sprintf( $result, $special_case );
				}
			}
			return $result;
		}

		private function print_form_submission_action( $action ) {
			$form_id = $action->get_form_id();
			$name    = false;
			$result  = false;

			$cf7  = is_plugin_active( 'contact-form-7/wp-contact-form-7.php' );
			$gf   = is_plugin_active( 'gravityforms/gravityforms.php' );
			switch ( $action->get_type() ) {

				case NelioABAction::SUBMIT_CF7_FORM:
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
					if ( $name ) {
						$result = sprintf(
							__( 'Submission of form %1$s %2$s', 'nelioab' ),
							sprintf( '<a href="%2$s" target="_blank">%1$s</a>', $name, $link ),
							$mode
						);
					}
					else {
						$result = sprintf(
							__( 'Submission of an unknown Contact Form 7 %s' ),
							$mode );
					}
					break;

				case NelioABAction::SUBMIT_GRAVITY_FORM:
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
					if ( $name ) {
						$result = sprintf(
							__( 'Submission of form %1$s %2$s', 'nelioab' ),
							sprintf( '<a href="%2$s" target="_blank">%1$s</a>', $name, $link ),
							$mode
						);
					}
					else {
						$result = sprintf(
							__( 'Submission of an unknown Gravity Form %s', 'nelioab' ),
							$mode );
					}
					break;
			}

			return $result;
		}

		protected function do_render() {
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
				$total_visitors    = number_format( $res->get_total_visitors(), 0, '', ' ' );
				$total_conversions = number_format( $res->get_total_conversions(), 0, '', ' ' );
				$conversion_rate   = number_format( $res->get_total_conversion_rate(), 2 );
				$aux = $res->get_alternative_results();
				$originals_conversion_rate = number_format( $aux[0]->get_conversion_rate(), 2 );
			}

			// Winner (if any) details
			$the_winner            = $this->who_wins();
			$the_winner_confidence = $this->get_winning_confidence();

			$best_alt_conversion_rate = $this->get_best_alt_conversion_rate();
			if ( $best_alt_conversion_rate < 0 )
				$best_alt_conversion_rate = '&mdash;';
			else
				$best_alt_conversion_rate = number_format( $best_alt_conversion_rate, 2 );

			$this->winner_label = sprintf( ' alt-type-winner" title="%s"',
				sprintf( __( 'Wins with a %s%% confidence', 'nelioab'), $the_winner_confidence ) );

			// PRINTING RESULTS
			// ----------------------------------------------------------------
			?>

			<script type="text/javascript">
				var timelineGraphic;
				var visitsGraphic;
				var improvFactorGraphic;
				var convRateGraphic;
			</script>

			<?php
			if ( count( $this->goals ) > 1 ) { ?>
				<h3 class="nav-tab-wrapper" style="margin:0em;padding:0em;padding-left:2em;margin-bottom:2em;">
				<?php

				$this_goal_id = $this->goal->get_id();
				foreach ( $this->goals as $goal ) {
					$name   = $goal->get_name();
					$params = array( 'goal' => $goal->get_id() );
					$link   = add_query_arg( $params, $_SERVER['HTTP_REFERER'] );
					if ( $goal->get_id() == $this_goal_id )
						echo "<span href=\"$link\" class=\"nav-tab nav-tab-active\">$name</span>";
					else
						echo "<a href=\"$link\" class=\"nav-tab\">$name</a>";
				}

				?>
				</h3><?php
			}
			?>

			<!-- FRONT INFO BAR -->
			<div id="nelio-front">

				<!-- EXPERIMENT SUMMARY -->
				<div id="info-summary" class="postbox">
					<h3 style="cursor:auto;"><?php
						if ( $res )
							$light = NelioABGTest::generate_status_light( $res->get_summary_status() );
						else
							$light = NelioABGTest::generate_status_light( false );
						echo $light;
					?> <span><?php _e( 'Summary', 'nelioab' ); ?></span></h3>
					<div class="inside">

						<div id="summary-content">
							<div id="summary-numbers">
								<h3><?php
									$conf_label = ' (' . __( 'Confidence', 'nelioab' ) . ')';
									if ( $the_winner == -1 )
										$conf_label = '';
									if ( $exp->get_status() == NelioABExperimentStatus::RUNNING )
										echo __( 'Current Winner', 'nelioab' ) . $conf_label;
									else
										echo __( 'Winner', 'nelioab' ) . $conf_label;
								?></h3>
								<?php
								if ( $the_winner == -1 ) {
									printf ( '<p class="result">%s</p>', __( 'None', 'nelioab' ) );
								}
								else {
									if ( $the_winner == 0 ) {
										printf ( '<p class="result">%s <small>(%s %%)</small></p>',
											__( 'Original', 'nelioab' ), $the_winner_confidence );
									}
									else {
										printf ( '<p class="result">%s</p>',
											sprintf( __( 'Alternative %1$s <small>(%2$s %%)</small>', 'nelioab' ),
												$the_winner, $the_winner_confidence ) );
									}
								}
								?>

								<h3><?php _e( 'Original\'s Conversion Rate', 'nelioab' ); ?></h3>
								<p class="result"><?php printf( '%s %%', $originals_conversion_rate ); ?></p>

								<h3><?php _e( 'Best Alt\'s Conversion Rate', 'nelioab' ); ?></h3>
								<p class="result"><?php printf( '%s %%', $best_alt_conversion_rate ); ?></p>

								<h3><?php _e( 'Conversions / Page Views', 'nelioab' ); ?></h3>
								<p class="result"><?php echo $total_conversions . ' / ' . $total_visitors; ?></p>

							</div>

							<div id="nelioab-timeline" class="nelioab-timeline-graphic">
							</div>
							<?php
								if ( isset( $this->results ) && !$this->results->has_historic_info() )
									$this->print_timeline_js();
								else
									$this->print_timeline_for_alternatives_js();
							?>

							<div class="clear"></div>

						</div>

					</div>

					<?php
					if ( $this->exp->get_status() == NelioABExperimentStatus::RUNNING ) { ?>
						<div style="margin:0.5em;margin-top:0em;text-align:right;">
							<script>
							function forceStop() {<?php
								$msg = __( 'You are about to stop an experiment. Once the experiment is stopped, you cannot resume it. Do you want to continue?', 'nelioab' );
								$msg = str_replace( '"', '\\"', $msg ); ?>
								if ( !confirm( "<?php echo $msg; ?>") ) return;
								smoothTransitions();
								jQuery.get(
									"<?php
										echo admin_url( sprintf(
											'admin.php?page=nelioab-experiments&action=progress&id=%s&exp_type=%s&forcestop=true',
											$this->exp->get_id(), $this->exp->get_type() )
										); ?>",
									function(data) {
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
							echo $this->make_js_button( __( 'Stop Experiment Now', 'nelioab' ), 'javascript:forceStop();' );
							?>
						</div>
					<?php
					} ?>

				</div>
				<!-- ENDOF EXPERIMENT SUMMARY -->


				<!-- EXPERIMENT DETAILS -->
				<div id="exp-info">

					<h2><?php $this->print_experiment_details_title() ?></h2>
					<div class="nelioab-row">
						<div class="nelioab-first-col">
							<div id="exp-info-gen">
								<h3><?php _e( 'Name', 'nelioab' ); ?></h3>
									<p><?php echo $exp->get_name(); ?></p>
								<h3><?php _e( 'Description', 'nelioab' ); ?></h3>
									<p><?php echo $descr; ?></p>
							</div>

							<div id="exp-info-alts">
								<h3><?php _e( 'Alternatives', 'nelioab' ); ?></h3>

								<?php
								if ( $exp->get_status() == NelioABExperimentStatus::RUNNING ) { ?>
									<script>
									function nelioab_confirm_editing() {
										return confirm( "<?php
											_e( 'Editing an alternative while the experiment is running may invalidate the results of the experiment. Do you really want to continue?', 'nelioab' );
										?>" );
									}
									</script>
								<?php
								} ?>

								<?php
								if ( $exp->get_status() == NelioABExperimentStatus::FINISHED ) { ?>
									<script>
									<?php
									$this->print_js_function_for_post_data_overriding();
									?>

									function nelioab_show_the_dialog_for_overriding(id) {
										$ = jQuery;
										$(function() {
											$("#dialog-modal").dialog({
												title: '<?php echo __( 'Override Original', 'nelioab' ); ?>',
												resizable: false,
												width: 500,
												modal: true,
												buttons: {
													"OK": function() {
														$(this).dialog("close");
														nelioab_do_override(id);
													},
													"Cancel": function() {
														$(this).dialog("close");
													}
												}
											});
										});
									}

									function nelioab_do_override(id) {
										jQuery(".apply-link").each(function() {
											jQuery(this).fadeOut(100);
										});

										jQuery("#loading-" + id).delay(120).fadeIn();

										jQuery.ajax({
											url: jQuery("#apply_alternative").attr("action"),
											type: 'post',
											data: jQuery('#apply_alternative').serialize(),
											success: function(data) {
												jQuery("#loading-" + id).delay(120).fadeOut(250);
												jQuery("#success-" + id).delay(500).fadeIn(200).delay(10000).fadeOut(200);
											}
										});
									}
									</script>
								<?php
								}
								$this->print_alternatives_block();
								?>
							</div>
						</div>

						<div class="nelioab-second-col">
							<div id="exp-info-goal-actions">
								<?php
									$this->print_actions_info();
								?>
							</div>
						</div>
					</div>

				</div>
				<!-- END OF EXPERIMENT DETAILS -->

			</div>


			<?php
			// If results are available, print them.
			if ( $res != null ) { ?>

				<!-- Summary graphics -->
				<div style="text-align:center;">
					<div id="nelioab-visitors" class="nelioab-summary-graphic">
					</div>
					<?php $this->print_visitors_js(); ?>
	
					<div id="nelioab-convrate-and-impfactor">
						<div id="nelioab-conversion-rate" class="nelioab-summary-graphic">
						</div>
						<?php $this->print_conversion_rate_js(); ?>
		
						<div id="nelioab-improvement-factor" class="nelioab-summary-graphic">
						</div>
						<?php $this->print_improvement_factor_js(); ?>
					</div>
				</div>

				<?php
				$wp_list_table = new NelioABAltExpProgressTable( $res->get_alternative_results() );
				$wp_list_table->prepare_items();
				$wp_list_table->display();
				?>

				<!-- Statistical Info -->
				<h3><?php _e( 'Statistical Information', 'nelioab' ); ?></h3>
				<div style="margin-left:2em;">

					<p><?php
					if ( $exp->get_status() == NelioABExperimentStatus::RUNNING )
						_e( 'NelioAB is using the <a href="http://en.wikipedia.org/wiki/G-test">G-test statistic</a> for computing the results of this experiment. In the following, you may see the details: ', 'nelioab' );
					else
						_e( 'NelioAB used the <a href="http://en.wikipedia.org/wiki/G-test">G-test statistic</a> for computing the results of this experiment. In the following, you may see the details: ', 'nelioab' );
					?></p>

					<?php
					$this->print_winner_info();
					?>

					<ul style="list-style-type:circle; margin-left:2em;">
					<?php
						foreach( $res->get_gtests() as $gtest ) {
							echo '<li>' . $gtest->to_string() . '</li>';
						}
					?>
					</ul>

				</div>

				<?php require_once( NELIOAB_UTILS_DIR . '/formatter.php' ); ?>
				<p style="text-align:right;margin-top:3em;color:gray;"><?php
					printf( __( 'Last Update: %s', 'nelioab' ),
						NelioABFormatter::format_date( $res->get_last_update() )
					); ?></p>

				<?php
			}
			// Otherwise, show a message stating that no data is available yet
			else {
				printf( '<p style="color:#555;font-size:120%%;">%s</p>',
					__( 'There are no results available yet. Please, be patient until we collect more data. It might take up to two hours to get your first results.', 'nelioab' ) );
			}
		}

		protected function print_alternatives_block() {
			echo '<ul>';
			$this->print_the_original_alternative();
			$this->print_the_real_alternatives();
			echo '</ul>';
		}

		abstract protected function print_experiment_details_title();
		abstract protected function print_the_original_alternative();
		abstract protected function print_the_real_alternatives();
		abstract protected function print_winner_info();

		protected function trunk( $in ) {
			return strlen( $in ) > 50 ? substr( $in, 0, 50 ) . "..." : $in;
		}

		protected function get_best_alt_conversion_rate() {
			$res = $this->results;
			if ( $res == null )
				return -1;
			$best = 0;
			$alts = $res->get_alternative_results();
			for ( $i = 1; $i < count( $alts ); ++$i ) {
				$alt_result = $alts[$i];
				$conv = $alt_result->get_conversion_rate();
				if ( $best < $conv )
					$best = $conv;
			}
			return $best;
		}

		protected function get_winning_confidence() {
			$bestg = $this->get_winning_gtest();
			if ( !$bestg )
				return -1;
			return number_format( $bestg->get_certainty(), 2 );
		}

		protected function get_winning_gtest() {
			$res = $this->results;
			if ( $res == null )
				return false;

			$exp    = $this->exp;
			$gtests = $res->get_gtests();

			if ( count( $gtests ) == 0 )
				return false;

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
			$res = $this->results;
			if ( $res == null )
				return false;

			$gtests = $res->get_gtests();
			if ( count( $gtests ) == 0 )
				return false;

			$bestg = $gtests[count( $gtests ) - 1];
			if ( $bestg->get_max() == $id )
				if ( $bestg->get_type() == NelioABGTest::WINNER )
					return true;

			return false;
		}

		protected function who_wins() {
			$exp = $this->exp;
			if ( $this->is_winner( $this->get_original_value() ) )
				return 0;
			$i = 0;
			foreach ( $exp->get_alternatives() as $alt ) {
				$i++;
				if ( $this->is_winner( $alt->get_value() ) )
					return $i;
			}
			return -1;
		}

		/**
		 *
		 *
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
			var aux = setTimeout( function() {
				timelineGraphic = makeTimelineGraphic("nelioab-timeline", labels, visitors, conversions, startDate);
			}, <?php echo $this->print_graphic_delay(); ?> );
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
					$aux = round( ($num / $div) * 100 );
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
			$labels['yaxis']       = __( 'Conversion Rate', 'nelioab' );
			$labels['original']    = __( 'Original', 'nelioab' );
			$labels['alternative'] = __( 'Alternative %s', 'nelioab' );
		?>
		<script type="text/javascript">
		(function($) {
			var alternatives = <?php echo json_encode( $alternatives ); ?>;
			var labels       = <?php echo json_encode( $labels ); ?>;
			var startDate    = <?php echo $date; ?>;

			var aux = setTimeout( function() {
				timelineGraphic = makeTimelinePerAlternativeGraphic("nelioab-timeline", labels, alternatives, startDate);
			}, <?php echo $this->print_graphic_delay(); ?> );
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
				$rate = $aux->get_conversion_rate();
				$color = 'color:"#7CB5EC"';
				if ( $rate == $max_value )
					$color = 'color:"#81D24A"';
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

			var aux = setTimeout( function() {
				convRateGraphic = makeConversionRateGraphic("nelioab-conversion-rate", labels, categories, data);
			}, <?php echo $this->print_graphic_delay(); ?> );
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
				$factor = $aux->get_improvement_factor();
				$color = 'color:"#7CB5EC"';
				if ( $factor == $max_value )
					$color = 'color:"#81D24A"';
				if ( $factor < 0 )
					$color = 'color:"#DD3D36"';
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
			var aux = setTimeout( function() {
				improvFactorGraphic = makeImprovementFactorGraphic("nelioab-improvement-factor", labels, categories, data);
			}, <?php echo $this->print_graphic_delay(); ?> );
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
			var aux = setTimeout( function() {
				visitsGraphic   = makeVisitorsGraphic("nelioab-visitors", labels, categories, visitors, conversions);
			}, <?php echo $this->print_graphic_delay(); ?> );
		})(jQuery);
		</script>

		<?php
		}

	}//NelioABAltExpProgressPage

	require_once( NELIOAB_UTILS_DIR . '/admin-table.php' );
	class NelioABAltExpProgressTable extends NelioABAdminTable {

		private $form_name;
		private $show_new_form;
		private $copying_content;

		function __construct( $items ){
				parent::__construct( array(
				'singular' => __( 'result', 'nelioab' ),
				'plural'   => __( 'results', 'nelioab' ),
				'ajax'     => false
			)	);
			$this->set_items( $items );
		}

		public function get_columns(){
			return array(
				'name'        => __( 'Name', 'nelioab' ),
				'visits'      => __( 'Number of Page Views', 'nelioab' ),
				'conversions' => __( 'Number of Conversions', 'nelioab' ),
				'rate'        => __( 'Conversion Rate', 'nelioab' ),
				'improvement' => __( 'Improvement Factor', 'nelioab' ),
			);
		}

		function get_display_functions() {
			return array(
				'name'        => 'get_name',
				'visits'      => 'get_num_of_visitors',
				'conversions' => 'get_num_of_conversions',
				'rate'        => 'get_conversion_rate_text',
				'improvement' => 'get_improvement_factor_text',
			);
		}

	}// NelioABExperimentsTable

}

