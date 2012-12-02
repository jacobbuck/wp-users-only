<?php

/* If uninstall not called from WordPress exit */
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) )
	exit;

/* Delete some options */
delete_option('wp_users_only');