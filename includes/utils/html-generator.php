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


if ( !class_exists( 'NelioABHtmlGenerator' ) ) {

	abstract class NelioABHtmlGenerator extends WP_List_Table {

		public static function print_filters( $filter_url, $filters, $filter_name, $current = false ) {?>
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
				}?>
			</ul><?php
		}

	}//NelioABHtmlGenerator

}
?>
