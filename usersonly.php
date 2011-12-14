<?php
/*
Plugin Name: Users Only
Plugin URI: https://github.com/jacobbuck/wp-users-only
Description: Restricts a Wordpress website to logged in users only and redirects unauthorised visitors to login page.
Version: 1.0
Author: Jacob Buck
Author URI: http://jacobbuck.co.nz/
*/

class UsersOnly {
	
	function __construct () {
		/* Register functions */
		add_action("template_redirect", array($this,"template_redirect"));
		add_action("login_head", array($this,"login_head"));
	}
	
	function login_head () {
		/* Hides back to blog link */
		echo "<style type=\"text/css\" media=\"screen\">#backtoblog{display:none}</style>\n";
	}
	
	function template_redirect () {
		/* Redirects unauthorised visitors to login page */
		if (! is_user_logged_in()) {
			wp_redirect(wp_login_url(home_url()),302);
			exit;
		}
	}
	
}

$usersonly = new UsersOnly;