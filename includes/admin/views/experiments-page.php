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


if ( !class_exists( 'NelioABExperimentsPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );
	require_once( NELIOAB_UTILS_DIR . '/html-generator.php' );

	class NelioABExperimentsPage extends NelioABAdminAjaxPage {

		private $experiments;
		private $current_status;

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->add_title_action( __( 'Add New', 'nelioab' ), '?page=nelioab-add-experiment' );
			$this->current_filter = 'none';
			$this->current_status = false;
		}

		public function set_experiments( $experiments ) {
			$this->experiments = $experiments;
		}

		public function filter_by_status( $status ) {
			$this->current_status = $status;
		}

		protected function do_render() {
			// If there are no experiments, tell the user to create one.
			if ( count( $this->experiments ) == 0 ) {
				echo '<center>';
				echo sprintf( '<img src="%s" alt="%s" />',
					nelioab_asset_link( '/admin/images/happy.png' ),
					__( 'Happy smile.', 'nelioab' )
				);
				echo '<h2>';
				echo sprintf(
					__( 'Hey! It looks like you have not defined any experiment...<br /><a href="%s">Create one now</a>!', 'nelioab' ),
					'admin.php?page=nelioab-add-experiment' );
				echo '</h2>';
				echo '</center>';
				return;
			}

			?>
			<script type="text/javascript">
			(function($) {
				$('#dialog-modal').dialog({
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
							text: "<?php echo esc_html( __( 'OK', 'nelioab' ) ); ?>",
							'class': 'button-primary',
							click: function() {
								$(this).dialog('close');
								window.location.href = $(this).data( 'href' );
							}
						}
					]
				});
			})(jQuery);
			function nelioabValidateClick(msg_id, href) {
				var $dialog = jQuery('#dialog-modal');
				$dialog.data( 'href', href );
				switch (msg_id) {
					case 0:<?php
						$title = __( 'Start Experiment', 'nelioab' );
						$title = str_replace( '"', '\\"', $title );
						$msg = __( 'You are about to start an experiment. Once the experiment has started, you cannot edit it. Do you want to continue?', 'nelioab' );
						$msg = str_replace( '"', '\\"', $msg ); ?>
						jQuery('#dialog-content').html("<?php echo $msg; ?>");
						$dialog.dialog('option', 'title', "<?php echo $title; ?>");
						$dialog.parent().find('.button-primary .ui-button-text').text("<?php _e( 'Start', 'nelioab' ); ?>");
						$dialog.dialog('open');
						break;
					case 1:<?php
						$title = __( 'Stop Experiment', 'nelioab' );
						$title = str_replace( '"', '\\"', $title );
						$msg = __( 'You are about to stop an experiment. Once the experiment is stopped, you cannot resume it. Do you want to continue?', 'nelioab' );
						$msg = str_replace( '"', '\\"', $msg ); ?>
						jQuery('#dialog-content').html("<?php echo $msg; ?>");
						$dialog.dialog('option', 'title', "<?php echo $title; ?>");
						$dialog.parent().find('.button-primary .ui-button-text').text("<?php _e( 'Stop', 'nelioab' ); ?>");
						$dialog.dialog('open');
						break;
				}
			}
			</script>
			<form id="nelioab_experiment_list_form" method="POST" >
				<input type="hidden" name="nelioab_experiment_list_form" value="true" />
				<input type="hidden" id="action" name="action" value="" />
				<input type="hidden" id="experiment_id" name="experiment_id" value="" />
			</form>
			<?php

			$status_draft     = NelioABExperimentStatus::DRAFT;
			$status_ready     = NelioABExperimentStatus::READY;
			$status_scheduled = NelioABExperimentStatus::SCHEDULED;
			$status_running   = NelioABExperimentStatus::RUNNING;
			$status_finished  = NelioABExperimentStatus::FINISHED;
			$status_trash     = NelioABExperimentStatus::TRASH;
			NelioABHtmlGenerator::print_filters(
				admin_url( 'admin.php?page=nelioab-experiments' ),
				array (
					array ( 'value' => 'none',
					        'label' => __( 'All' ),
					        'count' => count( $this->filter_experiments() ) ),
					array ( 'value' => $status_draft,
					        'label' => NelioABExperimentStatus::to_string( $status_draft ),
					        'count' => count( $this->filter_experiments( $status_draft ) ) ),
					array ( 'value' => $status_ready,
					        'label' => NelioABExperimentStatus::to_string( $status_ready ),
					        'count' => count( $this->filter_experiments( $status_ready ) ) ),
					array ( 'value' => $status_scheduled,
					        'label' => NelioABExperimentStatus::to_string( $status_scheduled ),
					        'count' => count( $this->filter_experiments( $status_scheduled ) ) ),
					array ( 'value' => $status_running,
					        'label' => NelioABExperimentStatus::to_string( $status_running ),
					        'count' => count( $this->filter_experiments( $status_running ) ) ),
					array ( 'value' => $status_finished,
					        'label' => NelioABExperimentStatus::to_string( $status_finished ),
					        'count' => count( $this->filter_experiments( $status_finished ) ) ),
					array ( 'value' => $status_trash,
					        'label' => NelioABExperimentStatus::to_string( $status_trash ),
					        'count' => count( $this->filter_experiments( $status_trash ) ) ),
				),
				'status',
				$this->current_status
			);

			$wp_list_table = new NelioABExperimentsTable( $this->filter_experiments( $this->current_status ) );
			$wp_list_table->prepare_items();
			echo '<div id="nelioab-experiment-list-table">';
			$wp_list_table->display();
			echo '</div>';
		}

		private function filter_experiments( $status = false ) {
			if ( !$status ) {
				$result = array();
				$filter_finished = NelioABSettings::show_finished_experiments();
				foreach ( $this->experiments as $exp ) {
					if ( $exp->get_status() == NelioABExperimentStatus::FINISHED ) {
						if ( NelioABSettings::FINISHED_EXPERIMENTS_HIDE_ALL == $filter_finished )
							continue;
						if ( NelioABSettings::FINISHED_EXPERIMENTS_SHOW_RECENT == $filter_finished &&
						     $exp->get_days_since_finalization() > 7 )
							continue;
					}
					if ( $exp->get_status() != NelioABExperimentStatus::TRASH )
						array_push( $result, $exp );
				}
				return $result;
			}
			else {
				$result = array();
				foreach ( $this->experiments as $exp )
					if ( $exp->get_status() == $status )
						array_push( $result, $exp );
				return $result;
			}
		}

	}//NelioABExperimentsPage


	require_once( NELIOAB_UTILS_DIR . '/admin-table.php' );
	class NelioABExperimentsTable extends NelioABAdminTable {

		function __construct( $experiments ){
			parent::__construct( array(
				'singular'  => __( 'experiment', 'nelioab' ),
				'plural'    => __( 'experiments', 'nelioab' ),
				'ajax'      => false
			)	);
			$this->set_items( $experiments );
			add_action( 'admin_head', array( &$this, 'admin_header' ) );
		}

		function get_columns(){
			return array(
				'type'          => '',
				'name'          => __( 'Name', 'nelioab' ),
				'status'        => __( 'Status', 'nelioab' ),
				'relevant_date' => __( 'Relevant Date', 'nelioab' ),
			);
		}

		public function get_table_id() {
			return 'list-of-experiments-table';
		}

		public function get_jquery_sortable_columns() {
			return array( 'name', 'status', 'relevant_date' );
		}

		function get_display_functions() {
			return array(
				// 'description' => 'get_description',
			);
		}

		function column_name( $exp ) {

			$url_fragment = admin_url( 'admin.php?page=nelioab-experiments&action=%1$s&id=%2$s&exp_type=%3$s' );
			if ( isset( $_REQUEST['status'] ) )
				$url_fragment .= '&status=' . $_REQUEST['status'];

			$edit_url     = '<a href="?page=nelioab-experiments&action=edit&id=%1$s&exp_type=%2$s">%3$s</a>';
			$url          = '<a href="' . $url_fragment . '">%4$s</a>';
			$url_dialog   = '<a href="#" onclick="javascript:nelioabValidateClick(%5$s, \'' . $url_fragment . '\');return false;">%4$s</a>';
			$progress_url = '<a href="?page=nelioab-experiments&action=progress&id=%1$s&exp_type=%2$s">%3$s</a>';
			if ( $exp->get_type() == NelioABExperiment::HEATMAP_EXP ) {
				include_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
				$progress_url = '<a href="' . NelioABWPHelper::get_unsecured_site_url() . '/wp-content/plugins/' . NELIOAB_PLUGIN_DIR_NAME . '/heatmaps.php?id=%1$s&exp_type=%2$s">%3$s</a>';
			}

			$actions = array();
			switch( $exp->get_status() ) {
				case NelioABExperimentStatus::DRAFT:
					$actions = array(
						'edit'  => sprintf( $edit_url, $exp->get_id(), $exp->get_type(), __( 'Edit' ) ),
						'trash' => sprintf( $url, 'trash', $exp->get_id(), $exp->get_type(), __( 'Trash' ) ),
					);
					break;
				case NelioABExperimentStatus::READY:
					$actions = array();
					$actions['edit'] = sprintf( $edit_url, $exp->get_id(), $exp->get_type(), __( 'Edit' ) );
					$actions['start'] = sprintf( $url_dialog, 'start', $exp->get_id(), $exp->get_type(), __( 'Start', 'nelioab' ), 0 );
					if ( NelioABAccountSettings::get_subscription_plan() >= NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN )
						$actions['schedule'] = sprintf( $url, 'schedule', $exp->get_id(), $exp->get_type(), __( 'Schedule' ) );
					$actions['trash'] = sprintf( $url, 'trash', $exp->get_id(), $exp->get_type(), __( 'Trash' ) );
					break;
				case NelioABExperimentStatus::SCHEDULED:
					$actions = array(
						'start' => sprintf( $url_dialog, 'start', $exp->get_id(), $exp->get_type(), __( 'Start Now', 'nelioab' ), 0 ),
						'schedule' => sprintf( $url, 'schedule', $exp->get_id(), $exp->get_type(), __( 'Reschedule' ) ),
						'cancel-schedule' => sprintf( $url, 'cancel-schedule', $exp->get_id(), $exp->get_type(), __( 'Cancel Schedule', 'nelioab' ), 1 ),
					);
					break;
				case NelioABExperimentStatus::RUNNING:
					$actions = array(
						'theprogress' => sprintf( $progress_url, $exp->get_id(), $exp->get_type(), __( 'View' ) ),
						'stop'        => sprintf( $url_dialog, 'stop', $exp->get_id(), $exp->get_type(), __( 'Stop', 'nelioab' ), 1 ),
					);
					break;
				case NelioABExperimentStatus::FINISHED:
					$actions = array(
						'theprogress' => sprintf( $progress_url, $exp->get_id(), $exp->get_type(), __( 'View' ) ),
						'delete'      => sprintf( $url, 'delete', $exp->get_id(), $exp->get_type(), __( 'Delete Permanently' ) ),
					);
					break;
				case NelioABExperimentStatus::TRASH:
				default:
					$actions = array(
						'restore' => sprintf( $url, 'restore', $exp->get_id(), $exp->get_type(), __( 'Restore' ) ),
						'delete'  => sprintf( $url, 'delete', $exp->get_id(), $exp->get_type(), __( 'Delete Permanently' ) ),
					);
					break;
			}

			//Build row actions
			return sprintf(
				'<span class="row-title">%2$s</span>%3$s',
				/*%1$s*/ $exp->get_id(),
				/*%2$s*/ $exp->get_name(),
				/*%3$s*/ $this->row_actions( $actions )
			);
		}

		public function column_relevant_date( $exp ) {
			include_once( NELIOAB_UTILS_DIR . '/formatter.php' );
			$date = '<span style="display:none;">%s</span><span title="%s">%s</span>';

			switch ( $exp->get_status() ) {

				case NelioABExperimentStatus::FINISHED:
					$res = sprintf( $date,
						strtotime( $exp->get_end_date() ),
						__( 'Finalization Date', 'nelioab' ),
						NelioABFormatter::format_date( $exp->get_end_date() ) );
					break;

				case NelioABExperimentStatus::RUNNING:
					$res = sprintf( $date,
						strtotime( $exp->get_start_date() ),
						__( 'Start Date', 'nelioab' ),
						NelioABFormatter::format_date( $exp->get_start_date() ) );
					break;

				case NelioABExperimentStatus::SCHEDULED:
					$res = sprintf( $date,
						strtotime( $exp->get_start_date() ),
						__( 'Scheduled Date', 'nelioab' ),
						NelioABFormatter::format_date( $exp->get_start_date() ) );
					break;

				default:
					$res = sprintf( $date,
						strtotime( $exp->get_creation_date() ),
						__( 'Creation Date', 'nelioab' ),
						NelioABFormatter::format_date( $exp->get_creation_date() ) );
					break;

			}

			return $res;
		}

		public function column_status( $exp ){
			$str = NelioABExperimentStatus::to_string( $exp->get_status() );
			switch( $exp->get_status() ) {
				case NelioABExperimentStatus::DRAFT:
					return $this->make_label( $str, '#999999', '#eeeeee' );
				case NelioABExperimentStatus::PAUSED:
					return $this->make_label( $str, '#999999', '#eeeeee' );
				case NelioABExperimentStatus::READY:
					return $this->make_label( $str, '#e96500', '#fff6ad' );
				case NelioABExperimentStatus::SCHEDULED:
					return $this->make_label( $str, '#fff6ad', '#e96500' );
				case NelioABExperimentStatus::RUNNING:
					return $this->make_label( $str, '#266529', '#d1ffd3' );
				case NelioABExperimentStatus::FINISHED:
					return $this->make_label( $str, '#103269', '#BED6FC' );
				case NelioABExperimentStatus::TRASH:
					return $this->make_label( $str, '#802a28', '#ffe0df' );
				default:
					return $this->make_label( $str, '#999999', '#eeeeee' );
			}
		}

		function column_type( $exp ){
			$img = '<div class="tab-type tab-type-%1$s" alt="%2$s" title="%2$s"></div>';

			switch( $exp->get_type() ) {
				case NelioABExperiment::TITLE_ALT_EXP:
					return sprintf( $img, 'title', __( 'Title', 'nelioab' ) );

				case NelioABExperiment::PAGE_ALT_EXP:
					return sprintf( $img, 'page', __( 'Page', 'nelioab' ) );

				case NelioABExperiment::POST_ALT_EXP:
					return sprintf( $img, 'post', __( 'Post', 'nelioab' ) );

				case NelioABExperiment::TITLE_ALT_EXP:
					return sprintf( $img, 'title', __( 'Title', 'nelioab' ) );

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

				default:
					return '';
			}
		}

		private function make_label( $label, $color, $bgcolor = false ) {
			if ( $bgcolor )
				$aux = 'background-color:' . $bgcolor . ';';
			else
				$aux = '';
			$style = '<div style="padding-top:5px;">' .
				'<span class="add-new-h2" style="' .
				'color:%s;' .
				$aux .
				'font-size:90%%;' .
				'padding-top:1px;' .
				'padding-bottom:1px;' .
				'position:inherit;' .
				'">%s</span></div>';
			return sprintf( $style, $color, $label );
		}

		protected function print_inline_edit_form() {
			// No inline edit form, but I'll use this function to
			// print the dialog for scheduling experiments
			if ( NelioABAccountSettings::get_subscription_plan() >= NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN ) { ?>
				<div id="nelioab-scheduling-dialog" title="<?php
					_e( 'Experiment Scheduling', 'nelioab' );
				?>">
					<p>Schedule experiment start for:</p>
					<?php
					require_once( NELIOAB_UTILS_DIR . '/html-generator.php' );
					NelioABHtmlGenerator::print_scheduling_picker();
					?>
					<p class="error" style="color:red;display:none;"><?php
						_e( 'Please, specify a full date (month, day, and year) in the future.', 'nelioab' );
					?></p>
				</div>
				<script>
					jQuery(function($) {<?php
						$ts = time(); ?>
						var TODAY_DAY   = '<?php echo date( 'd', $ts ); ?>';
						var TODAY_MONTH = '<?php echo date( 'm', $ts ); ?>';
						var TODAY_YEAR  = '<?php echo date( 'Y', $ts ); ?>';
						var $info = $('#nelioab-scheduling-dialog');
						$info.dialog({
							'dialogClass'   : 'wp-dialog',
							'modal'         : true,
							'autoOpen'      : false,
							'closeOnEscape' : true,
							buttons: [
								{
									text: "<?php echo esc_html( __( 'Cancel', 'nelioab' ) ); ?>",
									click: function() {
										$(this).dialog('close');
									}
								},
								{
									text: "<?php echo esc_html( __( 'Schedule', 'nelioab' ) ); ?>",
									'class': 'button-primary',
									click: function() {
										try {
											var day   = $('#nelioab-scheduling-dialog input.jj').attr('value');
											var month = $('#nelioab-scheduling-dialog select.mm').attr('value');
											var year  = $('#nelioab-scheduling-dialog input.aa').attr('value');

											if ( day == undefined ) day = '00';
											if ( year == undefined ) year = '0000';
											while ( day.length < 2 ) day = '0' + day;
											while ( year.length < 4 ) year = '0' + year;
											if ( year < TODAY_YEAR )
												throw new Exception();
											if ( year == TODAY_YEAR && month < TODAY_MONTH )
												throw new Exception();
											else if ( year == TODAY_YEAR && month == TODAY_MONTH && day <= TODAY_DAY )
												throw new Exception();

											var res = year + '-' + month + '-' + day;
											$( '#nelioab-scheduling-dialog .error').hide();
											$(this).dialog('close');
											window.location = $(this).data('url') + '&schedule_date=' + res;
										}
										catch ( e ) {
											$( '#nelioab-scheduling-dialog .error').show();
										}
									}
								}
							],
						});
						$('.row-actions .schedule > a').click(function(event) {
							event.preventDefault();
							$('#nelioab-scheduling-dialog input.jj').attr('value', '');
							$('#nelioab-scheduling-dialog select.mm').attr('value', TODAY_MONTH);
							$('#nelioab-scheduling-dialog input.aa').attr('value', '');
							$info.data('url', $(this).attr('href'));
							$info.dialog( 'open' );
						});
					});
				</script>
			<?php
			}
		}

	}// NelioABExperimentsTable
}

