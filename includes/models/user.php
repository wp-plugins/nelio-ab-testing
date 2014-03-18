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


if( !class_exists( 'NelioABUser' ) ) {

	require_once( NELIOAB_MODELS_DIR . '/settings.php' );

	class NelioABUser {

		const COOKIE_LIFETIME = 5184000; // 60 days
		const TEN_YEARS = 315360000;

		public static function get_id() {
			global $NELIOAB_COOKIES;

			if ( isset( $NELIOAB_COOKIES['nelioab_userid'] ) )
				return $NELIOAB_COOKIES['nelioab_userid'];

			$user_id     = get_option( 'nelioab_last_user_id', 0 ) + 1;
			$cookie_name =  NelioABSettings::cookie_prefix() . 'userid';
			nelioab_setcookie( $cookie_name, $user_id, time() + NelioABUser::TEN_YEARS );
			update_option( 'nelioab_last_user_id', $user_id );

			return $user_id;
		}

		public static function get_alternative_for_post_alt_exp( $post_id ) {
			global $NELIOAB_COOKIES;

			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );

			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
			$exp = null;
			foreach ( $running_exps as $data ) {
				if ( $data->get_type() != NelioABExperiment::POST_ALT_EXP &&
				     $data->get_type() != NelioABExperiment::PAGE_ALT_EXP &&
			        $data->get_type() != NelioABExperiment::TITLE_ALT_EXP ) {
					continue;
				}
				if ( $data->get_originals_id() == $post_id ) {
					$exp = $data;
					break;
				}
			}

			if ( $exp == null )
				return $post_id;

			$cookie_name = NelioABSettings::cookie_prefix() . 'altexp_' . $exp->get_id();
			$cookie_life = time() + NelioABUser::COOKIE_LIFETIME;

			if ( !isset( $NELIOAB_COOKIES[$cookie_name] ) ) {
				// Creating the cookie for the experiment information
				$alternatives   = $exp->get_alternatives();
				$num_of_options = count( $alternatives );
				$option         = mt_rand( 0, $num_of_options );
				$alt_post       = $exp->get_originals_id();
				if ( $option != $num_of_options ) {
					$alt_post = $alternatives[$option];
					$alt_post = $alt_post->get_value();
				}

				// Before setting any cookie, we check that the original and the alternative
				// posts exist...
				$post = get_post( $post_id );
				if ( !$post ) return $post_id;

				if ( $alt_post < 0 ) {
					// The ALT_POST id is negative if we are testing titles only
				}
				else {
					// If we are not, we check whether the post exists or not...
					$post = get_post( $alt_post );
					if ( !$post ) return $post_id;
				}

				// If everything seems to exist, we set the cookie and keep going
				nelioab_setcookie( $cookie_name, $alt_post, $cookie_life );
			}
			else {
				$alt_post = $NELIOAB_COOKIES[$cookie_name];
			}

			// This cookies works because it is a session cookie...
			$cookie_name =  NelioABSettings::cookie_prefix() . 'title_' . $post_id;
			if ( !isset( $NELIOAB_COOKIES[$cookie_name] ) ) {
				// Creating the cookie for the title that goes to the menus
				$post      = get_post( $post_id );
				$ori_title = rawurlencode( $post->post_title );

				if ( $alt_post < 0 ) {
					$alternative = false;
					foreach ( $exp->get_alternatives() as $alt )
						if ( $alt->get_value() == $alt_post )
							$alternative = $alt;

					if ( $alternative ) {
						$exp_id = $exp->get_id();
						$alt_title = rawurlencode( $alternative->get_name() );
						nelioab_setrawcookie( $cookie_name, "$ori_title:$alt_title:$exp_id" );
					}
				}
				else {
					$post = get_post( $alt_post );
					if ( $post ) {
						$exp_id = $exp->get_id();
						$alt_title = rawurlencode( $post->post_title );
						nelioab_setrawcookie( $cookie_name, "$ori_title:$alt_title:$exp_id" );
					}
				}
			}

			return $alt_post;
		}

		public static function get_alternative_for_global_alt_exp( $type ) {
			global $NELIOAB_COOKIES;

			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );

			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
			$exp = NULL;
			foreach ( $running_exps as $data ) {
				if ( $data->get_type() == $type ) {
					$exp = $data;
					break;
				}
			}

			if ( $exp == NULL )
				return false;

			$cookie_name = NelioABSettings::cookie_prefix() . 'theme_altexp_' . $exp->get_id();
			$cookie_life = time() + NelioABUser::COOKIE_LIFETIME;

			if ( !isset( $NELIOAB_COOKIES[$cookie_name] ) ) {
				// Creating the cookie for the experiment information
				$alternatives   = $exp->get_alternatives();
				$num_of_options = count( $alternatives );
				$option         = mt_rand( 0, $num_of_options );

				$aux    = $exp->get_original();
				$alt_id = $aux->get_id();
				if ( $option != $num_of_options ) {
					$aux    = $exp->get_alternatives();
					$aux    = $aux[$option];
					$alt_id = $aux->get_id();
				}

				// If everything seems to exist, we set the cookie and keep going
				nelioab_setcookie( $cookie_name, $alt_id, $cookie_life );
			}


			$alt_id = $NELIOAB_COOKIES[$cookie_name];

			if ( $exp->get_original()->get_id() == $alt_id )
				return $exp->get_original();

			foreach ( $exp->get_alternatives() as $candidate )
				if ( $candidate->get_id() == $alt_id )
					return $candidate;

			return false;
		}

		public static function get_assigned_theme() {
			require_once( NELIOAB_MODELS_DIR . '/experiment.php' );
			$alt = NelioABUser::get_alternative_for_global_alt_exp( NelioABExperiment::THEME_ALT_EXP );

			if ( !$alt )
				return wp_get_theme();

			$themes = wp_get_themes();
			foreach ( $themes as $theme )
				if ( $theme['Stylesheet'] == $alt->get_value() )
					return $theme;

			return wp_get_theme();
		}

	}//NelioABUser

}

?>
