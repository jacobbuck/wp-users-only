<?php
/*
Plugin Name: Users Only
Plugin URI: https://github.com/jacobbuck/wp-users-only
Description: Restrict a website to logged in users only, and disable the dashboard for non-admin user types.
Version: 2.2.2
Author: Jacob Buck
Author URI: http://jacobbuck.co.nz/
*/

require('class.users_only.php');
require('class.users_only_settings.php');
require('class.users_only_shortcodes.php');

Users_Only::initialize();
Users_Only_Settings::initialize();
Users_Only_Shortcodes::initialize();