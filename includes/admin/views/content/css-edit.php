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


require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );
class NelioABCssEditPage extends NelioABAdminAjaxPage {

	public static function build() {
		$view = new NelioABCssEditPage();
		$view->set_icon( 'icon-tools' );
		if ( isset( $_GET['exp_id'] ) )
			$view->keep_request_param( 'exp_id', $_GET['exp_id'] );
		if ( isset( $_GET['css_id'] ) )
			$view->keep_request_param( 'css_id', $_GET['css_id'] );

		if ( isset( $_POST['css_save_alt'] ) )
			$view->keep_request_param( 'css_save_alt', $_POST['css_save_alt'] );
		if ( isset( $_POST['post_title'] ) )
			$view->keep_request_param( 'css_alt_name', $_POST['post_title'] );
		if ( isset( $_POST['content'] ) )
			$view->keep_request_param( 'css_alt_value', urlencode( $_POST['content'] ) );
		$view->get_content_with_ajax_and_render( __FILE__, __CLASS__ );
	}

	public static function generate_html_content() {
		require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
		$view   = new NelioABCssEditPage();
		$mgr    = new NelioABExperimentsManager();
		$exp_id = 0;
		$css_id = 0;

		if ( isset( $_REQUEST['exp_id'] ) )
			$exp_id = $_REQUEST['exp_id'];

		if ( isset( $_REQUEST['css_id'] ) )
			$css_id = $_REQUEST['css_id'];

		if ( isset( $_REQUEST['css_save_alt'] ) &&
		     isset( $_REQUEST['css_alt_name'] ) &&
		     isset( $_REQUEST['css_alt_value'] )
			) {
			require_once( NELIOAB_MODELS_DIR . '/alternatives/css-alternative-experiment.php' );
			NelioABCssAlternativeExperiment::update_css_alternative(
				$css_id, $_REQUEST['css_alt_name'], urldecode( $_REQUEST['css_alt_value'] ) );
			NelioABExperimentsManager::update_running_experiments_cache( true );
		}

		try {
			$view->experiment = $mgr->get_experiment_by_id( $exp_id, NelioABExperiment::CSS_ALT_EXP );
			foreach( $view->experiment->get_alternatives() as $alt )
				if ( $alt->get_id() == $css_id )
					$view->css_alt = $alt;
		}
		catch ( Exception $e  ){
			require_once( NELIOAB_ADMIN_DIR . '/error-controller.php' );
			NelioABErrorController::build( $e );
		}
		$view->do_render();
		die();
	}

	public function __construct() {
		$title = __( 'Edit CSS', 'nelioab' );
		parent::__construct( $title );
	}

	private $css_alt;
	private $experiment;

