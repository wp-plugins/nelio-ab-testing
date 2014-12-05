<?php
/**
 * Copyright 2013 Nelio Software S.L.
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


if ( !class_exists( 'NelioABThemeAltExpEditionPage' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/experiment.php' );

	require_once( NELIOAB_ADMIN_DIR . '/views/alternatives/alt-exp-page.php' );
	class NelioABThemeAltExpEditionPage extends NelioABAltExpPage {

		private $current_theme;
		private $themes;
		private $selected_themes;
		private $appspot_ids;

		public function __construct( $title = false ) {
			if ( !$title)
				$title = __( 'Edit Theme Experiment', 'nelioab' );
			parent::__construct( $title );
			$this->set_icon( 'icon-nelioab' );
			$this->set_form_name( 'nelioab_edit_ab_theme_exp_form' );

			$this->is_global = true;

			$this->current_theme   = array();
			$this->themes          = array();
			$this->selected_themes = array();
			$this->appspot_ids     = array();

			// Prepare tabs
			$this->add_tab( 'info', __( 'General', 'nelioab' ), array( $this, 'print_basic_info' ) );
			$this->add_tab( 'theme-alts', __( 'Alternatives', 'nelioab' ), array( $this, 'print_alternatives' ) );
			$this->add_tab( 'goals', __( 'Goals', 'nelioab' ), array( $this, 'print_goals' ) );
		}

		public function get_alt_exp_type() {
			return NelioABExperiment::THEME_ALT_EXP;
		}

		protected function get_save_experiment_name() {
			return _e( 'Save', 'nelioab' );
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

		public function set_selected_themes( $themes ) {
			$this->selected_themes = $themes;
		}

		public function set_appspot_ids( $ids ) {
			$this->appspot_ids = $ids;
		}

		protected function get_basic_info_elements() {
			return array(
				array (
					'label'     => __( 'Name', 'nelioab' ),
					'id'        => 'exp_name',
					'callback'  => array( &$this, 'print_name_field' ),
					'mandatory' => true ),
				array (
					'label'     => __( 'Description', 'nelioab' ),
					'id'        => 'exp_descr',
					'callback'  => array( &$this, 'print_descr_field' ) ),
				array (
					'label'     => __( 'Finalization Mode', 'nelioab' ),
					'id'        => 'exp_finalization_mode',
					'callback'  => array( &$this, 'print_finalization_mode_field' ),
					'min_plan'  => NelioABAccountSettings::ENTERPRISE_SUBSCRIPTION_PLAN,
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

			?>

			<input type="hidden" name="nelioab_selected_themes" id="nelioab_selected_themes" value="<?php
					echo rawurlencode( json_encode( $this->selected_themes ) );
				?>" />

			<input type="hidden" name="nelioab_appspot_ids" id="nelioab_appspot_ids" value="<?php
					echo rawurlencode( json_encode( $this->appspot_ids ) );
				?>" />

			<script type="text/javascript">
				var NelioABSelectedThemes;
				(function($) {

					<?php
					require_once( NELIOAB_UTILS_DIR . '/wp-helper.php' );
					$colorscheme = NelioABWpHelper::get_current_colorscheme();
					?>
					NelioABParams = {css:{}};
					NelioABParams.css.value	= document.createElement('style');
					NelioABParams.css.value.setAttribute('type', 'text/css');
					NelioABParams.css.value.innerHTML = '' +
						'.nelioab-selected .theme-image-selector { ' +
						'  border-color:<?php echo $colorscheme['primary']; ?> !important;' +
						'} ' +
						'.nelioab-theme.nelioab-selected, .nelioab-theme.nelioab-selected:hover { ' +
						'  background:<?php echo $colorscheme['primary']; ?> !important;' +
						'}';
					document.getElementsByTagName('head')[0].appendChild(NelioABParams.css.value);


					NelioABSelectedThemes = JSON.parse( decodeURIComponent(
							jQuery('#nelioab_selected_themes').attr('value')
						)	);

					function validate() {
						var isOneSelected = false;
						for( var i = 0; i < NelioABSelectedThemes.length && !isOneSelected; ++i )
							if ( NelioABSelectedThemes[i].isSelected )
								isOneSelected = true;
						if ( isOneSelected )
							return [true,true];
						else
							return [true,false];
					}

					function toggleTheme( theme ) {
						if ( theme.hasClass( 'nelioab-is-current-theme' ) )
							return;
						var selected;
						if ( theme.hasClass('nelioab-selected') ) {
							theme.removeClass('nelioab-selected');
							selected = false;
						}
						else {
							theme.addClass('nelioab-selected');
							selected = true;
						}

						var id = theme.attr('id');
						var name = theme.find('.the-whole-name').first().text();
						var found = false;
						for( var i = 0; i < NelioABSelectedThemes.length && !found; ++i ) {
							var st = NelioABSelectedThemes[i];
							if ( st.value == id )
								found = st;
						}
						if ( !found ) {
							found = { value: id, name: name };
							NelioABSelectedThemes.push( found );
						}
						found.isSelected = selected;
						validate();
					}

					$('.nelioab-theme').click(function() {
						toggleTheme($(this));
					});

					// Save the experiment (and encode the alternatives)
					$(document).on('save-experiment', function() {
						$( '#nelioab_selected_themes' ).attr('value',
							encodeURIComponent( JSON.stringify( NelioABSelectedThemes ) )
								.replace( /'/g, "%27") );
					});

					$(document).on( 'tab-changed', function( e, tabId ) {
						if ( tabId == 'tab-theme-alts' )
							NelioABEditExperiment.validateCurrentTab = validate;
					});

				})(jQuery);
			</script>
			<?php
		}

		private function print_theme( $id, $name, $image, $creator, $selected, $current = false ) {
			$shortname = substr( $name, 0, 30 );
			if ( $shortname != $name )
				$shortname .= '...';
			?>
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
					<span class="the-whole-name" style="display:none;"><?php echo $name; ?></span>
					<p><b class="the-theme-name"><?php echo $shortname; ?></b><br />
					<?php echo sprintf(
						__( 'By %s', 'nelioab' ),
						$creator ); ?></p>
				</div>
			</div>
		<?php
		}

	}//NelioABThemeAltExpEditionPage

}

