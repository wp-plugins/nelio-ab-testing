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
				echo "<div class='nelio-message'>";
				echo sprintf( '<img class="animated flipInY" src="%s" alt="%s" />',
					nelioab_admin_asset_link( '/images/message-icon.png' ),
					__( 'Information Notice', 'nelioab' )
				);
				echo '<h2 style="max-width:750px;">';
				printf( '%1$s<br><br><a class="button button-primary" href="%3$s">%2$s</a>',
					__( 'Find and manage all your experiments from this page.<br>Click the following button and create your first experiment!', 'nelioab' ),
					__( 'Create Experiment', 'nelioab', 'create-experiment' ),
					'admin.php?page=nelioab-add-experiment' );
				echo '</h2>';
				echo '</div>';

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
							'class': 'button button-primary',
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
						$msg = __( 'You are about to start an experiment. Once the experiment has started, you cannot edit it. Are you sure you want to start the experiment?', 'nelioab' );
						$msg = str_replace( '"', '\\"', $msg ); ?>
						jQuery('#dialog-content').html("<?php echo $msg; ?>");
						$dialog.dialog('option', 'title', "<?php echo $title; ?>");
						$dialog.parent().find('.button-primary .ui-button-text').text("<?php _e( 'Start', 'nelioab' ); ?>");
						$dialog.dialog('open');
						break;
					case 1:<?php
						$title = __( 'Stop Experiment', 'nelioab' );
						$title = str_replace( '"', '\\"', $title );
						$msg = __( 'You are about to stop an experiment. Once the experiment is stopped, you cannot resume it. Are you sure you want to stop the experiment?', 'nelioab' );
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

			$status_draft     = NelioABExperiment::STATUS_DRAFT;
			$status_ready     = NelioABExperiment::STATUS_READY;
			$status_scheduled = NelioABExperiment::STATUS_SCHEDULED;
			$status_running   = NelioABExperiment::STATUS_RUNNING;
			$status_finished  = NelioABExperiment::STATUS_FINISHED;
			$status_trash     = NelioABExperiment::STATUS_TRASH;
			NelioABHtmlGenerator::print_filters(
				admin_url( 'admin.php?page=nelioab-experiments' ),
				array (
					array ( 'value' => 'none',
					        'label' => __( 'All' ),
					        'count' => count( $this->filter_experiments() ) ),
					array ( 'value' => $status_draft,
					        'label' => NelioABExperiment::get_label_for_status( $status_draft ),
					        'count' => count( $this->filter_experiments( $status_draft ) ) ),
					array ( 'value' => $status_ready,
					        'label' => NelioABExperiment::get_label_for_status( $status_ready ),
					        'count' => count( $this->filter_experiments( $status_ready ) ) ),
					array ( 'value' => $status_scheduled,
					        'label' => NelioABExperiment::get_label_for_status( $status_scheduled ),
					        'count' => count( $this->filter_experiments( $status_scheduled ) ) ),
					array ( 'value' => $status_running,
					        'label' => NelioABExperiment::get_label_for_status( $status_running ),
					        'count' => count( $this->filter_experiments( $status_running ) ) ),
					array ( 'value' => $status_finished,
					        'label' => NelioABExperiment::get_label_for_status( $status_finished ),
					        'count' => count( $this->filter_experiments( $status_finished ) ) ),
					array ( 'value' => $status_trash,
					        'label' => NelioABExperiment::get_label_for_status( $status_trash ),
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

			// Code for duplicating experiments.
			$this->insert_duplicate_dialog();

			// Code for scheduling experiments.
			if ( NelioABAccountSettings::is_plan_at_least( NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN ) )
				$this->insert_schedule_dialog();
		}

		private function filter_experiments( $status = false ) {
			if ( !$status ) {
				$result = array();
				$filter_finished = NelioABSettings::show_finished_experiments();
				foreach ( $this->experiments as $exp ) {
					if ( $exp->get_status() == NelioABExperiment::STATUS_FINISHED ) {
						if ( NelioABSettings::FINISHED_EXPERIMENTS_HIDE_ALL == $filter_finished )
							continue;
						if ( NelioABSettings::FINISHED_EXPERIMENTS_SHOW_RECENT == $filter_finished &&
						     $exp->get_days_since_finalization() > 7 )
							continue;
					}
					if ( $exp->get_status() != NelioABExperiment::STATUS_TRASH )
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

		private function insert_duplicate_dialog() { ?>
			<div id="nelioab-scheduling-dialog" class="nelio-sect" title="<?php
				_e( 'Experiment Duplication', 'nelioab' );
			?>">
				<p><?php _e( 'You are about to duplicate an experiment.<br>New name:', 'nelioab' ); ?></p>
				<input type="text" id="duplicate-name" style="width:100%;" />
			</div>
			<script>
				jQuery(function($) {<?php
					$ts = time() + 86400; ?>
					var TOMORROW_DAY   = '<?php echo date( 'd', $ts ); ?>';
					var TOMORROW_MONTH = '<?php echo date( 'm', $ts ); ?>';
					var TOMORROW_YEAR  = '<?php echo date( 'Y', $ts ); ?>';
					var $dupDialog = $('#nelioab-scheduling-dialog').dialog({
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
								text: "<?php echo esc_html( __( 'Duplicate', 'nelioab' ) ); ?>",
								'class': 'button button-primary',
								click: function() {
									if ( $okButton.hasClass('disabled') ) return;
									window.location = $(this).data('url') + '&name=' + encodeURIComponent( $input.val() );
								}
							},
						],
					});
					var $okButton = $dupDialog.closest('.ui-dialog').find('.button-primary');
					var $input = $dupDialog.find('input');
					$('.row-actions .duplicate > a').click(function(event) {
						event.preventDefault();
						$('#duplicate-name').val('');
						$dupDialog.data('url', $(this).attr('href'));
						$okButton.addClass('disabled');
						$input.removeClass('error');
						$dupDialog.dialog( 'open' );
					});
					$input.on('keyup change focusout', function() {
						var name = $(this).val().trim();
						if ( "" == name ) {
							$okButton.addClass('disabled');
							$(this).addClass('error');
							return;
						}
						var names = <?php
							$names = array();
							foreach ( $this->experiments as $exp )
								array_push( $names, trim( $exp->get_name() ) );
							echo json_encode( $names );
						?>;
						for ( var i=0; i<names.length; ++i ) {
							if ( names[i] == name ) {
								$okButton.addClass('disabled');
								$(this).addClass('error');
								return;
							}
						}
						$okButton.removeClass('disabled');
						$(this).removeClass('error');
					});
				});
				</script><?php
		}

		private function insert_schedule_dialog() { ?>
			<div id="nelioab-scheduling-dialog" title="<?php
				_e( 'Experiment Scheduling', 'nelioab' );
			?>">
				<p><?php _e( 'Schedule experiment start for:', 'nelioab' ); ?></p>
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
					$ts = time() + 86400; ?>
					var TOMORROW_DAY   = '<?php echo date( 'd', $ts ); ?>';
					var TOMORROW_MONTH = '<?php echo date( 'm', $ts ); ?>';
					var TOMORROW_YEAR  = '<?php echo date( 'Y', $ts ); ?>';
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
								'class': 'button button-primary',
								click: function() {
									try {
										var day   = $('#nelioab-scheduling-dialog input.jj').attr('value');
										var month = $('#nelioab-scheduling-dialog select.mm').attr('value');
										var year  = $('#nelioab-scheduling-dialog input.aa').attr('value');

										if ( day == undefined ) day = '00';
										if ( year == undefined ) year = '0000';
										while ( day.length < 2 ) day = '0' + day;
										while ( year.length < 4 ) year = '0' + year;
										if ( year < TOMORROW_YEAR )
											throw new Exception();
										if ( year == TOMORROW_YEAR && month < TOMORROW_MONTH )
											throw new Exception();
										else if ( year == TOMORROW_YEAR && month == TOMORROW_MONTH && day < TOMORROW_DAY )
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
						$('#nelioab-scheduling-dialog input.jj').attr('value', TOMORROW_DAY);
						$('#nelioab-scheduling-dialog select.mm').attr('value', TOMORROW_MONTH);
						$('#nelioab-scheduling-dialog input.aa').attr('value', TOMORROW_YEAR);
						$info.data('url', $(this).attr('href'));
						$info.dialog( 'open' );
					});
				});
			</script><?php
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

			$url           = '<a href="' . $url_fragment . '">%4$s</a>';
			$url_dialog    = '<a href="#" onclick="javascript:nelioabValidateClick(%5$s, \'' . $url_fragment . '\');return false;">%4$s</a>';
			$url_duplicate = '<a href="' . $url_fragment . '&_nonce=' . nelioab_onetime_nonce( 'duplicate-' . $exp->get_id() ) . '">%4$s</a>';
			$progress_url  = '<a href="?page=nelioab-experiments&action=progress&id=%1$s&exp_type=%2$s">%3$s</a>';
			if ( $exp->get_type() == NelioABExperiment::HEATMAP_EXP ) {
				include_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
				$progress_url = '<a href="' .
					str_replace( 'https://', 'http://',
						admin_url( 'admin.php?nelioab-page=heatmaps&id=%1$s&exp_type=%2$s' ) ) .
					'">%3$s</a>';
			}

			$actions = array();
			switch( $exp->get_status() ) {
				case NelioABExperiment::STATUS_DRAFT:
					$actions = array(
						'edit'  => sprintf( $url, 'edit', $exp->get_id(), $exp->get_type(), __( 'Edit' ) ),
						'trash' => sprintf( $url, 'trash', $exp->get_id(), $exp->get_type(), __( 'Trash' ) ),
					);
					break;
				case NelioABExperiment::STATUS_READY:
					$actions = array();
					$actions['edit'] = sprintf( $url, 'edit', $exp->get_id(), $exp->get_type(), __( 'Edit' ) );
					$actions['start'] = sprintf( $url_dialog, 'start', $exp->get_id(), $exp->get_type(), __( 'Start', 'nelioab' ), 0 );
					$actions['schedule'] = sprintf( $url, 'schedule', $exp->get_id(), $exp->get_type(), __( 'Schedule' ) );
					$actions['duplicate'] = sprintf( $url_duplicate, 'duplicate', $exp->get_id(), $exp->get_type(), __( 'Duplicate' ), 'nelioab' );
					$actions['trash'] = sprintf( $url, 'trash', $exp->get_id(), $exp->get_type(), __( 'Trash' ) );
					break;
				case NelioABExperiment::STATUS_SCHEDULED:
					$actions = array(
						'start' => sprintf( $url_dialog, 'start', $exp->get_id(), $exp->get_type(), __( 'Start Now', 'nelioab' ), 0 ),
						'schedule' => sprintf( $url, 'schedule', $exp->get_id(), $exp->get_type(), __( 'Reschedule' ) ),
						'cancel-schedule' => sprintf( $url, 'cancel-schedule', $exp->get_id(), $exp->get_type(), __( 'Cancel Schedule', 'nelioab' ), 1 ),
						'duplicate' => sprintf( $url_duplicate, 'duplicate', $exp->get_id(), $exp->get_type(), __( 'Duplicate', 'nelioab' ) ),
					);
					break;
				case NelioABExperiment::STATUS_RUNNING:
					$actions = array(
						'theprogress' => sprintf( $progress_url, $exp->get_id(), $exp->get_type(), __( 'View' ) ),
						'stop'        => sprintf( $url_dialog, 'stop', $exp->get_id(), $exp->get_type(), __( 'Stop', 'nelioab' ), 1 ),
						'duplicate' => sprintf( $url_duplicate, 'duplicate', $exp->get_id(), $exp->get_type(), __( 'Duplicate', 'nelioab' ) ),
					);
					break;
				case NelioABExperiment::STATUS_FINISHED:
					$actions = array(
						'theprogress' => sprintf( $progress_url, $exp->get_id(), $exp->get_type(), __( 'View' ) ),
						'duplicate' => sprintf( $url_duplicate, 'duplicate', $exp->get_id(), $exp->get_type(), __( 'Duplicate', 'nelioab' ) ),
						'delete'      => sprintf( $url, 'delete', $exp->get_id(), $exp->get_type(), __( 'Delete Permanently' ) ),
					);
					break;
				case NelioABExperiment::STATUS_TRASH:
				default:
					$actions = array(
						'restore' => sprintf( $url, 'restore', $exp->get_id(), $exp->get_type(), __( 'Restore' ) ),
						'delete'  => sprintf( $url, 'delete', $exp->get_id(), $exp->get_type(), __( 'Delete Permanently' ) ),
					);
					break;
			}

			$related_post = $exp->get_related_post_id();
			if ( isset( $actions['start'] ) && $related_post && $related_post > 0 && get_post_status( $related_post ) !== 'publish' ) {
				$label = sprintf( '<span style="cursor:default;" title="%s">%s</span>',
					esc_html( __( 'The experiment cannot be started because the tested element has not been published yet', 'nelioab' ) ),
					__( 'Start' ) );
				$actions['start'] = $label;
			}

			if ( !NelioABAccountSettings::is_plan_at_least( NelioABAccountSettings::PROFESSIONAL_SUBSCRIPTION_PLAN ) ) {
				$expl = __( 'Feature only available in the Professional Plan', 'nelioab' );
				// No actions available to Professional Plans only
			}

			if ( !NelioABAccountSettings::is_plan_at_least( NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN ) ) {
				$expl = __( 'Feature only available in the Enterprise Plan', 'nelioab' );
				if ( isset( $actions['schedule'] ) )
					$actions['schedule'] = sprintf( '<span title="%s">%s</span>', $expl, __( 'Schedule', 'nelioab' ) );
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

			case NelioABExperiment::STATUS_FINISHED:
					$res = sprintf( $date,
						strtotime( $exp->get_end_date() ),
						__( 'Finalization Date', 'nelioab' ),
						NelioABFormatter::format_date( $exp->get_end_date() ) );
					break;

			case NelioABExperiment::STATUS_RUNNING:
					$res = sprintf( $date,
						strtotime( $exp->get_start_date() ),
						__( 'Start Date', 'nelioab' ),
						NelioABFormatter::format_date( $exp->get_start_date() ) );
					break;

			case NelioABExperiment::STATUS_SCHEDULED:
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
			$str = NelioABExperiment::get_label_for_status( $exp->get_status() );
			switch( $exp->get_status() ) {
			case NelioABExperiment::STATUS_DRAFT:
					return $this->make_label( $str, '#999999', '#eeeeee' );
				case NelioABExperiment::STATUS_PAUSED:
					return $this->make_label( $str, '#999999', '#eeeeee' );
				case NelioABExperiment::STATUS_READY:
					return $this->make_label( $str, '#e96500', '#fff6ad' );
				case NelioABExperiment::STATUS_SCHEDULED:
					return $this->make_label( $str, '#fff6ad', '#e96500' );
				case NelioABExperiment::STATUS_RUNNING:
					return $this->make_label( $str, '#266529', '#d1ffd3' );
				case NelioABExperiment::STATUS_FINISHED:
					return $this->make_label( $str, '#103269', '#BED6FC' );
				case NelioABExperiment::STATUS_TRASH:
					return $this->make_label( $str, '#802a28', '#ffe0df' );
				default:
					return $this->make_label( $str, '#999999', '#eeeeee' );
			}
		}

		function column_type( $exp ){
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

	}// NelioABExperimentsTable
}

