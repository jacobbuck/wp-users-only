<?php
class Users_Only {

	public static $options;

	protected static $is_disable_dashboard;
	protected static $current_user;

	static function initialize () {

		/* Add Actions */
		add_action( 'init', array( 'Users_Only', 'wp_init'), 5 );
		add_action( 'admin_init', array( 'Users_Only', 'wp_admin_init') );
		add_action( 'login_head', array( 'Users_Only', 'wp_login_head') );
		add_action( 'template_redirect', array( 'Users_Only', 'wp_template_redirect') );

		/* Get Options */
		self::$options = get_option( 'wp_users_only', array(
			'logged_out_action'  => '',
			'logged_out_page_id' => 0,
			'disable_dashboard'  => array()
		) );

	}

	public static function wp_init () {

		/* Get current user */
		self::$current_user = wp_get_current_user();

		/* Check if current user can access dashboard */
		self::$is_disable_dashboard = false;
		if (
			0 !== self::$current_user->ID
			&& ! in_array( 'administrator', self::$current_user->caps )
		) {
			foreach ( (array) self::$options['disable_dashboard'] as $role ) {
				if ( self::$current_user->has_cap( $role ) ) {
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

	public static function wp_admin_init () {
		if ( self::$is_disable_dashboard ) {
			/* Redirect if user can't access dashboard */
			wp_redirect( home_url(), 302 );
			exit;
		}
	}

	public static function wp_login_head () {
		if ( 'wp-login' === self::$options['logged_out_action'] ) {
			/* Hide back to blog link */
			echo '<style>#backtoblog{display:none}</style>';
		}
	}

	public static function wp_template_redirect () {

		/* Check if user logged in */
		if ( self::$current_user->ID )
			return;

		/* Do action */
		switch ( self::$options['logged_out_action'] ) {
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
				/* Check if we're already on the page */
				if ( is_page() && get_the_ID() === self::$options['logged_out_page_id'] )
					break;
				/* Otherwise redirect to the page */
				$redirect_to = get_permalink( self::$options['logged_out_page_id'] );
				/* Add referer query to redirect url */
				if ( untrailingslashit( $_SERVER['REQUEST_URI'] ) )
					$redirect_to = add_query_arg( 'ref', urlencode( home_url( $_SERVER['REQUEST_URI'] ) ), $redirect_to );
				wp_redirect( $redirect_to, 302 );
				exit;
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