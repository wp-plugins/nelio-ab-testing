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


if ( !class_exists( NelioABExperimentsPage ) ) {

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

		protected function do_render() {?>
			<script type="text/javascript">
			function isInvalidClick(msg_id) {
				switch (msg_id) {
					case 0:<?php
						$msg = __( 'You are about to start an experiment. Once the experiment has started, you cannot edit it. Do you want to continue?', 'nelioab' );
						$msg = str_replace( '"', '\\"', $msg ); ?>
						return !confirm("<?php echo $msg; ?>");
					case 1:<?php
						$msg = __( 'You are about to stop an experiment. Once the experiment is stopped, you cannot resume it. Do you want to continue?', 'nelioab' );
						$msg = str_replace( '"', '\\"', $msg ); ?>
						return !confirm("<?php echo $msg; ?>");
				}
			}
			</script>
			<form id="nelioab_experiment_list_form" method="POST" >
				<input type="hidden" name="nelioab_experiment_list_form" value="true" />
				<input type="hidden" id="action" name="action" value="" />
				<input type="hidden" id="experiment_id" name="experiment_id" value="" />
			</form>
			<?php

			$status_draft    = NelioABExperimentStatus::DRAFT;
			$status_ready    = NelioABExperimentStatus::READY;
			$status_running  = NelioABExperimentStatus::RUNNING;
			$status_finished = NelioABExperimentStatus::FINISHED;
			$status_trash    = NelioABExperimentStatus::TRASH;
			NelioABHtmlGenerator::print_filters(
				get_admin_url() . 'admin.php?page=nelioab-experiments',
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
			$wp_list_table->display();
		}

		private function filter_experiments( $status = false ) {
			if ( !$status ) {
				$result = array();
				foreach ( $this->experiments as $exp )
					if ( $exp->get_status() != NelioABExperimentStatus::TRASH )
						array_push( $result, $exp );
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
		}
		
		function get_columns(){
			return array(
				'name'        => __( 'Name', 'nelioab' ),
				'description' => __( 'Description', 'nelioab' ),
				'status'      => __( 'Status', 'nelioab' ),
				'creation'    => __( 'Creation Date', 'nelioab' ),
			);
		}

//		function get_sortable_columns(){
//			return array(
//				'name'        => __( 'Name', 'nelioab' ),
//				'status'      => __( 'Status', 'nelioab' ),
//			);
//		}

		function get_display_functions() {
			return array(
				'description' => 'get_description',
				'status'      => 'get_status',
			);
		}

		function column_name( $exp ){

			$edit_url     = '<a href="?page=nelioab-experiments&action=edit&id=%1$s">%2$s</a>';
			$progress_url = '<a href="?page=nelioab-experiments&action=progress&id=%1$s">%2$s</a>';
			$url          = '<a href="?page=nelioab-experiments&action=%1$s&id=%2$s">%3$s</a>';
			$url_dialog   = '<a href="?page=nelioab-experiments&action=%1$s&id=%2$s" onclick="javascript:if(isInvalidClick(%4$s)){return false;}">%3$s</a>';

			$actions = array();
			switch( $exp->get_status() ) {
				case NelioABExperimentStatus::DRAFT:
					$actions = array(
						'edit'  => sprintf( $edit_url, $exp->get_id(), __( 'Edit' ) ),
						'trash' => sprintf( $url, 'trash', $exp->get_id(), __( 'Trash' ) ),
					);
					break;
				case NelioABExperimentStatus::READY:
					$actions = array(
						'edit'  => sprintf( $edit_url, $exp->get_id(), __( 'Edit' ) ),
						'start' => sprintf( $url_dialog, 'start', $exp->get_id(), __( 'Start', 'nelioab' ), 0 ),
						'trash' => sprintf( $url, 'trash', $exp->get_id(), __( 'Trash' ) ),
					);
					break;
				case NelioABExperimentStatus::RUNNING:
					$actions = array(
						'progress' => sprintf( $progress_url, $exp->get_id(), __( 'View' ) ),
						'stop'     => sprintf( $url_dialog, 'stop', $exp->get_id(), __( 'Stop', 'nelioab' ), 1 ),
					);
					break;
				case NelioABExperimentStatus::FINISHED:
					$actions = array(
						'progress' => sprintf( $progress_url, $exp->get_id(), __( 'View' ) ),
						'delete'   => sprintf( $url, 'delete', $exp->get_id(), __( 'Delete Permanently' ) ),
					);
					break;
				case NelioABExperimentStatus::TRASH:
				default:
					$actions = array(
						'restore' => sprintf( $url, 'restore', $exp->get_id(), __( 'Restore' ) ),
						'delete'  => sprintf( $url, 'delete', $exp->get_id(), __( 'Delete Permanently' ) ),
					);
					break;
			}
			
			//Build row actions
			return sprintf(
				'<span class="row-title">%2$s</span>%3$s',
				/*%1$s*/ $exp->get_id(),
				/*%2$s*/ $exp->get_name(),
				/*%3$s*/ $this->row_actions($actions)
			);
		}

		public function column_creation( $exp ) {
			include_once( NELIOAB_UTILS_DIR . '/formatter.php' );
			return NelioABFormatter::format_date( $exp->get_creation_date() );
		}

		public function column_status( $exp ){
			return NelioABExperimentStatus::to_string( $exp->get_status() );
		}

	}// NelioABExperimentsTable
}

?>
