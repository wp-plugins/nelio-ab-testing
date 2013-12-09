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


if ( !class_exists( 'NelioABThemeAltExpEditionPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alt-exp-page.php' );
	class NelioABThemeAltExpEditionPage extends NelioABAltExpPage {

		private $current_theme;
		private $themes;

		public function __construct( $title = false ) {
			if ( !$title)
				$title = __( 'Edit Theme Experiment', 'nelioab' );
			parent::__construct( $title );
			$this->set_form_name( 'nelioab_new_ab_theme_exp_form' );

			$this->current_theme = array();
			$this->themes        = array();
		}

		public function get_alt_exp_type() {
			require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
			return NelioABExperiment::THEME_ALT_EXP;
		}

		public function set_current_theme( $id, $name, $image, $creator ) {
			$this->current_theme = array(
				'id'       => $id,
				'name'     => $name,
				'image'    => $image,
				'creator'  => $creator,
				'selected' => true );
		}

		public function add_theme( $id, $name, $image, $creator, $selected = false ) {
			$theme = array(
				'id'       => $id,
				'name'     => $name,
				'image'    => $image,
				'creator'  => $creator,
				'selected' => $selected );
			array_push( $this->themes, $theme );
		}

		protected function get_basic_info_elements() {
			return array(
				array (
					'label'     => 'Name',
					'id'        => 'exp_name',
					'callback'  => array( &$this, 'print_name_field' ),
					'mandatory' => true ),
				array (
					'label'     => 'Description',
					'id'        => 'exp_descr',
					'callback'  => array( &$this, 'print_descr_field' ) ),
				array (
					'label'     => __( 'Goal Pages and Posts', 'nelioab' ),
					'id'        => 'exp_goal',
					'callback'  => array ( &$this, 'print_goal_field' ),
					'mandatory' => true ),
			);
		}

		protected function print_alternatives() { ?>
			<h2 style="padding-top:2em;"><?php _e( 'Alternatives', 'nelioab' ); ?></h2>

			<?php
			$this->print_theme(
				$this->current_theme['id'],
				$this->current_theme['name'],
				$this->current_theme['image'],
				$this->current_theme['creator'],
				true, true );

			foreach( $this->themes as $theme ) {
				$this->print_theme(
					$theme['id'],
					$theme['name'],
					$theme['image'],
					$theme['creator'],
					$theme['selected'] );
			}

		}

		private function print_theme( $id, $name, $image, $creator, $selected, $current = false ) { ?>
			<div class="nelioab-theme<?php
				if ( $selected ) echo ' nelioab-selected';
				if ( $current ) echo ' nelioab-is-current-theme';
				?>" id="<?php echo $id; ?>">
				<div class="theme-image-selector">
					<div class="theme-image-wrapper">
						<img
							src="<?php echo $image; ?>" />
					</div>
					<div class="nelioab-theme-tick">&nbsp;</div>
					<?php
					if ( $current ) { ?>
						<div class="nelioab-current-theme"><?php _e( 'Current Theme' ); ?> </div>
					<?php
					} ?>
				</div>
				<div class="theme-description">
					<p><b class="the-theme-name"><?php echo $name; ?></b><br />
					<?php echo sprintf(
						__( 'By %s', 'nelioab' ),
						$creator ); ?></p>
				</div>
			</div>
		<?php
		}

		protected function print_validator_js() { ?>
			<script type="text/javascript">
			jQuery(document).ready(function() {
				var $ = jQuery;

				// Global form
				checkSubmit(jQuery);
				$("#exp_name").bind( "change paste keyup", function() { checkSubmit(jQuery); } );
				$("#active_goals").bind('NelioABGoalsChanged', function() { checkSubmit(jQuery); } );

			});

			function checkSubmit($) {
				if ( validateGeneral($) )
					$(".actions > .button-primary").removeClass("button-primary-disabled");
				else
					$(".actions > .button-primary").addClass("button-primary-disabled");
			}

			function validateGeneral($) {

				try {
					aux = $("#exp_name").attr("value");
					if ( aux == undefined )
						return false;
					aux = aux.trim();
					if ( aux.length == 0 )
						return false;
				} catch ( e ) {}

				if ( !is_there_one_goal_at_least() )
					return false;

				return true;
			}

			function submitAndRedirect(action,force) {
				if ( !force ) {
					var primaryEnabled = true;
					jQuery(".nelioab-js-button").each(function() {
						if ( jQuery(this).hasClass("button-primary") &&
						     jQuery(this).hasClass("button-primary-disabled") )
						primaryEnabled = false;
					});
					if ( !primaryEnabled )
						return;
				}
				smoothTransitions();
				jQuery("#action").attr('value', action);
				jQuery.post(
					location.href,
					jQuery("#<?php echo $this->form_name; ?>").serialize()
				).success(function(data) {
					data = data.trim();
					if ( data.indexOf("[SUCCESS]") == 0) {
						location.href = data.replace("[SUCCESS]", "");
					}
					else {
						document.open();
						document.write(data);
						document.close();
					}
				});
			}
			</script>
			<?php
		}

		public function print_page_buttons() {
			echo $this->make_js_button(
					_x( 'Update', 'action', 'nelioab' ),
					'javascript:submitAndRedirect(\'validate\',false)',
					false, true
				);
			echo $this->make_js_button(
					_x( 'Cancel', 'nelioab' ),
					'javascript:submitAndRedirect(\'cancel\',true)'
				);
		}

		public function print_custom_js() {
		?>
		<script>
			jQuery(document).ready(function() {
				$ = jQuery;
				$(".theme-image-selector").click(function() {
					p = $(this).parent();

					if ( p.hasClass( "nelioab-is-current-theme" ) )
						return;

					if ( p.hasClass( "nelioab-selected" ) )
						p.removeClass( "nelioab-selected" );
					else
						p.addClass( "nelioab-selected" );

					selected = new Array();
					$(".nelioab-selected").each( function() {
						if ( $(this).hasClass( "nelioab-is-current-theme" ) )
							return;
						option = {};
						option.name  = $(this).find(".the-theme-name").first().text();
						option.value = $(this).attr("id");
						selected.push( option );
					} );
					$("#local_alternatives").attr( "value", btoa( JSON.stringify( selected ) ) );
				});
			});
		</script>
		<?php
		}

	}//NelioABThemeAltExpEditionPage

}

?>
