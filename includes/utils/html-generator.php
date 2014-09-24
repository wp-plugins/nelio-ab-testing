<?php
/**
 * Copyright 2013 Nelio Software S.L.
 * This script is distributed under the terms of the GNU General Public
 * License.
 *
 * This script is free software: you can redistribute it and/or modify it under
 * the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License.
 * This script is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS
 * FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with
 * this program. If not, see <http://www.gnu.org/licenses/>.
 */


if ( !class_exists( 'NelioABHtmlGenerator' ) ) {

	abstract class NelioABHtmlGenerator extends WP_List_Table {

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

		public static function get_page_searcher(
				$field_id, $value = false, $classes = array(), $autoconvert = true ) {
			ob_start();
			self::print_page_searcher( $field_id, $value, $classes, $autoconvert );
			$value = ob_get_contents();
			ob_end_clean();
			return $value;
		}

		public static function get_post_searcher(
				$field_id, $value = false, $classes = array(), $autoconvert = true ) {
			ob_start();
			self::print_post_searcher( $field_id, $value, $classes, $autoconvert );
			$value = ob_get_contents();
			ob_end_clean();
			return $value;
		}

		public static function print_full_searcher(
				$field_id, $value = false, $classes = array(), $autoconvert = true ) {
			self::print_post_searcher_based_on_type(
				$field_id, $value, $classes, $autoconvert, 'page-or-post-or-latest' );
		}

		public static function print_page_or_post_searcher(
				$field_id, $value = false, $classes = array(), $autoconvert = true ) {
			self::print_post_searcher_based_on_type(
				$field_id, $value, $classes, $autoconvert, 'page-or-post' );
		}

		public static function print_page_searcher(
				$field_id, $value = false, $classes = array(), $autoconvert = true ) {
			self::print_post_searcher_based_on_type(
				$field_id, $value, $classes, $autoconvert, 'page' );
		}

		public static function print_post_searcher(
				$field_id, $value = false, $classes = array(), $autoconvert = true ) {
			self::print_post_searcher_based_on_type(
				$field_id, $value, $classes, $autoconvert, 'post' );
		}

		public static function get_form_searcher(
				$field_id, $value = false, $classes = array(), $autoconvert = true ) {
			ob_start();
			self::print_post_searcher_based_on_type(
				$field_id, $value, $classes, $autoconvert, 'form' );
			$value = ob_get_contents();
			ob_end_clean();
			return $value;
		}

		public static function print_form_searcher(
				$field_id, $value = false, $classes = array(), $autoconvert = true ) {
			self::print_post_searcher_based_on_type(
				$field_id, $value, $classes, $autoconvert, 'form' );
		}

		private static function print_post_searcher_based_on_type(
				$field_id, $value, $classes, $autoconvert, $type ) {
			$placeholder = __( 'Select an option...', 'nelioab' );
			switch ( $type ) {
				case 'page':
					$placeholder = __( 'Select a page...', 'nelioab' );
					break;
				case 'post':
					$placeholder = __( 'Select a post...', 'nelioab' );
					break;
				case 'page-or-post':
					$placeholder = __( 'Select a page or post...', 'nelioab' );
					break;
				case 'form':
					$placeholder = __( 'Select a form...', 'nelioab' );
					break;
				case 'page-or-post-or-latest':
					$placeholder = __( 'Select a page or post...', 'nelioab' );
					break;
			}
			$searcher_type = 'post-searcher ' . $type;
			if ( 'form' == $type )
				$searcher_type = 'form-searcher';
			?>
			<input
				id="<?php echo $field_id; ?>" name="<?php echo $field_id; ?>"
				data-type="<?php echo $type; ?>"
				data-placeholder="<?php echo $placeholder; ?>"
				type="hidden" class="<?php
					echo $searcher_type; ?> <?php
					echo implode( ' ', $classes ); ?>"
				value="<?php echo $value; ?>" /><?php
			if ( $autoconvert ) { ?>
				<script type="text/javascript">
				(function($) {
					var field = $("#<?php echo $field_id; ?>");
					NelioABPostSearcher.buildSearcher(field, "<?php echo $type; ?>");
					<?php
						if ( $value !== false )
							echo 'NelioABPostSearcher.setDefault(field, "' . $type . '");';
						echo "\n";
					?>
				})(jQuery);
				</script><?php
			} ?>
			<?php
		}

	}//NelioABHtmlGenerator

}

