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


if ( !class_exists( 'NelioABAltExpProgressPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );

	abstract class NelioABAltExpProgressPage extends NelioABAdminAjaxPage {

		protected $exp;
		protected $results;
		protected $is_single_goal;
		protected $winner_label;
		protected $goals;
		protected $goal;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->exp          = null;
			$this->goal         = null;
			$this->results      = null;
		}

		public function set_experiment( $exp ) {
			$this->exp = $exp;
		}

		public function set_goals( $goals ) {
			$sorted = array();
			$aux    = array();
			foreach ( $goals as $goal ) {
				$this->autoset_goal_name( $goal );
				if ( $goal->is_main_goal() )
					array_push( $sorted, $goal );
				else
					array_push( $aux, $goal );
			}

			// Sort aux alphabetically...
			usort( $aux, array( 'NelioABAltExpProgressPage', 'sort_by_id' ) );
			usort( $aux, array( 'NelioABAltExpProgressPage', 'sort_by_name' ) );

			// And add them in sorted
			foreach ( $aux as $goal )
				array_push( $sorted, $goal );
			$this->goals = $sorted;

			// Finally, we select one by default...
			$this->results = null;
		}

		public static function sort_by_id( $a, $b ) {
			return $a->get_id() - $b->get_id();
		}

		public static function sort_by_name( $a, $b ) {
			return strcmp( $a->get_name(), $b->get_name() );
		}

		private function autoset_goal_name( $goal ) {
			if ( $goal->is_main_goal() ) {
				$goal->set_name( __( 'Aggregated Info', 'nelioab' ) );
				return;
			}
			$page = $goal->get_pages();
			$page = $page[0];
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

			if ( !$this->goal )
				return;

			try {
				$this->is_single_goal = count( $this->goal->get_pages() ) <= 1;
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

		private function print_single_goal_info() {
			$pages = $this->goal->get_pages();
			$page  = $pages[0];

			if ( $page->is_internal() )
				$this->print_single_goal_info_internal( $page );
			else
				$this->print_single_goal_info_external( $page );
		}

		private function print_single_goal_info_internal( $page ) {
			// Get goal post
			$post = false;
			$is_goal_page = false;
			$post = get_post( $page->get_reference() );
			$link = get_permalink( $page->get_reference() );
			$is_goal_page = ( $post->post_type == 'page' );

			// Create goal title
			if ( $is_goal_page )
				$label = __( 'Goal page not found.', 'nelioab' );
			else
				$label = __( 'Goal post not found.', 'nelioab' );
			if ( $post )
				$label = trim( $post->post_title );
			if ( strlen( $label ) == 0 ) {
				if ( $is_goal_page )
					$label = sprintf( __( 'Unnamed page «%s»', 'nelioab' ), $post->ID );
				else
					$label = sprintf( __( 'Unnamed post «%s»', 'nelioab' ), $post->ID );
			}
			if ( $post )
				$label = sprintf( '<a href="%s" target="_blank">%s</a>', $link, $label );
			$this->do_single_print( $label, $is_goal_page );
		}

		private function print_single_goal_info_external( $page ) {
			$label = sprintf( '<a href="%s" target="_blank">%s</a>',
				$page->get_reference(),
				$page->get_title() );
			$this->do_single_print( $label, true );
		}

		private function do_single_print( $label, $is_goal_page ) { ?>
			<h3><?php
			$aux  = $this->goal->get_pages();
			$page = false;
			if ( count( $aux ) > 0 )
				$page = $aux[0];
			if ( $page && $page->accepts_indirect_navigations() ) {
				if ( $is_goal_page )
					_e( 'Indirect Goal Page', 'nelioab' );
				else
					_e( 'Indirect Goal Post', 'nelioab' );
			}
			else {
				if ( $is_goal_page )
					_e( 'Direct Goal Page', 'nelioab' );
				else
					_e( 'Direct Goal Post', 'nelioab' );
			}
			?></h3><p><?php echo $label; ?></p><?php
		}

		private function print_multiple_goals_info() {
			$aux  = $this->goal->get_pages();
			$page = false;
			if ( count( $aux ) > 0 )
				$page = $aux[0];
			if ( $page && $page->accepts_indirect_navigations() ) { ?>
				<h3><?php _e( 'Indirect Goal Pages and Posts', 'nelioab' ); ?></h3><?php
			}
			else { ?>
				<h3><?php _e( 'Direct Goal Pages and Posts', 'nelioab' ); ?></h3><?php
			} ?>
			<ul style="margin-left:2em;"><?php
			$pages = $this->goal->get_pages();
			foreach ( $pages as $page ) {
				if ( $page->is_internal() ) {
					$post = get_post( $page->get_reference() );
					$label = sprintf( __( 'Page or post «%s» not found.', 'nelioab' ), $page->get_reference() );
					if ( $post ) {
						$name = trim( $post->post_title );
						$link = get_permalink( $post );
						if ( strlen( $name ) == 0 ) {
							if ( $is_goal_page )
								$name = sprintf( __( 'Unnamed page «%s»', 'nelioab' ), $post->ID );
							else
								$name = sprintf( __( 'Unnamed post «%s»', 'nelioab' ), $post->ID );
						}
						$label = sprintf( '<a href="%s" target="_blank">%s</a>', $link, $name );
					}
				}
				else {
					$link  = $page->get_reference();
					$name  = $page->get_title();
					$label = sprintf( '<a href="%s" target="_blank">%s</a>', $link, $name );
				}
				echo "<li>- $label</li>";
			}
			?></ul><?php
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

			$the_winner_conversion_rate = $this->get_winning_conversion_rate();
			if ( $the_winner_conversion_rate < 0 )
				$the_winner_conversion_rate = '&mdash;';
			else
				$the_winner_conversion_rate = number_format( $the_winner_conversion_rate, 2 );

			$this->winner_label = sprintf( ' alt-type-winner" title="%s"',
				sprintf( __( 'Wins with a %s%% confidence', 'nelioab'), $the_winner_confidence ) );

			// PRINTING RESULTS
			// ----------------------------------------------------------------
			?>

			<script type="text/javascript">
				var colors = Highcharts.getOptions().colors;
				var timelineGraphic;
				var visitsGraphic;
				var improvFactorGraphic;
				var convRateGraphic;

				function resizeGraphics() {
					var $ = jQuery;
					try {
						var defaultWidth = 480;
						if ( $("body").width() > 1200 )
							defaultWidth = ($("body").width() - 200) / 3 - 40;
						visitsGraphic.setSize( 320, 320, false);
						improvFactorGraphic.setSize( 320, 320, false);
						convRateGraphic.setSize( 320, 320, false);
						visitsGraphic.setSize( defaultWidth, 320, false);
						improvFactorGraphic.setSize( defaultWidth, 320, false);
						convRateGraphic.setSize( defaultWidth, 320, false);
					}
					catch (e) {}

					try {
						timelineGraphic.setSize( 320, 240, false );
						var infosumWidth  = 200;
						var timelineWidth = $("body").width() - infosumWidth - $("#adminmenuwrap").width() - 150;
						if ( timelineWidth < 400 ) {
							$("#summary-numbers").css("float", "none");
							$("#nelioab-timeline-graphic").css("float", "none");
							infosumWidth = 'auto';
							timelineWidth = $("body").width() - $("#adminmenuwrap").width() - 150;
						}
						else {
							$("#summary-numbers").css("float", "left");
							$("#nelioab-timeline-graphic").css("float", "left");
						}
						$("#summary-numbers").width( infosumWidth );
						timelineGraphic.setSize( timelineWidth, 260, false );
					}
					catch (e) {}
				}

				jQuery(window).resize(function() {
					resizeGraphics();
				});

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
						$lights_img   = '';
						$lights_label = '';
						if ( $the_winner == -1 ) {
							$lights_img = 'status-clock';
							$lights_label = __( 'There is not enough data to determine any winner yet', 'nelioab' );
						}
						else if ( $the_winner_confidence < 90 ) {
							$lights_img = 'status-star';
							$lights_label = __( 'There is a possible winner, but keep in mind the confidence does not reach 90%', 'nelioab' );
						}
						else {
							$lights_img = 'status-tick';
							$lights_label = __( 'There is a clear winner, with a confidence greater than 90%', 'nelioab' );
						}
					?><div class="<?php echo $lights_img; ?>"
						title="<?php echo $lights_label; ?>"></div> <span><?php _e( 'Summary', 'nelioab' ); ?></span></h3>
					<div class="inside">

						<div>
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
											sprintf( __( 'Alternative %s <small>(%s %%)</small>', 'nelioab' ),
												$the_winner, $the_winner_confidence ) );
									}
								}
								?>

								<h3><?php _e( 'Winner\'s Conversion Rate', 'nelioab' ); ?></h3>
								<p class="result"><?php printf( '%s %%', $the_winner_conversion_rate ); ?></p>

								<h3><?php _e( 'Original\'s Conversion Rate', 'nelioab' ); ?></h3>
								<p class="result"><?php printf( '%s %%', $originals_conversion_rate ); ?></p>

								<h3><?php _e( 'Conversions / Page Views', 'nelioab' ); ?></h3>
								<p class="result"><?php echo $total_conversions . ' / ' . $total_visitors; ?></p>

							</div>

							<div id="nelioab-timeline" class="nelioab-timeline-graphic">
							</div>
							<?php
								if ( isset( $this->results ) && $this->results->has_historic_info() )
									$this->print_timeline_for_alternatives_js();
								else
									$this->print_timeline_js();
							?>

							<div class="clear"></div>

						</div>

					</div>
				</div>
				<!-- ENDOF EXPERIMENT SUMMARY -->


				<!-- EXPERIMENT DETAILS -->
				<div id="exp-info">

					<h2><?php $this->print_experiment_details_title() ?></h2>
					<div id="exp-info-gen">
						<h3><?php _e( 'Name', 'nelioab' ); ?></h3>
							<p><?php echo $exp->get_name(); ?></p>
						<h3><?php _e( 'Description', 'nelioab' ); ?></h3>
							<p><?php echo $descr; ?></p>
						<?php
							if ( $this->is_single_goal )
								$this->print_single_goal_info();
							else
								$this->print_multiple_goals_info();
						?>

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
										jQuery("#loading-" + id).fadeOut(250);
										jQuery("#success-" + id).delay(500).fadeIn(200);
									}
								});
							}
							</script>

						<?php
						}

						$this->print_alternatives_block();

						if ( $this->exp->get_status() == NelioABExperimentStatus::RUNNING ) { ?>
							<div style="margin-top:1em;">
								<script>
								function forceStop() {
									smoothTransitions();
									jQuery.get(
										"<?php echo sprintf(
											'%s/admin.php?page=nelioab-experiments&action=progress&id=%s&exp_type=%s&forcestop=true',
											admin_url(), $this->exp->get_id(), $this->exp->get_type() ); ?>",
										function(data) {
											data = data.trim();
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
				</div>
				<!-- END OF EXPERIMENT DETAILS -->

			</div>


			<?php
			// If results are available, print them.
			if ( $res != null ) { ?>

				<!-- Summary graphics -->
				<div id="nelioab-visitors" class="nelioab-summary-graphic">
				</div>
				<?php $this->print_visitors_js(); ?>

				<div id="nelioab-conversion-rate" class="nelioab-summary-graphic">
				</div>
				<?php $this->print_conversion_rate_js(); ?>

				<div id="nelioab-improvement-factor" class="nelioab-summary-graphic">
				</div>
				<?php $this->print_improvement_factor_js(); ?>

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
						foreach( $res->get_gstats() as $g_stat ) {
							echo '<li>' . $g_stat->to_string() . '</li>';
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

		protected function get_winning_conversion_rate() {
			$res = $this->results;
			if ( $res == null )
				return -1;

			foreach ( $res->get_alternative_results() as $alt_result ) {
				if ( $this->is_winner( $alt_result->get_alt_id() ) )
					return $alt_result->get_conversion_rate();
			}

			return -1;
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
			$gtests = $res->get_gstats();

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

			$gtests = $res->get_gstats();
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

			timelineGraphic = makeTimelineGraphic("nelioab-timeline", labels, visitors, conversions, startDate);
			resizeGraphics();
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
				else
					$aux = round( ($num / $div) * 100 );
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

			timelineGraphic = makeTimelinePerAlternativeGraphic("nelioab-timeline", labels, alternatives, startDate);
			resizeGraphics();
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
				$color = 'color:colors[0]';
				if ( $rate == $max_value )
					$color = 'color:colors[2]';
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
			convRateGraphic = makeConversionRateGraphic("nelioab-conversion-rate", labels, categories, data);
			resizeGraphics();
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
				$color = 'color:colors[0]';
				if ( $factor == $max_value )
					$color = 'color:colors[2]';
				if ( $factor < 0 )
					$color = 'color:colors[8]';
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
			improvFactorGraphic = makeImprovementFactorGraphic("nelioab-improvement-factor", labels, categories, data);
			resizeGraphics();
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
			visitsGraphic   = makeVisitorsGraphic("nelioab-visitors", labels, categories, visitors, conversions);
			resizeGraphics();
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
		private $wp_pages;

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



?>
