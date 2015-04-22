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
 * FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <http://www.gnu.org/licenses/>.
 */


if ( !class_exists( 'NelioABDashboardPage' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );
	class NelioABDashboardPage extends NelioABAdminAjaxPage {

		private $graphic_delay;
		private $experiments;
		private $quota;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->add_title_action( __( 'New Experiment', 'nelioab' ), '?page=nelioab-add-experiment' );
			$this->experiments = array();
			$this->quota = array(
				'used'  => 0,
				'total' => 7500,
			);
			$this->graphic_delay = 500;
		}

		public function set_summary( $summary ) {
			$this->experiments = $summary['exps'];
			$this->quota = $summary['quota'];
		}

		public function do_render() {
			echo '<div id="post-body" class="metabox-holder columns-2">';
			echo '<div id="post-body-content">';
			if ( count( $this->experiments ) == 0 ) {
				echo '<center>';
				echo sprintf( '<img src="%s" alt="%s" />',
					nelioab_asset_link( '/admin/images/happy.png' ),
					__( 'Happy smile.', 'nelioab' )
				);
				echo '<h2 style="max-width:750px;">';
				echo sprintf(
					__( 'Hi! You\'re now in Nelio\'s Dashboard, where you\'ll find all relevant information about your running experiments. Right now, however, there are none...<br><a href="%s">Create one now!</a>', 'nelioab' ),
					'admin.php?page=nelioab-add-experiment' );
				echo '</h2>';
				echo '</center>';
			}
			else {
				echo '<h2>' . __( 'Running Experiments', 'nelioab' ) . '</h2>';
				$this->print_cards();
			}
			echo '</div>'; ?>
			<div id="postbox-container-1" class="postbox-container" style="overflow:hidden;"><?php // TODO remove the style ?>
				<h2><?php _e( 'Account Usage', 'nelioab' ); ?></h2>
				<?php
				require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
				$cs = NelioABWpHelper::get_current_colorscheme();
				?>

				<div class="numbers" style="height:40px;">
					<div class="left" style="float:left; width:60%;">
						<span style="font-weight:bold;"><?php _e( 'QUOTA USED', 'nelioab' ); ?></span><br>
						<span style="color:<?php echo $cs['primary']; ?>; font-size:10px;"><?php
							echo number_format_i18n( $this->quota['used'], 0 );
						?></span><span style="font-size:10px;"> / <?php
							echo number_format_i18n( $this->quota['total'], 0 );
						?></span>
					</div>
					<div class="right" style="font-size:32px; text-align:right; float:right; width:30%; padding-right:5%; margin-top:8px; opacity:0.7;">
						<span><?php
							if ( $this->quota['total'] > 0 )
								$perc = ( $this->quota['used'] / $this->quota['total'] ) * 100;
							else
								$perc = 0;
							$decs = 1;
							if ( 100 == $perc )
								$decs = 0;
							echo number_format( $perc, $decs );
						?>%</span>
					</div>
				</div>

				<div class="progress-bar-container" style="background:none;border:2px solid rgba(0,0,0,0.1); width:95%; margin:0px; height:20px;">
					<div class="progress-bar" style="height:20px;background-color:<?php
						echo $cs['primary'];
					?>;width:<?php echo $perc; ?>%;"></div>
				</div>
			</div>
			<?php
			echo '</div>'; // #post-body
		}

		public function print_cards() {
			// The following function is used by ALT_EXP cards ?>
			<script>
				function drawGraphic( id, data, label, baseColor ) {
					if ( baseColor == undefined )
						baseColor = '#CCCCCC';
					var $ = jQuery;
					Highcharts.getOptions().plotOptions.pie.colors = (function () {
					var divider = 25;
					var numOfAlts = data.length;
					if ( numOfAlts < 10 ) divider = 20
					if ( numOfAlts < 8 ) divider = 15
					if ( numOfAlts < 4 ) divider = 6
					var colors = [],
						base = baseColor,
						i
						for (i = 0; i < 10; i++)
							colors.push(Highcharts.Color(base).brighten(i / divider).get());
						return colors;
					}());

					// Build the chart
					var chart = $('#' + id).highcharts({
						chart: {
							plotBackgroundColor: null,
							plotBorderWidth: null,
							plotShadow: false,
							margin: [0, 0, 0, 0],
						},
						title: { text:'' },
						exporting: { enabled: false },
						tooltip: {
							pointFormat: '{series.name}: <b>{point.y:.0f}</b>'
						},
						plotOptions: {
							pie: {
								allowPointSelect: false,
								cursor: 'pointer',
								dataLabels: { enabled: false },
							}
						},
						series: [{
							type: 'pie',
							name: label,
							data: data
						}],
					});
				}
			</script><?php

			include_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
			foreach ( $this->experiments as $exp ) {
				switch( $exp->get_type() ) {
					case NelioABExperiment::HEATMAP_EXP:
						$progress_url = str_replace( 'https://', 'http://',
							admin_url( 'admin.php?nelioab-page=heatmaps&id=%1$s&exp_type=%2$s' ) );
						$this->print_linked_beautiful_box(
							$exp->get_id(),
							$this->get_beautiful_title( $exp ),
							sprintf( $progress_url, $exp->get_id(), $exp->get_type() ),
							array( &$this, 'print_heatmap_exp_card', array( $exp ) ) );
						break;
					default:
						$this->print_linked_beautiful_box(
							$exp->get_id(),
							$this->get_beautiful_title( $exp ),
							sprintf(
									'?page=nelioab-experiments&action=progress&id=%1$s&exp_type=%2$s',
									$exp->get_id(),
									$exp->get_type()
								),
							array( &$this, 'print_alt_exp_card', array( $exp ) ) );
				}
			}

		}

		public function get_beautiful_title( $exp ) {
			$img = '<div class="tab-type tab-type-%1$s" alt="%2$s" title="%2$s"></div>';
			switch ( $exp->get_type() ) {
				case NelioABExperiment::PAGE_ALT_EXP:
					try {
						$page_on_front = get_option( 'page_on_front' );
						$aux = $exp->get_alternative_info();
						if ( $page_on_front == $aux[0]['id'] )
							$img = sprintf( $img, 'landing-page', __( 'Landing Page', 'nelioab' ) );
						else
							$img = sprintf( $img, 'page', __( 'Page', 'nelioab' ) );
					}
					catch ( Exception $e ) {
						$img = sprintf( $img, 'page', __( 'Page', 'nelioab' ) );
					}
					break;
				case NelioABExperiment::POST_ALT_EXP:
					$img = sprintf( $img, 'post', __( 'Post', 'nelioab' ) );
					break;
				case NelioABExperiment::HEADLINE_ALT_EXP:
					$img = sprintf( $img, 'title', __( 'Headline', 'nelioab' ) );
					break;
				case NelioABExperiment::THEME_ALT_EXP:
					$img = sprintf( $img, 'theme', __( 'Theme', 'nelioab' ) );
					break;
				case NelioABExperiment::CSS_ALT_EXP:
					$img = sprintf( $img, 'css', __( 'CSS', 'nelioab' ) );
					break;
				case NelioABExperiment::HEATMAP_EXP:
					$img = sprintf( $img, 'heatmap', __( 'Heatmap', 'nelioab' ) );
					break;
				case NelioABExperiment::WIDGET_ALT_EXP:
					$img = sprintf( $img, 'widget', __( 'Widget', 'nelioab' ) );
					break;
				case NelioABExperiment::MENU_ALT_EXP:
					$img = sprintf( $img, 'menu', __( 'Menu', 'nelioab' ) );
					break;
				default:
					$img = '';
			}

			if ( $exp->has_result_status() )
				$light = NelioABGTest::generate_status_light( $exp->get_result_status() );
			else
				$light = '';

			$title = '';
			$name = $exp->get_name();
			if ( strlen( $name ) > 50 ) {
				$title = ' title="' . esc_html( $name ) . '"';
				$name = substr( $name, 0, 50 ) . '...';
			}
			$name = '<span class="exp-title"'. $title .'>' . $name . '</span>';
			$status = '<span id="info-summary">' . $light . '</span>';

			return $img . $name . $status;
		}

		public function print_alt_exp_card( $exp ) { ?>
			<div class="row padding-top">
				<div class="col col-4">
					<div class="row data padding-left">
						<span class="value"><?php echo $exp->get_total_visitors(); ?></span>
						<span class="label"><?php _e( 'Page Views', 'nelioab' ); ?></span>
					</div>
					<div class="row data padding-left">
						<span class="value"><?php echo count( $exp->get_alternative_info() ); ?></span>
						<span class="label"><?php _e( 'Alternatives', 'nelioab' ); ?></span>
					</div>
				</div>
				<div class="col col-4">
					<div class="row data">
						<?php
							$val = $exp->get_original_conversion_rate();
							$val = number_format_i18n( $val, 2 );
							$val = preg_replace( '/(...)$/', '<span class="decimals">$1</span>', $val );
							$val .= ' %';
						?>
						<span class="value"><?php echo $val; ?></span>
						<span class="label"><?php _e( 'Original Version\'s Conversion Rate', 'nelioab' ); ?></span>
					</div>
					<div class="row data">
						<?php
							$val = $exp->get_best_alternative_conversion_rate();
							$val = number_format_i18n( $val, 2 );
							$val = preg_replace( '/(...)$/', '<span class="decimals">$1</span>', $val );
							$val .= ' %';
						?>
						<span class="value"><?php echo $val; ?></span>
						<span class="label"><?php _e( 'Best Alternative\'s Conversion Rate', 'nelioab' ); ?></span>
					</div>
				</div>
				<?php $graphic_id = 'graphic-' . $exp->get_id(); ?>
				<div class="col col-4 graphic" id="<?php echo $graphic_id; ?>">
				</div><?php
					if ( $exp->get_total_conversions() > 0 )
						$fix = '';
					else
							$fix = '.1';
					$alt_infos = $exp->get_alternative_info();
					$values = '';
					for ( $i = 0; $i < count( $alt_infos ); ++$i ) {
						$aux = $alt_infos[$i];
						$name = $aux['name'];
						$name = str_replace( '\\', '\\\\', $name );
						$name = str_replace( '\'', '\\\'', $name );
						$conv = $aux['conversions'];
						$values .= "\n\t\t\t\t{ name: '$name', y: $conv$fix },\n";
					}

					switch( $exp->get_type() ) {
						case NelioABExperiment::PAGE_ALT_EXP:
							$color = '#DE4A3A';
							break;
						case NelioABExperiment::POST_ALT_EXP:
							$color = '#F19C00';
							break;
						case NelioABExperiment::HEADLINE_ALT_EXP:
							$color = '#79B75D';
							break;
						case NelioABExperiment::THEME_ALT_EXP:
							$color = '#61B8DD';
							break;
						case NelioABExperiment::CSS_ALT_EXP:
							$color = '#6EBEC5';
							break;
						case NelioABExperiment::WIDGET_ALT_EXP:
							$color = '#2A508D';
							break;
						case NelioABExperiment::MENU_ALT_EXP:
							$color = '#8bb846';
							break;
						default:
							$color = '#CCCCCC';
					}
				?>
				<script>jQuery(document).ready(function() {
					var aux = setTimeout( function() {
						drawGraphic('<?php echo $graphic_id; ?>',
							[<?php echo $values; ?>],
							"<?php echo esc_html( __( 'Conversions', 'nelioab' ) ); ?>",
							"<?php echo $color; ?>");
					}, <?php echo $this->graphic_delay; $this->graphic_delay += 250; ?> );
				});</script>
			</div>
			<?php
		}

		public function print_heatmap_exp_card( $exp ) {
			$hm = $exp->get_heatmap_info();
			?>
			<div class="row padding-top">
				<div class="col col-6">
					<div class="row data phone padding-left">
						<span class="value"><?php echo $hm['phone']; ?></span>
						<span class="label"><?php _e( 'Views on Phone', 'nelioab' ); ?></span>
					</div>
					<div class="row data tablet padding-left">
						<span class="value"><?php echo $hm['tablet']; ?></span>
						<span class="label"><?php _e( 'Views on Tablet', 'nelioab' ); ?></span>
					</div>
				</div>
				<div class="col col-6">
					<div class="row data desktop">
						<span class="value"><?php echo $hm['desktop']; ?></span>
						<span class="label"><?php _e( 'Views on Desktop', 'nelioab' ); ?></span>
					</div>
					<div class="row data hd">
						<span class="value"><?php echo $hm['hd']; ?></span>
						<span class="label"><?php _e( 'Views on Large Screens', 'nelioab' ); ?></span>
					</div>
				</div>
			</div>
			<?php
		}

	}//NelioABDashboardPage

}