	protected function do_render() { ?>

		<script>
		</script>

		<div id="post-body" class="metabox-holder columns-2">
		<form id="css_edit_value" method="POST" action="#">
			<input type="hidden" name="css_save_alt" id="css_save_alt" value="true"></input>
			<div id="post-body-content">
				
				<div id="titlediv">

					<div id="titlewrap">
						<label class="screen-reader-text" id="title-prompt-text" for="title">Enter title here</label>
						<input type="text" name="post_title" size="30" value="<?php
							echo $this->css_alt->get_name();
						?>" id="title" autocomplete="off">
					</div>

					<div class="inside">
						<div id="postdivrich" class="postarea edit-form-section">
							<div id="wp-content-wrap" class="wp-core-ui wp-editor-wrap tmce-active">
								<link rel="stylesheet" id="editor-buttons-css" href="http://localhost/wordpress/wp-includes/css/editor.min.css?ver=3.8" type="text/css" media="all">
								<div class="wp-editor-tabs">
									<a id="content-html" class="wp-switch-editor switch-html" onclick="switchEditors.switchto(this);">Plain</a>
									<!-- <a id="content-tmce" class="wp-switch-editor switch-tmce" onclick="switchEditors.switchto(this);">Visual</a> -->
								</div>
							</div>

							<div id="wp-content-editor-container" class="wp-editor-container">
								<textarea class="wp-editor-area" style="height:426px;resize:none;width:100%;" cols="40" name="content" id="content"><?php
									echo $this->css_alt->get_value();
								?></textarea>
							</div>

						</div>
					</div>

				</div>
			</div><!-- /post-body-content -->

			<div id="postbox-container-1" class="postbox-container">

				<div id="side-sortables" class="meta-box-sortables ui-sortable">
					<div id="submitdiv" class="postbox">
						<h3 style="cursor:auto;"><span><?php _e( 'Update' ); ?></span></h3>
						<div class="inside">
							<div class="submitbox" id="submitpost">

								<div style="margin:1em;">
									<p><?php _e( 'Select a page or post for preview', 'nelioab' ) ?></p>
									<?php
											$wp_pages = get_pages();
											$options_for_posts = array(
												'posts_per_page' => 150,
												'orderby'        => 'title',
												'order'          => 'asc' );
											$wp_posts = get_posts( $options_for_posts );

									?>
									<select id="goal_options" style="width:240px !important;" class="required">
										<option id="select_goal_label" value="-1"><?php _e( 'Preview with...', 'nelioab' ); ?></option>
										<?php
										if ( !get_option( 'page_on_front', 0 ) ) { ?>
											<optgroup id="latest-posts" label="<?php _e( 'Dynamic Front Page', 'nelioab' ); ?>">
												<option
													id="goal-0"
													value="<?php echo NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS; ?>"
													title="<?php _e( 'Your latest posts' ); ?>"><?php
													_e( 'Your latest posts' ); ?></option>
											</optgroup><?php
										}
										$counter = 1;
										if ( count( $wp_pages ) > 0 ) { ?>
											<optgroup id="page-options" label="<?php _e( 'WordPress Pages' ); ?>">
											<?php
											foreach ( $wp_pages as $p ) {
												$title = $p->post_title;
												$short = $title;
												if ( strlen( $short ) > 50 )
													$short = substr( $short, 0, 50 ) . '...';
												$title = str_replace( '"', '\'\'', $title ); ?>
												<option
													id="goal-<?php echo $counter; ++$counter; ?>"
													value="<?php echo $p->ID; ?>"
													title="<?php echo $title; ?>"><?php
													echo $short; ?></option><?php
											} ?>
											</optgroup><?php
										}
										if ( count( $wp_posts ) > 0 ) { ?>
											<optgroup id="post-options" label="<?php _e( 'WordPress Posts' ); ?>"><?php
											foreach ( $wp_posts as $p ) {
												$title = $p->post_title;
												$short = $title;
												if ( strlen( $short ) > 50 )
													$short = substr( $short, 0, 50 ) . '...';
												$title = str_replace( '"', '\'\'', $title ); ?>
												<option
													id="goal-<?php echo $counter; ++$counter; ?>"
													value="<?php echo $p->ID; ?>"
													title="<?php echo $title; ?>"><?php
													echo $short; ?></option><?php
											} ?>
											</optgroup><?php
										}
										?>
									</select>
								</div>
	
								<div class="misc-pub-section" style="min-height:4em;">
									<div style="float:right;margin-top:1em;">
										<input name="original_publish" type="hidden" id="original_publish" value="Update">
										<input name="save" type="submit"
											class="button-primary" id="publish"
											tabindex="5"
											value="<?php _e( 'Update' ); ?>" />
									</div>
									<div style="float:right;margin-top:1em;margin-right:1em;">
										<div id="preview-action">
											<?php
												$url = admin_url();
												$url = add_query_arg( array(
													'nelioab-page' => 'save-css',
													'preview' => 'true',
													'nelioab_preview_css' => $_REQUEST['css_id'] ), $url );
											?>
											<a id="preview-button" class="preview button" target="wp-preview" id="post-preview" tabindex="4"><?php _e( 'Preview' ); ?></a>
											<input type="hidden" name="wp-preview" id="wp-preview" value="" />
										</div>
									</div>
								</div>
								<script>
									jQuery("#preview-button").click(function(e) {
										var aux = jQuery("#goal_options").attr('value');
										if ( aux == -1 ) return;
										if ( aux == <?php echo NelioABController::FRONT_PAGE__YOUR_LATEST_POSTS; ?> ) aux = "";
										else aux = "&p=" + aux;
										jQuery("#css_edit_value").attr('action', "<?php echo $url ?>" + aux);
										jQuery("#css_edit_value").attr('target', '_blank');
										jQuery("#css_edit_value").submit();
										jQuery("#css_edit_value").attr('action', '#');
										jQuery("#css_edit_value").attr('target', '');
									});
								</script>
	
								<div style="margin:10px;">
									<b><?php _e( 'Go back to...', 'nelioab' ); ?></b>
									<?php
									$url        = admin_url() . 'admin.php?page=nelioab-experiments';
									$exp_id     = $this->experiment->get_id();
									$exp_status = $this->experiment->get_status();
									?>
									<ul style="margin-left:1.5em;">
										<?php
										require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
										switch( $exp_status ){
											case NelioABExperimentStatus::DRAFT:
											case NelioABExperimentStatus::READY:
									   		?><li><a href="<?php
													echo $url . '&action=edit&id=' . $exp_id . '&exp_type=' . $this->experiment->get_type(); ?>"><?php
													 _e( 'Editing this experiment', 'nelioab' ); ?></a></li><?php
												break;
											case NelioABExperimentStatus::RUNNING:
											case NelioABExperimentStatus::FINISHED:
									   		?><li><a href="<?php
													echo $url . '&action=progress&id=' . $exp_id . '&exp_type=' . $this->experiment->get_type(); ?>"><?php
													_e( 'The results of the related experiment', 'nelioab' ); ?></a></li><?php
												break;
											case NelioABExperimentStatus::TRASH:
											case NelioABExperimentStatus::PAUSED:
											default:
												// Nothing here
										}
										?>
									   <li><a href="<?php echo $url; ?>"><?php _e( 'My list of experiments', 'nelioab' ); ?></a></li>
									</ul>
								</div>
	
	
	
	
	
							</div>
						</div>
					</div>
				</div>
				<!-- END OF SIDEBAR -->

			</div>
		</form>
		</div>
		<?php
	}

}

?>
