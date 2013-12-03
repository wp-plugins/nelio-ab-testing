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


if ( !class_exists( 'NelioABFeedbackPage' ) ) {

	require_once( NELIOAB_UTILS_DIR . '/admin-ajax-page.php' );
	class NelioABFeedbackPage extends NelioABAdminAjaxPage {

		public function __construct( $title ) {
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
		}

		protected function do_render() { ?>
			<form id="nelioab_feedback_form" method="post">
			<input type="hidden" name="nelioab_feedback_form" value="true" />
			<?php
			$this->make_section(
				__( 'Feedback &mdash; Your opinion matters; Tell us what you think!', 'nelioab' ),
				array(
					array(
						'label'     => __( 'Share your thoughts with us', 'nelioab' ),
						'id'        => 'content',
						'callback'  => array ( $this, 'print_textarea' ),
						'mandatory' => 'true'
					),
				)
			);
			?>
			</form>

			<div id="fb-controls" style="height:48px;">
			<?php echo $this->make_button(
					__( 'Send Feedback', 'nelioab' ),
					'javascript:void(0);sendFeedback();', true
				); ?>
			<div id="fb-processing" style="display:inline-block;">
				<div id="fb-sending" style="display:none;vertical-align:middle;">
					<img src="<?php echo NELIOAB_ASSETS_URL . '/images/loading-small.gif?' . NELIOAB_PLUGIN_VERSION; ?>" alt="<?php _e( 'Sending...', 'nelioab' ); ?>" />
				</div>
				<div id="fb-fail" style="display:none;"><?php _e( 'There was an error while sending your comment. Please, try again.', 'nelioab' ); ?> </div>
				<div id="fb-ok" style="display:none;"><?php _e( 'Comment successfully sent.', 'nelioab' ); ?> </div>
			</div>
			</div>

			<script>

			function enableButton() {
				$ = jQuery;
				button = $("#fb-controls > a");
				button.removeClass("button-primary-disabled");
				button.attr("href", "javascript:void(0);sendFeedback();");
			}

			function disableButton() {
				$ = jQuery;
				button = $("#fb-controls > a");
				button.addClass("button-primary-disabled");
				button.attr("href", "javascript:void(0);");
			}

			function sendFeedback() {
				$ = jQuery;
				button = $("#fb-controls > a");
				if ( button.hasClass("button-primary-disabled") )
					return;
				disableButton();
				$("#fb-processing").fadeOut(300, function() {
					$("#fb-ok").css('display', 'none');
					$("#fb-fail").css('display', 'none');
					$("#fb-sending").css('display', 'inline-block');
					$("#fb-processing").fadeIn(300);
				});
				$.post( document.url, $('#nelioab_feedback_form').serialize(), function(response) {
					if ( response.indexOf( "OK" ) != -1 ) {
						$("#content").val("");
						$("#fb-processing").fadeOut(300, function() {
							$("#fb-ok").css('display', 'inline-block');
							$("#fb-fail").css('display', 'none');
							$("#fb-sending").css('display', 'none');
							$("#fb-processing").fadeIn(300);
							enableButton();
						});
					}
					else {
						$("#fb-processing").fadeOut(300, function() {
							$("#fb-ok").css('display', 'none');
							$("#fb-fail").css('display', 'inline-block');
							$("#fb-sending").css('display', 'none');
							$("#fb-processing").fadeIn(300);
							enableButton();
						});
					}
				} );
			}

			</script>

		<?php
		}

		public function print_textarea() { ?>
			<textarea id="content" name="content" cols="80" rows="4" maxlength="450"></textarea>
		<?php
		}

	}//NelioABFeedbackPage

}

?>
