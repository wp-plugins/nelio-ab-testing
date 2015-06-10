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

if ( !class_exists( 'NelioABHtmlGenerator' ) ) {

	/**
	 * PHPDOC
	 *
	 * @since PHPDOC
	 * @package \NelioABTesting\Utils
	 */
	abstract class NelioABHtmlGenerator extends WP_List_Table {

		/**
		 * PHPDOC
		 *
		 * @param string         $filter_url  PHPDOC
		 * @param array          $filters     PHPDOC
		 * @param string         $filter_name PHPDOC
		 * @param string|boolean $current     PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public static function print_filters( $filter_url, $filters, $filter_name, $current = false ) { ?>
			<ul class='subsubsub'><?php
				// Default filter
				$filter = $filters[0];
				echo ( sprintf (
						'<li class="%s"><a href="%s" class="%s">%s <span class="count">(%s)</span></a></li>',
						$filter['value'],
						$filter_url,
						( $filter['value'] == $current ) ? 'current' : '',
						$filter['label'],
						$filter['count']
					)	);

				// The rest of the filters
				for ( $i = 1; $i < count( $filters); ++$i ) {
					$filter = $filters[$i];
					if ( $filter['count'] == 0 )
						continue;
					echo ( sprintf (
						' | <li class="%s"><a href="%s&%s=%s" class="%s">%s <span class="count">(%s)</span></a></li>',
						$filter['value'],
						$filter_url,
						$filter_name,
						$filter['value'],
						( $filter['value'] == $current ) ? 'current' : '',
						$filter['label'],
						$filter['count']
					)	);
				} ?>
			</ul><?php
		}


		/**
		 * PHPDOC
		 *
		 * @param int           $mode        PHPDOC
		 * @param mixed         $value       PHPDOC
		 * @param array|boolean $valid_modes PHPDOC
		 *
		 * @return void
		 *
		 * @see NelioABExperiment::FINALIZATION_MANUAL,
		 * @see NelioABExperiment::FINALIZATION_AFTER_DATE,
		 * @see NelioABExperiment::FINALIZATION_AFTER_VIEWS,
		 * @see NelioABExperiment::FINALIZATION_AFTER_CONFIDENCE,
		 *
		 * @since PHPDOC
		 */
		public static function print_finalization_mode_field( $mode, $value, $valid_modes = false ) {
			require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
			// TODO: canviar-ho per empty array
			if ( !$valid_modes ) {
				$valid_modes = array(
					NelioABExperiment::FINALIZATION_MANUAL,
					NelioABExperiment::FINALIZATION_AFTER_DATE,
					NelioABExperiment::FINALIZATION_AFTER_VIEWS,
					NelioABExperiment::FINALIZATION_AFTER_CONFIDENCE,
				);
			}
			?>
			<select
					id="exp_finalization_mode"
					name="exp_finalization_mode"
					style="width:100%;max-width:350px;margin-bottom:1.5em;">
				<?php
				$current_mode = NelioABExperiment::FINALIZATION_MANUAL;
				if ( in_array( $current_mode, $valid_modes ) ) { ?>
					<option
						<?php if ( $current_mode == $mode ) echo 'selected="selected"'; ?>
						value="<?php echo $current_mode; ?>"
							><?php _e( 'Manual', 'nelioab' ); ?></option><?php
				} ?>
				<?php
				$current_mode = NelioABExperiment::FINALIZATION_AFTER_DATE;
				if ( in_array( $current_mode, $valid_modes ) ) { ?>
					<option
						<?php if ( $current_mode == $mode ) echo 'selected="selected"'; ?>
						value="<?php echo $current_mode; ?>"
							><?php _e( 'Duration', 'nelioab' ); ?></option><?php
				} ?>
				<?php
				$current_mode = NelioABExperiment::FINALIZATION_AFTER_VIEWS;
				if ( in_array( $current_mode, $valid_modes ) ) { ?>
					<option
						<?php if ( $current_mode == $mode ) echo 'selected="selected"'; ?>
						value="<?php echo $current_mode; ?>"
							><?php _e( 'Number of Page Views', 'nelioab' ); ?></option><?php
				} ?>
				<?php
				$current_mode = NelioABExperiment::FINALIZATION_AFTER_CONFIDENCE;
				if ( in_array( $current_mode, $valid_modes ) ) { ?>
					<option
						<?php if ( $current_mode == $mode ) echo 'selected="selected"'; ?>
						value="<?php echo $current_mode; ?>"
							><?php _e( 'Winning Alternative Reaches a Minimum Confidence', 'nelioab' ); ?></option><?php
				} ?>
			</select>
			<input
				id="exp_finalization_value"
				name="exp_finalization_value"
				type="hidden"
				value="<?php echo $value; ?>" />
			<div class="fin-mode manual" style="display:block;"><?php
					_e( 'The experiment will be running until you manually stop it.', 'nelioab' );
			?></div>
			<div class="fin-mode date" style="display:none;">
				<p style="margin-bottom:0.2em;"><strong><?php _e( 'Mode Configuration', 'nelioab' ); ?></strong></p><?php
				printf(
					__( 'The experiment will be runnning for %s and will then be automatically stopped.', 'nelioab' ),
					'<select class="fin-mode-value">' .
						'<option value="1">' . __( '24 hours', 'nelioab' ) . '</option>' .
						'<option value="2">' . __( '48 hours', 'nelioab' ) . '</option>' .
						'<option value="5">' . __( '5 days', 'nelioab' ) . '</option>' .
						'<option value="7">' . __( '1 week', 'nelioab' ) . '</option>' .
						'<option value="14">' . __( '2 weeks', 'nelioab' ) . '</option>' .
						'<option value="30">' . __( '1 month', 'nelioab' ) . '</option>' .
						'<option value="60">' . __( '2 months', 'nelioab' ) . '</option>' .
					'</select>'
				);
			?></div>
			<div class="fin-mode views" style="display:none;">
				<p style="margin-bottom:0.2em;"><strong><?php _e( 'Mode Configuration', 'nelioab' ); ?></strong></p><?php
				printf(
					__( 'If the tested page (and its alternatives) have been seen over %s times, the experiment will be automatically stopped.', 'nelioab' ),
					'<select class="fin-mode-value">' .
						'<option value="100">100</option>' .
						'<option value="200">200</option>' .
						'<option value="500">500</option>' .
						'<option value="1000">1,000</option>' .
						'<option value="2000">2,000</option>' .
						'<option value="5000">5,000</option>' .
						'<option value="10000">10,000</option>' .
						'<option value="15000">15,000</option>' .
						'<option value="20000">20,000</option>' .
						'<option value="50000">50,000</option>' .
						'<option value="100000">100,000</option>' .
					'</select>'
				);
				$value = NelioABSettings::get_quota_limit_per_exp();
				if ( -1 != $value ) { ?>
					<div id="quota-warning" style="display:none;">
						<div style="width:100%;max-width:100px;padding-top:0.9em;border-bottom:1px solid #ccc;">&nbsp;</div>
						<p style="font-size:90%;"><?php
							printf(
									__( 'According to your global settings, there\'s a Quota Limit of %s page views per experiment. For this experiment, however, the global setting will be overriden.', 'nelioab' ),
									number_format_i18n( $value )
								); ?></p>
					</div>
					<script>
						(function($) {
							var warning = $('#quota-warning');
							$('.fin-mode-value').on('change', function() {
								if ( $(this).attr('value') <= <?php echo $value; ?> )
									warning.hide();
								else
									warning.show();
							});
						})(jQuery);
					</script>
					<?php
				}
			?></div>
			<div class="fin-mode confidence" style="display:none;">
				<p style="margin-bottom:0.2em;"><strong><?php _e( 'Mode Configuration', 'nelioab' ); ?></strong></p><?php
				printf(
					__( '%s the experiment will be automatically stopped.', 'nelioab' ),
					'<select class="fin-mode-value">' .
						'<option' . self::select_confidence( 99 )    . 'value="99">' . __( '99% - If we are absolutely confident there\'s a clear winner', 'nelioab' ) . '</option>' .
						'<option' . self::select_confidence( 98 )    . 'value="98">' . __( '98% - If we are extremely confident there\'s a clear winner', 'nelioab' ) . '</option>' .
						'<option' . self::select_confidence( 97 )    . 'value="97">' . __( '97% - If we are quite confident there\'s a winner', 'nelioab' ) . '</option>' .
						'<option' . self::select_confidence( 96 )    . 'value="96">' . __( '96% - If we are confident there\'s a winner', 'nelioab' ) . '</option>' .
						'<option' . self::select_confidence( 95 )    . 'value="95">' . __( '95% - If we are slightly confident there\'s a winner', 'nelioab' ) . '</option>' .
						'<option' . self::select_confidence( 90, 0 ) . 'value="90">' . __( '90% - If it is possible that there\'s a winner', 'nelioab' ) . '</option>' .
					'</select>'
				);
			?></div>
			<script type="text/javascript">
			(function($) {
				// Functions
				function switch_finalization_mode( mode, value ) {
					$('div.fin-mode').hide();
					var block;
					switch ( mode ) {
						case <?php echo NelioABExperiment::FINALIZATION_MANUAL; ?>:
							block = 'div.fin-mode.manual';
							break;
						case <?php echo NelioABExperiment::FINALIZATION_AFTER_DATE; ?>:
							block = 'div.fin-mode.date';
							break;
						case <?php echo NelioABExperiment::FINALIZATION_AFTER_VIEWS; ?>:
							block = 'div.fin-mode.views';
							break;
						case <?php echo NelioABExperiment::FINALIZATION_AFTER_CONFIDENCE; ?>:
							block = 'div.fin-mode.confidence';
							break;
						default:
							return;
					}
					if ( undefined != value )
						$(block + ' .fin-mode-value').attr('value', value);
					$('#exp_finalization_value').attr('value',
						$(block + ' .fin-mode-value').attr('value') );
					$(block).show();
				}
				// Events
				$('#exp_finalization_mode').on('change', function() {
					switch_finalization_mode( parseInt( $('#exp_finalization_mode').attr('value') ) );
				});
				$('.fin-mode .fin-mode-value').on('change', function() {
					$('#exp_finalization_value').attr('value', $(this).attr('value') );
				});
				// Initialization
				switch_finalization_mode(
					parseInt( $('#exp_finalization_mode').attr('value') ),
					parseInt( $('#exp_finalization_value').attr('value') )
				);
			})(jQuery);
			</script><?php
		}


		/**
		 * PHPDOC
		 *
		 * @param int $max PHPDOC
		 * @param int $min PHPDOC
		 *
		 * @return string PHPDOC
		 *
		 * @since PHPDOC
		 */
		private static function select_confidence( $max, $min = -1 ) {
			if ( $min === -1 ) {
				$min = $max;
				++$max;
			}
			$confidence = NelioABSettings::get_min_confidence_for_significance();
			if ( $min <= $confidence && $confidence < $max )
				return ' selected="selected" ';
			else
				return ' ';
		}


		/**
		 * PHPDOC
		 *
		 * @param int|boolean $id PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public static function print_scheduling_picker( $id = false ) {
			/** @var WP_Locale $wp_locale */
			global $wp_locale;
			$style = ' style="vertical-align:top;height:28px;max-width:';

			$month = '<label for="mm" class="screen-reader-text">' . __( 'Month' ) . '</label>';
			$month .= '<select' . $style . '9em;" name="mm" class="mm">'."\n";
			for ( $i = 1; $i < 13; $i = $i +1 ) {
				$monthnum = zeroise($i, 2);
				$month .= "\t\t\t" . '<option value="' . $monthnum . '">' . $wp_locale->get_month( $i ) . "</option>\n";
			}
			$month .= '</select>';

			$day = '<label for="jj" class="screen-reader-text">' . __( 'Day' ) . '</label>' .
				'<input type="text" name="jj" ' . $style . '4em;" size="2" maxlength="2" autocomplete="off" ' .
				'placeholder="' . __( 'Day', 'nelioab' ) . '" class="jj"/>';

			$year = '<label for="aa" class="screen-reader-text">' . __( 'Year' ) . '</label>' .
				'<input type="text" name="aa" ' . $style . '6em;" size="4" maxlength="4" autocomplete="off" ' .
				'placeholder="' . __( 'Year', 'nelioab' ) . '" class="aa" />';

			if ( $id )
				$id = 'id="' . $id . '"';
			else
				$id = '';
			echo '<div ' . $id . ' class="timestamp-wrap" style="vertical-align:bottom;">';
			printf( __( '%1$s %2$s, %3$s', 'nelioab' ), $month, $day, $year );
			echo '</div>';
		}


		/**
		 * PHPDOC
		 *
		 * @param string $jquery_bypass_elems PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public static function print_unsaved_changes_control( $jquery_bypass_elems ) { ?>
			<script type="text/javascript">
			(function($) {
				$(document).ready(function() {
					var unsavedChanges = false;
					var isControllerBypassed = false;
					function attention() {
						unsavedChanges = true;
						$('input[type="text"],input[type="hidden"],textarea,select').
							unbind('change',attention);
						$('#poststuff a').unbind('click',attention);
					}
					function bypassController() {
						isControllerBypassed = true;
					}
					$('input[type="text"],input[type="hidden"],textarea,select').
						on('change',attention);
					$(document).bind( 'DOMNodeInserted', function(event) {
						$(event.relatedNode).find('<?php echo $jquery_bypass_elems; ?>').unbind('click', bypassController);
						$(event.relatedNode).find('<?php echo $jquery_bypass_elems; ?>').on('click', bypassController);
					});
					$('#poststuff a').on('click',attention);
					$('<?php echo $jquery_bypass_elems; ?>').on('click', bypassController);
					window.onbeforeunload = function() {
						if ( unsavedChanges && !isControllerBypassed )
							return "<?php
								echo str_replace( '"', '\\"', __( 'The changes you made will be lost if you navigate away from this page.', 'nelioab' ) );
							?>";
						isControllerBypassed = false;
					};
				});
			})(jQuery);
			</script>
			<?php
		}


		/**
		 * PHPDOC
		 *
		 * @param string         $field_id    PHPDOC
		 * @param string|boolean $value       PHPDOC
		 * @param string         $drafts      PHPDOC
		 * @param array          $classes     PHPDOC
		 * @param boolean        $autoconvert PHPDOC
		 *
		 *
		 * @return string The searcher HTML element.
		 *
		 * @see self::print_TODO()
		 *
		 * @since PHPDOC
		 */
		public static function get_page_searcher(
				$field_id, $value = false, $drafts = 'no-drafts', $classes = array(), $autoconvert = true ) {
			ob_start();
			self::print_page_searcher( $field_id, $value, $drafts, $classes, $autoconvert );
			$value = ob_get_contents();
			ob_end_clean();
			return $value;
		}


		/**
		 * PHPDOC
		 *
		 * @param string         $field_id    PHPDOC
		 * @param string|boolean $value       PHPDOC
		 * @param string         $drafts      PHPDOC
		 * @param array          $classes     PHPDOC
		 * @param boolean        $autoconvert PHPDOC
		 *
		 *
		 * @return string The searcher HTML element.
		 *
		 * @see self::print_TODO()
		 *
		 * @since PHPDOC
		 */
		public static function get_post_searcher(
				$field_id, $value = false, $drafts = 'no-drafts', $classes = array(), $autoconvert = true ) {
			ob_start();
			self::print_post_searcher( $field_id, $value, $drafts, $classes, $autoconvert );
			$value = ob_get_contents();
			ob_end_clean();
			return $value;
		}


		/**
		 * This function prints a searcher that searches for pages, posts, and custom elements, and includes the latest posts and the landing page.
		 *
		 * @param string         $field_id    PHPDOC
		 * @param string|boolean $value       PHPDOC
		 * @param string         $drafts      PHPDOC
		 * @param array          $classes     PHPDOC
		 * @param boolean        $autoconvert PHPDOC
		 *
		 * @return void
		 *
		 * @see self::print_post_searcher_based_on_type
		 *
		 * @since PHPDOC
		 */
		public static function print_full_searcher(
				$field_id, $value = false, $drafts = 'no-drafts', $classes = array(), $autoconvert = true ) {
			self::print_post_searcher_based_on_type(
				$field_id, $value, $drafts, $classes, $autoconvert,
				array( 'nelioab-all-post-types', 'nelioab-latest-posts', 'nelioab-theme-landing-page' ) );
		}


		/**
		 * This function prints a page, post, or custom element searcher.
		 *
		 * @param string         $field_id    PHPDOC
		 * @param string|boolean $value       PHPDOC
		 * @param string         $drafts      PHPDOC
		 * @param array          $classes     PHPDOC
		 * @param boolean        $autoconvert PHPDOC
		 *
		 * @return void
		 *
		 * @see self::print_post_searcher_based_on_type
		 *
		 * @since PHPDOC
		 */
		public static function print_any_post_type_searcher(
				$field_id, $value = false, $drafts = 'no-drafts', $classes = array(), $autoconvert = true ) {
			self::print_post_searcher_based_on_type(
				$field_id, $value, $drafts, $classes, $autoconvert, array( 'nelioab-all-post-types' ) );
		}


		/**
		 * This function prints a page/post searcher.
		 *
		 * @param string         $field_id    PHPDOC
		 * @param string|boolean $value       PHPDOC
		 * @param string         $drafts      PHPDOC
		 * @param array          $classes     PHPDOC
		 * @param boolean        $autoconvert PHPDOC
		 *
		 * @return void
		 *
		 * @see self::print_post_searcher_based_on_type
		 *
		 * @since PHPDOC
		 */
		public static function print_page_or_post_searcher(
				$field_id, $value = false, $drafts = 'no-drafts', $classes = array(), $autoconvert = true ) {
			self::print_post_searcher_based_on_type(
				$field_id, $value, $drafts, $classes, $autoconvert, array( 'page', 'post' ) );
		}


		/**
		 * This function prints a page searcher.
		 *
		 * @param string         $field_id    PHPDOC
		 * @param string|boolean $value       PHPDOC
		 * @param string         $drafts      PHPDOC
		 * @param array          $classes     PHPDOC
		 * @param boolean        $autoconvert PHPDOC
		 *
		 * @return void
		 *
		 * @see self::print_post_searcher_based_on_type
		 *
		 * @since PHPDOC
		 */
		public static function print_page_searcher(
				$field_id, $value = false, $drafts = 'no-drafts', $classes = array(), $autoconvert = true ) {
			self::print_post_searcher_based_on_type(
				$field_id, $value, $drafts, $classes, $autoconvert, array( 'page' ) );
		}


		/**
		 * This function prints a post searcher.
		 *
		 * @param string         $field_id    PHPDOC
		 * @param string|boolean $value       PHPDOC
		 * @param string         $drafts      PHPDOC
		 * @param array          $classes     PHPDOC
		 * @param boolean        $autoconvert PHPDOC
		 *
		 * @return void
		 *
		 * @see self::print_post_searcher_based_on_type
		 *
		 * @since PHPDOC
		 */
		public static function print_post_searcher(
				$field_id, $value = false, $drafts = 'no-drafts', $classes = array(), $autoconvert = true ) {
			self::print_post_searcher_based_on_type(
				$field_id, $value, $drafts, $classes, $autoconvert, array( 'post' ) );
		}


		/**
		 * This function prints a post type searcher.
		 *
		 * @param string         $field_id       PHPDOC
		 * @param string|boolean $value          PHPDOC
		 * @param string         $drafts         PHPDOC
		 * @param string         $post_type_name PHPDOC
		 * @param array          $classes        PHPDOC
		 * @param boolean        $autoconvert    PHPDOC
		 *
		 * @return void
		 *
		 * @see self::print_post_searcher_based_on_type
		 *
		 * @since PHPDOC
		 */
		public static function print_post_type_searcher(
			$field_id, $value = false, $drafts = 'no-drafts', $post_type_name, $classes = array(), $autoconvert = true ) {
			self::print_post_searcher_based_on_type(
				$field_id, $value, $drafts, $classes, $autoconvert, array( $post_type_name ) );
		}


		/**
		 * PHPDOC
		 *
		 * @param string         $field_id    PHPDOC
		 * @param string|boolean $value       PHPDOC
		 * @param array          $classes     PHPDOC
		 * @param boolean        $autoconvert PHPDOC
		 *
		 *
		 * @return string The searcher HTML element.
		 *
		 * @see self::print_TODO()
		 *
		 * @since PHPDOC
		 */
		public static function get_form_searcher(
				$field_id, $value = false, $classes = array(), $autoconvert = true ) {
			ob_start();
			self::print_form_searcher(
				$field_id, $value, $classes, $autoconvert );
			$value = ob_get_contents();
			ob_end_clean();
			return $value;
		}


		/**
		 * This function prints a form searcher.
		 *
		 * PHPDOC
		 *
		 * @param string         $field_id    PHPDOC
		 * @param string|boolean $value       PHPDOC
		 * @param array          $classes     PHPDOC
		 * @param boolean        $autoconvert PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public static function print_form_searcher(
				$field_id, $value = false, $classes = array(), $autoconvert = true ) {
			self::print_post_searcher_based_on_type(
				$field_id, $value, 'no-drafts', $classes, $autoconvert, array( 'form' ) );
		}


		/**
		 * PHPDOC
		 *
		 * @param string         $field_id    PHPDOC
		 * @param string|boolean $value       PHPDOC
		 * @param string         $drafts      PHPDOC
		 * @param array          $classes     PHPDOC
		 * @param boolean        $autoconvert PHPDOC
		 * @param array          $types       PHPDOC
		 *
		 * @return void
		 *
		 * @since PHPDOC
		 */
		public static function print_post_searcher_based_on_type(
				$field_id, $value, $drafts, $classes, $autoconvert, $types ) {
			$placeholder = __( 'Select an option...', 'nelioab' );
			if ( count( $types ) == 1 ) {
				switch ( $types[0] ) {
					case 'page':
						$placeholder = __( 'Select a page...', 'nelioab' );
						break;
					case 'post':
						$placeholder = __( 'Select a post...', 'nelioab' );
						break;
					case 'form':
						$placeholder = __( 'Select a form...', 'nelioab' );
						$drafts = '';
						break;
					case 'nelioab-all-post-types':
						$placeholder = __( 'Select a page, post, or custom element...', 'nelioab' );
						break;
				}
			}
			else {
				if ( in_array( 'page', $types ) && in_array( 'post', $types ) )
					$placeholder = __( 'Select a page or post...', 'nelioab' );
			}
			$searcher_type = 'post-searcher ' . implode( ' ', $types );
			if ( count( $types ) == 1 && 'form' == $types[0] )
				$searcher_type = 'form-searcher';

			if ( strlen( $drafts ) > 0 )
				$drafts = ', "' . $drafts . '"';
			?>
			<input
				id="<?php echo $field_id; ?>" name="<?php echo $field_id; ?>"
				data-type="<?php echo esc_html( json_encode( $types ) ); ?>"
				data-placeholder="<?php echo $placeholder; ?>"
				type="hidden" class="<?php
					echo $searcher_type; ?> <?php
					echo implode( ' ', $classes ); ?>"
				value="<?php echo $value; ?>" /><?php
			if ( $autoconvert ) { ?>
				<script type="text/javascript">
				(function($) {
					var field = $("#<?php echo $field_id; ?>");
					var NelioABSearcher = NelioAB<?php
						if ( 'form-searcher' == $searcher_type )
							echo 'Form';
						else
							echo 'Post';
					?>Searcher;
					NelioABSearcher.buildSearcher(field, <?php echo json_encode( $types ); echo $drafts; ?> );
					<?php
						if ( $value !== false )
							echo 'NelioABSearcher.setDefault(field, ' . json_encode( $types ) . $drafts . ');';
						echo "\n";
					?>
				})(jQuery);
				</script><?php
			} ?>
			<?php
		}

	}//NelioABHtmlGenerator

}

