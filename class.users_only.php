<?php
class Users_Only {

	protected static $is_disable_dashboard;
	protected static $current_user;

	static function initialize () {

		add_action( 'init', array( 'Users_Only', 'wp_init'), 5 );
		add_action( 'admin_init', array( 'Users_Only', 'admin_init') );
		add_action( 'login_head', array( 'Users_Only', 'login_head') );
		add_action( 'template_redirect', array( 'Users_Only', 'template_redirect') );

	}

	/**
	 * Users Only
	 */

	static function wp_init () {

		/* Get current user */
		self::$current_user = wp_get_current_user();

		/* Check if current user can access dashboard */
		self::$is_disable_dashboard = false;
		if (
			0 !== self::$current_user->ID
			&& ! in_array( 'administrator', self::$current_user->caps )
		) {
			$li_disabledashboard = (array) get_option('wpuo_li_disabledashboard');
			foreach ( $li_disabledashboard as $role ) {
				if ( isset( self::$current_user->caps[ $role ] ) ) {
					self::$is_disable_dashboard = true;
					break;
				}
			}
		}

		if ( self::$is_disable_dashboard ) {
			/* Disable admin bar if user can't access dashboard */
			show_admin_bar(false);
		}

	}

	static function admin_init () {
		if ( self::$is_disable_dashboard ) {
			/* Redirect if user can't access dashboard */
			wp_redirect( home_url(), 302 );
			exit;
		}
	}

	static function login_head () {
		if ( 'wp-login' === get_option('wpuo_lo_action') ) {
			/* Hide back to blog link */
			echo '<style>#backtoblog{display:none}</style>';
		}
	}

	static function template_redirect () {

		/* Check if user logged in */
		if ( 0 !== self::$current_user->ID )
			return;

		/* Do action */
		switch ( get_option('wpuo_lo_action') ) {
			case '':
				/**
				 * Do nothing
				 */
				break;
			case 'wp-login':
				/**
				 * Redirect to WordPress Login
				 */
				wp_redirect( wp_login_url( home_url( $_SERVER['REQUEST_URI'] ) ), 302 );
				exit;
				break;
			case 'page':
				/**
				 * Redirect to page
				 */
				$lo_pageid = get_option('wpuo_lo_pageid');
				/* Check if we're already on the page */
				if ( ! is_page() || get_the_ID() != $lo_pageid ) {
					/* Otherwise redirect to the page */
					$redirect_to = get_permalink( $lo_pageid );
					/* Add referer query to redirect url */
					if ( untrailingslashit( $_SERVER['REQUEST_URI'] ) )
						$redirect_to = add_query_arg( 'ref', urlencode( home_url( $_SERVER['REQUEST_URI'] ) ), $redirect_to );
					if ( false !== wp_redirect( $redirect_to, 302 ) )
						exit;
				}
				break;
			case 'holding':
				/**
				 * Display holding template
				 */
				/* Redirect to home page if we aren't already */
				if ( ! is_home() ) {
					wp_redirect( home_url(), 302 );
					exit;
				}
				/* Then display holding template (if available) */
				if ( locate_template( array('holding.php'), true, false ) )
					exit;
				break;
		}

	}

}