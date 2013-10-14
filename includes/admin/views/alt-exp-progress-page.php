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


if ( !class_exists( NelioABAltExpProgressPage ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	class NelioABAltExpProgressPage extends NelioABAdminAjaxPage {

		private $exp;
		private $results;

		private $is_ori_page;
		private $is_goal_page;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->exp          = null;
			$this->results      = null;
			$this->is_ori_page  = true;
			$this->is_goal_page = true;
		}

		public function set_experiment( $exp ) {
			$this->exp = $exp;
		}

		public function set_results( $results ) {
			$this->results = $results;
		}

		protected function do_render() {
			// SOME VARIABLES
			$exp  = $this->exp;
			$res  = $this->results;

			// Description of the experiment
			$descr = trim( $exp->get_description() );
			if ( empty( $descr ) )
				$descr = '-';

			// Goal title
			$goal = __( 'Page not found.', 'nelioab' );
			$aux  = get_post( $exp->get_conversion_post() );
			if ( $aux ) {
				$goal = trim( $aux->post_title );
				if ( $aux->post_type == 'post' )
					$this->is_goal_page = false;
			}
			if ( strlen( $goal ) == 0 )
				$goal = sprintf( __( 'No title available (id is %s)', 'nelioab' ), $aux->ID );

			// Original title
			$aux  = get_post( $exp->get_original() );
			$ori = sprintf( __( 'id is %s', 'nelioab' ), $aux->ID );
			if ( $aux ) {
				$ori = trim( $aux->post_title );
				if ( $aux->post_type == 'post' )
					$this->is_ori_page = false;
			}

			// Statistics
			$total_visitors    = 0;
			$total_conversions = 0;
			$conversion_rate   = '&mdash;';
			if ( $res ) {
				$total_visitors    = number_format( $res->get_total_visitors(), 0, '', ' ' );
				$total_conversions = number_format( $res->get_total_conversions(), 0, '', ' ' );
				$conversion_rate   = number_format( $res->get_total_conversion_rate(), 2 );
			}

			// Winner (if any) details
			$the_winner            = $this->who_wins();
			$the_winner_confidence = $this->get_winning_confidence();

			$the_winner_conversion_rate = $this->get_winning_conversion_rate();
			if ( $the_winner_conversion_rate < 0 )
				$the_winner_conversion_rate = '&mdash;';
			else
				$the_winner_conversion_rate = number_format( $the_winner_conversion_rate, 2 );

			$winner_label = sprintf( ' alt-type-winner" title="%s"',
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

			<!-- FRONT INFO BAR -->
			<div id="nelio-front">

				<!-- EXPERIMENT SUMMARY -->
				<div id="info-summary" class="postbox">
					<h3 style="cursor:auto;"><?php
						$lights_img   = '';
						$lights_label = '';
						if ( $the_winner == -1 ) {
							$lights_img = NELIOAB_ADMIN_ASSETS_URL . '/images/clock.png';
							$lights_label = __( 'There is not enough data to determine any winner yet', 'nelioab' );
						}
						else if ( $the_winner_confidence < 90 ) {
							$lights_img = NELIOAB_ADMIN_ASSETS_URL . '/images/star.png';
							$lights_label = __( 'There is a possible winner, but keep in mind the confidence does not reach 90%', 'nelioab' );
						}
						else {
							$lights_img = NELIOAB_ADMIN_ASSETS_URL . '/images/tick.png';
							$lights_label = __( 'There is a clear winner, with a confidence greater than 90%', 'nelioab' );
						}
					?><img style="margin-bottom:-4px;"
						src="<?php echo $lights_img; ?>"
						title="<?php echo $lights_label; ?>"/> <span><?php _e( 'Summary', 'nelioab' ); ?></span></h3>
					<div class="inside">

						<div>
							<div id="summary-numbers">
								<h3><?php _e( 'Conversions / Visitors', 'nelioab' ); ?></h3>
								<p class="result"><?php echo $total_conversions . ' / ' . $total_visitors; ?></p>

								<h3><?php _e( 'Average Conversion Rate', 'nelioab' ); ?></h3>
								<p class="result"><?php printf( '%s %%', $conversion_rate ); ?></p>

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
							</div>
	
							<div id="nelioab-timeline" class="nelioab-timeline-graphic">
							</div>
							<?php $this->print_timeline_js(); ?>

							<div class="clear"></div>

						</div>

					</div>
				</div>
				<!-- ENDOF EXPERIMENT SUMMARY -->


				<!-- EXPERIMENT DETAILS -->
				<div id="exp-info">

					<h2><?php _e( 'Experiment Details', 'nelioab' ); ?></h2>
					<div id="exp-info-gen">
						<h3><?php _e( 'Name', 'nelioab' ); ?></h3>
							<p><?php echo $exp->get_name(); ?></p>
						<h3><?php _e( 'Description', 'nelioab' ); ?></h3>
							<p><?php echo $descr; ?></p>
						<h3><?php
							if ( $this->is_goal_page )
								_e( 'Goal Page', 'nelioab' );
							else
								_e( 'Goal Post', 'nelioab' );
						?></h3>
							<?php $link = get_permalink( $exp->get_conversion_post() ); ?>
							<p><?php echo sprintf( '<a href="%s" target="_blank">%s</a>', $link, $goal ); ?></p>
					</div>
	
					<div id="exp-info-alts">
						<h3><?php _e( 'Alternatives', 'nelioab' ); ?></h3>
		
						<ul>
							<?php
							if ( $exp->get_status() == NelioABExperimentStatus::RUNNING ) {?>
								<script>
								function nelioab_confirm_editing() {
									return confirm( "<?php
										_e( 'Editing an alternative while the experiment is running ' .
										'may invalidate the results of the experiment. ' .
										'Do you really want to continue?', 'nelioab' );
									?>" );
								}
								</script>
							<?php
							}?>
		
							<?php
							if ( $exp->get_status() == NelioABExperimentStatus::FINISHED ) {?>
								<script>
								function nelioab_confirm_overriding_original(id) {
									if ( !confirm( "<?php
											if ( $this->is_ori_page )
												_e( 'You are about to override the original page ' .
												'with the contents of an alternative. ' .
												'Do you really want to continue?', 'nelioab' );
											else
												_e( 'You are about to override the original post ' .
												'with the contents of an alternative. ' .
												'Do you really want to continue?', 'nelioab' );
										?>" ) )
											return;
		
									jQuery(".apply-link").each(function() {
										jQuery(this).fadeOut(100);
									});
		
									jQuery("#loading-" + id).delay(120).fadeIn();
		
									jQuery.post(
										"<?php echo admin_url() . 'admin.php?page=nelioab-experiments&action=progress&apply-alternative=true&id=' . $exp->get_id(); ?>",
										{ 'original': <?php echo $exp->get_original(); ?>, 'alternative': id },
										function(data) {
											jQuery("#loading-" + id).fadeOut(250);
											jQuery("#success-" + id).delay(500).fadeIn(200);
										});
								}
								</script>
							<?php
							}
		
		
							// THE ORIGINAL
							// -----------------------------------------
							$link      = get_permalink( $exp->get_original() );
							$ori_label = __( 'Original', 'nelioab' );
		
							if ( $exp->get_status() == NelioABExperimentStatus::RUNNING ) {
								$edit_link = sprintf( ' <small>(<a href="javascript:if(nelioab_confirm_editing()) window.location.href=\'%s\'">%s</a>)</small></li>',
									admin_url() . '/post.php?post=' . $exp->get_original() . '&action=edit',
									__( 'Edit' ) );
							}
		
							if ( $this->is_winner( $exp->get_original() ) )
								$set_as_winner = $winner_label;
							else
								$set_as_winner = '';

							echo sprintf( '<li><span class="alt-type add-new-h2 %s">%s</span><a href="%s" target="_blank">%s</a>%s</li>',
								$set_as_winner, $ori_label, $link, $ori, $edit_link );
		
		
							// AND THE ALTERNATIVES
							// -----------------------------------------
		
							$i = 0;
							foreach ( $exp->get_alternatives() as $alt ) {
								$i++;
								$link      = get_permalink( $alt->get_post_id() );
								$edit_link = '';
								
								if ( $exp->get_status() == NelioABExperimentStatus::RUNNING ) {
									$edit_link = sprintf( ' <small>(<a href="javascript:if(nelioab_confirm_editing()) window.location.href=\'%s\'">%s</a>)</small></li>',
										admin_url() . '/post.php?post=' . $alt->get_post_id() . '&action=edit',
										__( 'Edit' ) );
								}
		
								if ( $exp->get_status() == NelioABExperimentStatus::FINISHED ) {
									$edit_link = sprintf(
										' <small id="success-%3$s" style="display:none;">(%1$s)</small>' .
										'<img id="loading-%3$s" style="height:10px;width:10px;display:none;" src="%2$s" />' .
										'<small class="apply-link">(<a href="javascript:nelioab_confirm_overriding_original(%3$s);">%4$s</a>)</small></li>',
										__( 'Done!', 'nelioab' ),
										NELIOAB_ASSETS_URL . '/images/loading-small.gif?' . NELIOAB_PLUGIN_VERSION,
										$alt->get_post_id(), __( 'Apply', 'nelioab' ) );
								}
		
								if ( $this->is_winner( $alt->get_post_id() ) )
									$set_as_winner = $winner_label;
								else
									$set_as_winner = '';

								$alt_label = sprintf( __( 'Alternative %s', 'nelioab' ), $i );
								echo sprintf( '<li><span class="alt-type add-new-h2 %s">%s</span><a href="%s" target="_blank">%s</a>%s',
									$set_as_winner, $alt_label, $link, $alt->get_name(), $edit_link );
		
							}
							?>
						</ul>

						<?php
						if ( $this->exp->get_status() == NelioABExperimentStatus::RUNNING ) { ?>
							<div style="margin-top:1em;">
								<script>
								function forceStop() {
									smoothTransitions();
									jQuery.get(
										"<?php echo sprintf(
											'%s/admin.php?page=nelioab-experiments&action=progress&id=%s&forcestop=true',
											admin_url(), $this->exp->get_id() ); ?>",
										function(data) {
											data = data.trim();
											console.log(data);
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
			if ( $res != null ) {?>

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
				$wp_list_table = new NelioABAltExpResultsTable( $res->get_alternative_results() );
				$wp_list_table->prepare_items();
				$wp_list_table->display();
				?>

				<!-- Statistical Info -->
				<h3><?php _e( 'Statistical Information', 'nelioab' ); ?></h3>
				<div style="margin-left:2em;">

					<p><?php
					if ( $exp->get_status() == NelioABExperimentStatus::RUNNING )
						_e( 'NelioAB is using the <a href="http://en.wikipedia.org/wiki/G-test">G-test statistic</a> for ' .
							'computing the results of this experiment. In the following, you may see the details: ',
							'nelioab' );
					else
						_e( 'NelioAB used the <a href="http://en.wikipedia.org/wiki/G-test">G-test statistic</a> for ' .
							'computing the results of this experiment. In the following, you may see the details: ',
							'nelioab' );
					?></p>

					<?php
					if ( $exp->get_status() == NelioABExperimentStatus::RUNNING ) {
						if ( $the_winner == 0 ) {
							if ( $this->is_ori_page )
								echo '<p><b>' . __( 'Right now, no alternative is beating the original page.', 'nelioab' ) . '</b></p>';
							else
								echo '<p><b>' . __( 'Right now, no alternative is beating the original post.', 'nelioab' ) . '</b></p>';
						}
						if ( $the_winner > 0 ) {
							if ( $this->is_ori_page )
								echo '<p><b>' . sprintf( __( 'Right now, alternative %s is better than the original page.', 'nelioab' ), $the_winner ) . '</b></p>';
							else
								echo '<p><b>' . sprintf( __( 'Right now, alternative %s is better than the original post.', 'nelioab' ), $the_winner ) . '</b></p>';
						}
					}
					else {
						if ( $the_winner == 0 ) {
							if ( $this->is_ori_page )
								echo '<p><b>' . __( 'No alternative was better the original page.', 'nelioab' ) . '</b></p>';
							else
								echo '<p><b>' . __( 'No alternative was better the original post.', 'nelioab' ) . '</b></p>';
						}
						if ( $the_winner > 0 ) {
							if ( $this->is_ori_page )
								echo '<p><b>' . sprintf( __( 'Alternative %s was better than the original page.', 'nelioab' ), $the_winner ) . '</b></p>';
							else
								echo '<p><b>' . sprintf( __( 'Alternative %s was better than the original post.', 'nelioab' ), $the_winner ) . '</b></p>';
						}
					}
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
				printf( '<p style="color:grey;font-size:120%%;">%s</p>',
					__( 'Oops! There are no results available yet. ' .
						'Please, check again later.', 'nelioab' ) );
			}
			
		}

		private function get_winning_conversion_rate() {
			$res = $this->results;
			if ( $res == null )
				return -1;

			foreach ( $res->get_alternative_results() as $alt_result ) {
				if ( $this->is_winner( $alt_result->get_post_id() ) )
					return $alt_result->get_conversion_rate();
			}

			return -1;
		}

		private function get_winning_confidence() {
			$bestg = $this->get_winning_gtest();
			if ( !$bestg )
				return -1;
			return number_format( $bestg->get_certainty(), 2 );
		}

		private function get_winning_gtest() {
			$res = $this->results;
			if ( $res == null )
				return false;

			$exp    = $this->exp;
			$gtests = $res->get_gstats();

			if ( count( $gtests ) == 0 )
				return false;

			$bestg = $gtests[count( $gtests ) - 1];
			if ( $bestg->is_original_the_best() ) {
				if ( $bestg->get_type() == NelioABGStats::WINNER )
					return $bestg;
			}
			else {
				$aux = null;
				foreach ( $gtests as $gtest )
					if ( $gtest->get_min() == $exp->get_original() )
						$aux = $gtest;
				if ( $aux )
					if ( $aux->get_type() == NelioABGStats::WINNER )
						return $aux;
			}
			
			return false;
		}

		private function is_winner( $id ) {
			$res = $this->results;
			if ( $res == null )
				return false;

			$gtests = $res->get_gstats();
			if ( count( $gtests ) == 0 )
				return false;

			$bestg = $gtests[count( $gtests ) - 1];
			if ( $bestg->get_max() == $id )
				if ( $bestg->get_type() == NelioABGStats::WINNER )
					return true;

			return false;
		}

		private function who_wins() {
			$exp = $this->exp;
			if ( $this->is_winner( $exp->get_original() ) )
				return 0;
			$i = 0;
			foreach ( $exp->get_alternatives() as $alt ) {
				$i++;
				if ( $this->is_winner( $alt->get_post_id() ) )
					return $i;
			}
			return -1;
		}

		/**
		 *
		 *
		 */
		private function print_timeline_js() {

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
			$labels['yaxis']       = __( 'Visitors and Conversions', 'nelioab' );
			$labels['visitors']    = __( 'Visitors', 'nelioab' );
			$labels['conversions'] = __( 'Conversions', 'nelioab' );
		?>
		<script type="text/javascript">
		(function($) {
			var categories  = <?php echo json_encode( $categories ); ?>;
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

		/**
		 *
		 *
		 */
		private function print_conversion_rate_js() {
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
			$labels = array();
			$labels['title']    = __( 'Conversion Rates', 'nelioab' );
			if ( $this->is_ori_page )
				$labels['subtitle'] = __( 'for the original and the alternative pages', 'nelioab' );
			else
				$labels['subtitle'] = __( 'for the original and the alternative posts', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Conversion Rate (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />Conversions: {1}%', 'nelioab' );
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

		/**
		 *
		 *
		 */
		private function print_improvement_factor_js() {
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
			$labels = array();
			$labels['title']    = __( 'Improvement Factors', 'nelioab' );
			if ( $this->is_ori_page )
				$labels['subtitle'] = __( 'with respect to the original page', 'nelioab' );
			else
				$labels['subtitle'] = __( 'with respect to the original post', 'nelioab' );
			$labels['xaxis']    = __( 'Alternatives', 'nelioab' );
			$labels['yaxis']    = __( 'Improvement (%)', 'nelioab' );
			$labels['column']   = __( '{0}%', 'nelioab' );
			$labels['detail']   = __( '<b>{0}</b><br />{1}% improvement', 'nelioab' );
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

		/**
		 *
		 *
		 */
		private function print_visitors_js() {
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
			$labels = array();
			$labels['title']       = __( 'Visitors and Conversions', 'nelioab' );
			if ( $this->is_ori_page )
				$labels['subtitle']    = __( 'for the original and the alternative pages', 'nelioab' );
			else
				$labels['subtitle']    = __( 'for the original and the alternative posts', 'nelioab' );
			$labels['xaxis']       = __( 'Alternatives', 'nelioab' );
			$labels['detail']      = __( 'Number of {series.name}: <b>{point.y}</b>', 'nelioab' );
			$labels['visitors']    = __( 'Visitors', 'nelioab' );
			$labels['conversions'] = __( 'Conversions', 'nelioab' );
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
	class NelioABAltExpResultsTable extends NelioABAdminTable {

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
				'visits'      => __( 'Number of Visits', 'nelioab' ),
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
