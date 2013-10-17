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
	
		public static function get_id() {
			global $NELIOAB_COOKIES;
	
			if ( isset( $NELIOAB_COOKIES['nelioab_userid'] ) )
				return $NELIOAB_COOKIES['nelioab_userid'];
	
			$user_id     = get_option( 'nelioab_last_user_id', 0 ) + 1;
			$cookie_name =  NelioABSettings::cookie_prefix() . 'userid';
			nelioab_setcookie( $cookie_name, $user_id, time() + (86400*28) );
			update_option( 'nelioab_last_user_id', $user_id );
	
			return $user_id;
		}
	
		public static function get_alternative_for_conversion_experiment( $post_id ) {
			global $NELIOAB_COOKIES;
	
			require_once( NELIOAB_MODELS_DIR . '/experiments-manager.php' );
	
			$running_exps = NelioABExperimentsManager::get_running_experiments_from_cache();
			$exp_data = null;
			foreach ( $running_exps as $data )
				if ( $data->original == $post_id )
					$exp_data = $data;
	
			if ( $exp_data == null )
				return $post_id;
	
			$cookie_name =  NelioABSettings::cookie_prefix() . 'altexp_' . $exp_data->id;
	
			if ( !isset( $NELIOAB_COOKIES[$cookie_name] ) ) {
				// Creating the cookie for the experiment information
				$num_of_options = count( $exp_data->alternatives );
				$option         = mt_rand( 0, $num_of_options );
				$alt_post       = $exp_data->original;
				if ( $option != $num_of_options )
					$alt_post = $exp_data->alternatives[$option];
				nelioab_setcookie( $cookie_name, $alt_post );
			}
			$alt_post = $NELIOAB_COOKIES[$cookie_name];
	
			$cookie_name =  NelioABSettings::cookie_prefix() . 'title_' . $post_id;
			if ( !isset( $NELIOAB_COOKIES[$cookie_name] ) ) {
				// Creating the cookie for the title that goes to the menus
				$post      = get_post( $post_id );
				$ori_title = rawurlencode( $post->post_title );

				$post = get_post( $alt_post );
				if ( $post ) {
					$alt_title = rawurlencode( $post->post_title );
					nelioab_setrawcookie( $cookie_name, "$ori_title:$alt_title" );
				}
			}
	
			return $alt_post;
		}
	
	}//NelioABUser

}

?>
