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


require_once( NELIOAB_UTILS_DIR . '/admin-page.php' );
class NelioABCssEditPage extends NelioABAdminPage {
	
	public static function build() {
		$view = new NelioABCssEditPage();
		$view->set_icon( 'icon-tools' );
		$view->enable_two_columns( true );
		$view->render();
	}

	public function __construct() {
		$title = __( 'Edit CSS', 'nelioab' );
		parent::__construct( $title );
	}

	protected function do_render() {?>

		<!-- SIDEBAR -->
		<div id="side-info-column" class="inner-sidebar">
			<div id="submitdiv" class="postbox">
				<h3 style="cursor:auto;"><span><?php _e( 'Update' ); ?></span></h3>
				<div class="inside">
					<div class="submitbox" id="submitpost">

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
								<?php $preview_link = esc_url( apply_filters( 'preview_post_link', add_query_arg( 'preview', 'true' ) ) ); ?>
								<a class="preview button" href="<?php echo $preview_link; ?>" target="wp-preview" id="post-preview" tabindex="4"><?php _e( 'Preview' ); ?></a>
								<input type="hidden" name="wp-preview" id="wp-preview" value="" />
							</div>
							</div>
						</div>

						<div style="margin:10px;">
							<b><?php _e( 'Go back to...', 'nelioab' ); ?></b>
							<?php
							// TODO
							$url        = admin_url() . 'admin.php?page=nelioab-experiments';
							$exp_id     = -1;
							$exp_status = NelioABExperimentStatus::DRAFT;
							?>
							<ul style="margin-left:1.5em;">
								<?php
								require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
								switch( $exp_status ){
									case NelioABExperimentStatus::DRAFT:
									case NelioABExperimentStatus::READY:
							   		?><li><a href="<?php echo $url . '&action=edit&id=' . $exp_id; ?>"><?php _e( 'Editing this experiment', 'nelioab' ); ?></a></li><?php
										break;
									case NelioABExperimentStatus::RUNNING:
									case NelioABExperimentStatus::FINISHED:
							   		?><li><a href="<?php echo $url . '&action=progress&id=' . $exp_id; ?>"><?php _e( 'The results of the related experiment', 'nelioab' ); ?></a></li><?php
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


		<!-- MAIN CONTENT -->
		<div id="post-body">
			<div id="post-body-content">

				<h2>HOLA!</h2>

			</div>
		</div>
		<!-- END OF MAIN CONTENT -->

	<?php
	}

}

?>
