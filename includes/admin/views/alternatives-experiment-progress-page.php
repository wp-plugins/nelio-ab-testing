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


if ( !class_exists( NelioABAlternativesExperimentProgressPage ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );
	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	class NelioABAlternativesExperimentProgressPage extends NelioABAdminAjaxPage {

		private $exp;
		private $results;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->exp = null;
			$this->results = null;
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
			if ( $aux )
				$goal = trim( $aux->post_title );
			if ( strlen( $goal ) == 0 )
				$goal = sprintf( __( 'No title available (id is %s)', 'nelioab' ), $aux->ID );

			// Original title
			$aux  = get_post( $exp->get_original() );
			$ori = sprintf( __( 'id is %s', 'nelioab' ), $aux->ID );
			if ( $aux )
				$ori = trim( $aux->post_title );


			// PRINTING RESULTS
			?>

			<style type="text/css">
				div#nelio-front {
					min-height: 320px;
					margin-bottom: 2em;
				}
				div.nelio-main {
					float: left;
					position: absolute;
					margin-right: 300px;
				}
				div.nelio-side {
					float: right;
					width: 320px;
				}
				.postbox h3 {
					cursor: default;
				}
				.nelioab-alts h2 {
					margin-bottom: 0px;
					padding-bottom: 0px;
				}
			</style>

			<script type="text/javascript">
				var colors = Highcharts.getOptions().colors;
				var timelineGraphic;
				var visitsGraphic;
				var improvFactorGraphic;
				var convRateGraphic;

				function resizeGraphics() {
					try {
						var defaultWidth = 480;
						var alts = <?php echo count( $this->exp->get_alternatives() ); ?>;
						if ( alts == 1 )
							defaultWidth = 360;
						else if ( alts == 2 )
							defaultWidth = 420;
						else
							defaultWidth = 480;
						visitsGraphic.setSize( 320, 320, false);
						timelineGraphic.setSize( 320, 320, false);
						improvFactorGraphic.setSize( 320, 320, false);
						convRateGraphic.setSize( 320, 320, false);
						var $ = jQuery;
						var w = $("#poststuff").width() - 80;
						timelineGraphic.setSize( w, 320, false);
						w = w - 320;
						if ( w < 320 )
							w = 320;
						if ( w > 1024 )
							w = 1024;
						visitsGraphic.setSize( w, 320, false);
						improvFactorGraphic.setSize( defaultWidth, 320, false);
						convRateGraphic.setSize( defaultWidth, 320, false);
					}
					catch (e) {}
				}
				
				jQuery(window).resize(function() {
					resizeGraphics();
				});

		</script>

			<!-- FRONT INFO BAR -->
			<div id="nelio-front">

				<!-- SIDE BOX -->
				<div class="nelio-side">
					<div id="info" class="postbox">
						<h3><span><?php _e( 'Experiment Information', 'nelioab' ); ?></span></h3>
						<div class="inside">

							<!-- Information -->
							<table style="vertical-align:top;">
								<tr>
									<td style="vertical-align:top;"><b><?php _e( 'Name', 'nelioab' ); ?>&nbsp;&nbsp;</b></td>
									<td><?php echo $exp->get_name(); ?></td>
								</tr>
								<tr>
									<td style="vertical-align:top;"><b><?php _e( 'Description', 'nelioab' ); ?>&nbsp;&nbsp;</b>
									<td><?php echo $descr; ?></td>
								</tr>
								<tr>
									<td style="vertical-align:top;"><b><?php _e( 'Goal Page', 'nelioab' ); ?>&nbsp;&nbsp;</b>
									<?php $link = get_permalink( $exp->get_conversion_post() ); ?>
									<td><?php echo sprintf( '<a href="%s" target="_blank">%s</a>', $link, $goal ); ?></td>
								</tr>

								<tr>
									<td style="vertical-align:top;"><b><?php _e( 'Original Page', 'nelioab' ); ?>&nbsp;&nbsp;</b>
									<td style="vertical-align:top;"><?php
										$link = get_permalink( $exp->get_original() );
										echo sprintf( '<a href="%s" target="_blank">%s</a>', $link, $ori );
									?></td>
								</tr>
								<tr>
									<td><b><?php if ( $i == 0 ) _e( 'Alternatives', 'nelioab' ); ?>&nbsp;&nbsp;</b></td>
									<td></td>
								</tr>
							</table>
							<ul style="margin:0px;padding:0px;list-style-type:circle;margin-left:2em;">
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
												_e( 'You are about to override the original page ' .
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
								}?>

								<?php
								foreach ( $exp->get_alternatives() as $alt ) {
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

									echo sprintf( '<li><a href="%s" target="_blank">%s</a>%s',
										$link, $alt->get_name(), $edit_link );

								}
								?>
							</ul>

							<?php
							if ( $this->exp->get_status() == NelioABExperimentStatus::RUNNING ) { ?>
								<div style="border-top: 1px solid #dfdfdf; padding: 10px; padding-bottom: 0px; text-align: right; margin-top:2em;">
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
									echo $this->make_js_button( __( 'Stop', 'nelioab' ), 'javascript:forceStop();' );
									?>
								</div>
							<?php
							} ?>

						</div>
					</div>
				</div>
				<!-- ENDOF SIDE BOX -->

			<?php
			// If results are available, print them.
			if ( $res != null ) {?>
					<!-- MAIN GRAPHIC -->
					<div class="nelio-main"><?php
						// If results are available, print them.
						if ( $res != null ) {?>
							<!-- Summary graphics -->
							<div id="nelioab-visitors" class="nelioab-summary-graphic">
							</div>
							<?php $this->print_visitors_js(); ?>
						<?php
						}
						?>
					</div>
					<!-- ENDOF MAIN GRAPHIC -->
	
				</div>
	
				<!-- Statistical Info -->
				<?php require_once( NELIOAB_UTILS_DIR . '/formatter.php' ); ?>
				<p><?php
					printf( __( 'Results computed at: %s', 'nelioab' ),
						NelioABFormatter::format_date( $res->get_last_update() )
					); ?></p>
				<h2><small><?php _e( 'Statistical Information', 'nelioab' ); ?></small></h2>
				<ul style="list-style-type:circle; margin-left:2em;">
				<?php
					foreach( $res->get_gstats() as $g_stat ) {
						echo '<li>' . $g_stat->to_string() . '</li>';
					}
				?>
				</ul>

				<!-- Timline graphic -->
				<h2><?php _e( 'Evolution of the Experiment', 'nelioab' ); ?></h2>
				<center>
				<div id="nelioab-timeline" class="nelioab-timeline-graphic">
				</div>
				<?php $this->print_timeline_js(); ?>
				</center>
	
				<!-- Summary graphics -->
				<h2><?php _e( 'Results', 'nelioab' ); ?></h2>
				<center>

				<div id="nelioab-conversion-rate" class="nelioab-summary-graphic">
				</div>
				<?php $this->print_conversion_rate_js(); ?>
	
				<div id="nelioab-improvement-factor" class="nelioab-summary-graphic">
				</div>
				<script type="text/javascript">
				</script>
				<?php $this->print_improvement_factor_js(); ?>

				</center>
	
				<?php
				$wp_list_table = new NelioABAltExpResultsTable( $res->get_alternative_results() );
				$wp_list_table->prepare_items();
				$wp_list_table->display();

			}
			// Otherwise, show a message stating that no data is available yet
			else {
				printf( '<h2>%s</h2>',
					__( 'Evolution of the Experiment', 'nelioab') );
				printf( '<p style="color:grey;font-size:120%%;">%s</p>',
					__( 'Oops! There are no results available yet. ' .
						'Please, check again later.', 'nelioab' ) );
			}
			
		}

		/**
		 *
		 *
		 */
		private function print_timeline_js() {

			$res = $this->results;

			// Start date
			// -------------------------------------------
			$first_update = strtotime( $res->get_first_update() ); // This has to be a unixtimestamp...
			$timestamp    = mktime( 0, 0, 0,
					date( 'n', $first_update ),
					date( 'j', $first_update ),
					date( 'Y', $first_update )
				); // M, D, Y

			// Build data
			// -------------------------------------------
			$visitors    = $res->get_visitors_history();
			$conversions = $res->get_conversions_history();

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
			foreach ( $alt_results as $aux )
				array_push( $categories, $aux->get_name() );

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
			$labels['title']    = __( 'Conversion Rate', 'nelioab' );
			$labels['subtitle'] = __( 'original and alternative pages', 'nelioab' );
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
			foreach ( $alt_results as $aux )
				array_push( $categories, $aux->get_name() );

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
			$labels['title']    = __( 'Improvement Factor', 'nelioab' );
			$labels['subtitle'] = __( 'with respect to the original page', 'nelioab' );
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
			foreach ( $alt_results as $aux )
				array_push( $categories, $aux->get_name() );


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
			$labels['subtitle']    = __( 'with respect to the original page', 'nelioab' );
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

	}//NelioABAlternativesExperimentProgressPage

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
