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

			<!-- SHARING -->
			<div class="share_pre">
				<h2><?php _e( 'Together, we\'ll build a better service!', 'nelioab' ); ?></h2>

				<p><?php
					_e( 'We\'re honoured you\'re using our service. We hope Nelio A/B Testing is meeting your expectations. Together, we\'ll get your site to the next level!', 'nelioab' );
				?></p>

				<p><?php
					_e( 'If you\'re happy with our service, please help us <b>spread the word and let others know about your experience with Nelio A/B Testing</b>!', 'nelioab' );
				?></p>

			</div>

			<!-- SHARING -->
			<div class="share" style="height:3em;">
				<!-- TWITTER -->
				<div style="float:left; min-width: 130px;">
				<a href="https://twitter.com/share" class="twitter-share-button" data-url="http://nelioabtesting.com/" data-text="I'm using Nelio A/B Testing for #WordPress by @NelioSoft and it rocks! Pages, headlines, widgets, heatmaps...">Tweet</a>
				</div>

				<!-- FACEBOOK -->
				<div style="float:left; min-width: 185px;">
				<div class="fb-like" data-href="http://nelioabtesting.com" data-send="false" data-layout="button_count" data-width="450" data-show-faces="true" data-action="recommend"></div>
				<div id="fb-root"></div>
				</div>

				<!-- GOOGLE PLUS -->
				<div style="float:left; min-width: 140px;">
				<div class="g-plus" data-action="share" data-annotation="bubble" data-href="http://nelioabtesting.com/"></div>
				</div>

				<!-- LINKEDIN -->
				<div style="float:left;">
				<script src="//platform.linkedin.com/in.js" type="text/javascript"> lang: en_US</script><script type="IN/Share" data-url="http://nelioabtesting.com/" data-counter="right"></script>
				</div>
			</div>

			<p><?php
				printf(
					__( 'And, please, don\'t forget to <a href="%s" target="_blank">rate the plugin in the WordPress Plugin Directory</a>!', 'nelioab' ),
					'http://wordpress.org/plugins/nelio-ab-testing/' );
			?></p>

			<!-- SCRIPTS FOR SHARING -->
			<script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+'://platform.twitter.com/widgets.js';fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');</script>
			<script>(function(d, s, id) {
			  var js, fjs = d.getElementsByTagName(s)[0];
			  if (d.getElementById(id)) return;
			  js = d.createElement(s); js.id = id;
			  js.src = "//connect.facebook.net/es_LA/all.js#xfbml=1";
			  fjs.parentNode.insertBefore(js, fjs);
			}(document, 'script', 'facebook-jssdk'));</script>
			<script type="text/javascript">
			  (function() {
			    var po = document.createElement('script'); po.type = 'text/javascript'; po.async = true;
			    po.src = 'https://apis.google.com/js/plusone.js';
			    var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(po, s);
			  })();
			</script>


			<!-- FEEDBACK -->
			<div class="feedback_pre" style="margin-top:6em;">
				<h2><?php _e( 'Your opinion matters!', 'nelioab' ); ?></h2>

				<p><?php
					_e( 'Would you like to request a new feature? Do you have any doubt using our service? Have you encountered any problems?', 'nelioab' );
				?></p>

				<p><?php
					_e( 'Please, <b>do not hesitate to contact us and tell what you are thinking!</b>', 'nelioab' );
				?></p>

			</div>

			<form id="nelioab_feedback_form" method="post">
			<input type="hidden" name="nelioab_feedback_form" value="true" />
			<?php
			$this->make_section(
				__( 'Contact with Nelio', 'nelioab' ),
				array(
					array(
						'label'     => __( 'Message', 'nelioab' ),
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
					__( 'Send Comment', 'nelioab' ),
					'javascript:void(0);sendFeedback();', true
				); ?>
			<div id="fb-processing" style="display:inline-block;">
				<div id="fb-sending" style="display:none;vertical-align:middle;">
					<img src="<?php echo nelioab_asset_link( '/images/loading-small.gif' ); ?>" alt="<?php _e( 'Sending...', 'nelioab' ); ?>" />
				</div>
				<div id="fb-fail" style="display:none;"><?php _e( 'There was an error while sending your comment. Please, try again.', 'nelioab' ); ?> </div>
				<div id="fb-ok" style="display:none;"><?php _e( 'Comment successfully sent.', 'nelioab' ); ?> </div>
			</div>
			</div>

			<script>

			function enableButton() {
				$ = jQuery;
				button = $("#fb-controls > a");
				button.removeClass("disabled");
				button.attr("href", "javascript:void(0);sendFeedback();");
			}

			function disableButton() {
				$ = jQuery;
				button = $("#fb-controls > a");
				button.addClass("disabled");
				button.attr("href", "javascript:void(0);");
			}

			function sendFeedback() {
				$ = jQuery;
				button = $("#fb-controls > a");
				if ( button.hasClass("disabled") )
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

