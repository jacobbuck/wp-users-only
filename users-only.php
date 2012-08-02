<?php
/*
Plugin Name: Users Only
Plugin URI: https://github.com/jacobbuck/wp-users-only
Description: Restrict a website to logged in users. Visitors get redirected to login page or displayed a holding page.
Version: 1.2.1
Author: Jacob Buck
Author URI: http://jacobbuck.co.nz/
*/

class Users_Only {
	
	function __construct () {
		/* Register actions */
		add_action( 'login_head', array( &$this, 'login_head') );
		add_action( 'template_redirect', array( &$this, 'template_redirect') );
	}
	
	function login_head () {
		/* Hide back to blog link */
		echo '<style>#backtoblog{display:none}</style>';
	}
	
	function template_redirect () {
		/* Check unauthorised visitors */
		if ( is_user_logged_in() ) 
			return;
		
		/* Show holding template file if is located, otherwise redirect to login page */	
		if ( ! locate_template( array('holding/index.php'), true, false ) ) 
			wp_redirect( wp_login_url( home_url() ), 302 );
		
		/* Shutdown */
		exit(); 
	}
	
}

$users_only = new Users_Only;