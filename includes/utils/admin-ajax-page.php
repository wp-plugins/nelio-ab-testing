<?php
/**
 * Copyright 2015 Nelio Software S.L.
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

if ( !class_exists( 'NelioABAdminAjaxPage' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-page.php' );

	/**
	 * This class is an abstract page prepared for loading content using AJAX.
	 *
	 * @since PHPDOC
	 * @package \NelioABTesting\Utils
	 */
	abstract class NelioABAdminAjaxPage extends NelioABAdminPage {

		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var boolean
		 */
		private $is_data_pending;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		private $controller_file;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var string
		 */
		private $controller_class;


		/**
		 * PHPDOC
		 *
		 * @since PHPDOC
		 * @var array
		 */
		private $post_params;


		/**
		 * It creates a new instance of this class.
		 *
		 * @param string $title The title of the page.
		 *
		 * @return NelioABAdminAjaxPage a new instance of this class.
		 *
		 * @since PHPDOC
		 */
		public function __construct( $title ) {
			parent::__construct( $title );
			$this->is_data_pending = false;
			$this->post_params = array();
		}


		/**
		 * PHPDOC
		 *
		 * @param string $name PHPDOC
		 * @param mixed  $val  PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function keep_request_param( $name, $val ) {
			array_push( $this->post_params, array( $name, $val ) );
		}


		/**
		 * PHPDOC
		 *
		 * @param string $controller_file  PHPDOC
		 * @param string $controller_class PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function get_content_with_ajax_and_render( $controller_file, $controller_class ) {
			$this->is_data_pending  = true;

			$controller_file = str_replace( '\\', '\\\\', $controller_file );
			$controller_file = str_replace( '"', '\\"', $controller_file );

			$this->controller_file  = $controller_file;
			$this->controller_class = $controller_class;

			$this->render();
		}


		// @Override
		public function render() { ?>
			<script type="text/javascript" src="<?php echo nelioab_admin_asset_link( '/js/tablesorter.min.js' ); ?>"></script><?php
			$is_data_pending_loader = 'display:none;';
			$is_data_pending_data = 'display:visible;';
			if ( $this->is_data_pending) {
				$is_data_pending_loader = 'display:visible;';
				$is_data_pending_data = 'display:none;';
			}
			?>
			<script type="text/javascript">
			function smoothTransitions() {
				jQuery("#ajax-loader-label2").hide().delay(10000).fadeIn('fast');
				jQuery("#poststuff").delay(100).fadeOut(150);
				jQuery("#errors-div").delay(100).fadeOut(150);
				jQuery("#error-message-div").delay(100).fadeOut(150);
				jQuery("#message-div").delay(100).fadeOut(150);
				jQuery("#ajax-loader").delay(260).fadeIn(150);
			}
			</script>
			<div class="wrap">
				<div class="icon32" id="<?php echo $this->icon_id; ?>"></div>
				<h2><?php echo $this->title . ' ' . $this->title_action; ?></h2>
				<?php
					if ( $this->is_data_pending ) {
						$this->print_global_warnings();
						$this->print_error_message( 'none' );
						$this->print_message( 'none' );
						$this->print_errors( 'none' );
					}
					else {
						$this->print_error_message();
						$this->print_message();
						$this->print_errors();
					}
				?>
				<br />
				<div id="ajax-loader" style="text-align:center;<?php echo $is_data_pending_loader; ?>">
					<br /><br />

					<div style="text-align:center;height:50px;">
						<div class="nelioab_spinner"></div>
					</div>
					<h2 style="color:#555;margin:0;padding:0;"><?php _e( 'Loading...', 'nelioab' ); ?></h2>
					<p id="ajax-loader-label1" style="color:#777;margin:0;padding:0;"><?php _e( 'Please, wait a moment.', 'nelioab' ); ?></p>
					<p id="ajax-loader-label2" style="color:#777;margin:0;padding:0;display:none;"><?php _e( 'Keep waiting...', 'nelioab' ); ?></p>
					<p id="ajax-loader-label3" style="color:#777;margin:0;padding:0;display:none;"><?php _e( 'Internet connection seems very slow.', 'nelioab' ); ?></p>
				</div>
				<div id="poststuff" class="metabox-hold" style="<?php echo $is_data_pending_data; ?>">
					<div id="ajax-data"><?php
					if ( !$this->is_data_pending ) {
						$this->do_render();
					?>
						<br />
						<div class="actions"><?php
							$this->print_page_buttons(); ?>
						</div><?php
					}
					?>
					</div>
				</div>
			</div><?php
			if ( !$this->is_data_pending ) { ?>
				<div id="dialog-modal" title="Basic modal dialog" style="display:none;">
					<div id="dialog-content">
						<?php $this->print_dialog_content(); ?>
					</div>
				</div>
			<?php
			}

			if ( $this->is_data_pending ) { ?>
			<script>

				function nelioabHideSpinnerAndShowContent() {
					var $ajaxLoader = jQuery("#ajax-loader");
					$ajaxLoader.fadeOut(200, function() {
						var $postStuff = jQuery("#poststuff");
						$postStuff.fadeIn(200);

						var aux;
						var $errMsgDiv = jQuery("#error-message-div");
						var $msgDiv = jQuery("#message-div");
						var $errorsDiv = jQuery("#errors-div");

						aux = jQuery.trim( jQuery("#error-message-box-delayed").html() );
						if ( aux.length > 0 ) {
							$errMsgDiv.addClass("to-be-shown");
							$errMsgDiv.html( aux );
						}
						aux = jQuery.trim( jQuery("#message-box-delayed").html() );
						if ( aux.length > 0 ) {
							$msgDiv.addClass("to-be-shown");
							$msgDiv.html( aux );
						}
						aux = jQuery.trim( jQuery("#errors-box-delayed").html() );
						if ( aux.length > 0 ) {
							$errorsDiv.addClass("to-be-shown");
							$errorsDiv.html( aux );
						}

						if ( $errMsgDiv.hasClass("to-be-shown") ) {
							$errMsgDiv.css('display','block');
							$errMsgDiv.hide();
							$errMsgDiv.fadeIn(200);
						}
						if ( $msgDiv.hasClass("to-be-shown") ) {
							$msgDiv.css('display','block');
							$msgDiv.hide();
							$msgDiv.fadeIn(200);
						}
						if ( $errorsDiv.hasClass("to-be-shown") ) {
							$errorsDiv.css('display','block');
							$errorsDiv.hide();
							$errorsDiv.fadeIn(200);
						}

						jQuery(document).trigger('nelioab-ajax-page-loaded');
					});
				}

				jQuery(document).ready(function() {

					var data = {
						"action" : "nelioab_get_html_content",<?php
						foreach ( $this->post_params as $param )
							echo "\n\t\t\t\t\t\t\"$param[0]\" : \"$param[1]\",";
						?>

						"filename"  : "<?php echo $this->controller_file; ?>",
						"classname" : "<?php echo $this->controller_class; ?>"
					};

					// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
					jQuery.ajax({
						type: 'POST',
						url:  ajaxurl,
						data: data,
						success: function(response){
							jQuery("#poststuff > #ajax-data").html(response);
							nelioabHideSpinnerAndShowContent();
						},
						error: function() {
							jQuery("#ajax-loader").html(
							"<?php
								printf( "<img src='%s' alt='%s' />",
									nelioab_asset_link( '/admin/images/error-icon.png' ),
									__( 'Funny image to graphically notify of an error.', 'nelioab' )
							); ?>" +
							"<h2 style='color:#555;margin:0;padding:0;'><?php
								_e( 'Oops! There was an AJAX-related error.' );
							?></h2>" +
							"<div style='color:#999;text-align:left;max-width:600px;margin:auto;'>" +
								"<br /><br /><b>Details:</b><br />" +
								"<u>Class</u>: " + data.classname + "<br />" +
								"<u>File</u>: " + data.filename + "<br />" +
							"</div>");
						}
					});

					jQuery("#ajax-loader-label2").hide().delay(10000).fadeIn('fast');
					//jQuery("#ajax-loader-label3").hide().delay(15000).fadeIn('fast');
				});
			</script>
			<?php
			}
		}


		/**
		 * PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public function render_content() {
			$this->do_render();
			?>
			<div id="dialog-modal" title="Basic modal dialog" style="display:none;">
				<div id="dialog-content">
					<?php
					if ( !$this->is_data_pending )
						$this->print_dialog_content();
					?>
				</div>
			</div>
			<div id="error-message-box-delayed" style="display:none;">
				<?php $this->print_error_message_content(); ?>
			</div>
			<div id="message-box-delayed" style="display:none;">
				<?php $this->print_message_content(); ?>
			</div>
			<div id="errors-box-delayed" style="display:none;">
				<?php $this->print_errors_content(); ?>
			</div>
			<br />
			<div class="actions"><?php
				$this->print_page_buttons(); ?>
			</div><?php
		}


		// @Override
		protected function make_form_javascript( $form_name, $hidden_action ) {
			return sprintf(
				' onclick="javascript:' .
				'smoothTransitions();' .
				'jQuery(\'#%1$s > #action\').attr(\'value\', \'%2$s\');' .
				'jQuery(\'#%1$s\').submit();" ',
				$form_name, $hidden_action
			);
		}

	}//NelioABAdminAjaxPage

}

