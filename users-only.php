<?php
/*
Plugin Name: Users Only
Plugin URI: https://github.com/jacobbuck/wp-users-only
Description: Restrict a website to logged in users only, and disable the dashboard for non-admin user types.
Version: 2.0
Author: Jacob Buck
Author URI: http://jacobbuck.co.nz/
*/

class Users_Only {

	private $is_disable_dashboard = false;

	function __construct () {

		/* Settings Actions/Filters */
		add_action( 'admin_init', array( &$this, 'save_options') );
		add_action( 'admin_menu', array( &$this, 'admin_menu') );
		add_filter( 'plugin_action_links', array( &$this, 'add_settings_link' ), 10, 2 );

		/* Users Only Actions */
		add_action( 'init', array( &$this, 'init') );
		add_action( 'admin_init', array( &$this, 'admin_init') );
		add_action( 'login_head', array( &$this, 'login_head') );
		add_action( 'template_redirect', array( &$this, 'template_redirect') );

	}

	/**
	 * Users Only
	 */

	function init () {

		/* Check if current user can access dashboard */
		$current_user = wp_get_current_user();
		if ( $current_user && ! in_array( 'administrator', $current_user->caps ) ) {
			$li_disabledashboard = (array) get_option('wpuo_li_disabledashboard');
			foreach ( $li_disabledashboard as $role ) {
				if ( isset( $current_user->caps[ $role ] ) ) {
					$this->is_disable_dashboard = true;
					break;
				}
			}
		}

		if ( $this->is_disable_dashboard ) {
			/* Disable admin bar if user can't access dashboard */
			show_admin_bar(false);
		}

	}

	function admin_init () {
		if ( $this->is_disable_dashboard ) {
			/* Redirect if user can't access dashboard */
			wp_redirect( home_url(), 302 );
			exit;
		}
	}

	function login_head () {
		if ( 'wp-login' === get_option('wpuo_lo_action') ) {
			/* Hide back to blog link */
			echo '<style>#backtoblog{display:none}</style>';
		}
	}

	function template_redirect () {

		/* Check if user logged in */
		if ( is_user_logged_in() )
			return;

		/* Do action */
		switch ( get_option('wpuo_lo_action') ) {
			case 'wp-login':
				/**
				 * Redirect to WordPress Login
				 */
				wp_redirect( wp_login_url( home_url() ), 302 );
				exit;
				break;
			case 'page':
				/**
				 * Redirect to page
				 */
				$lo_pageid = get_option('wpuo_lo_pageid');
				/* Check if we're already on the page */
				if ( get_the_ID() != $lo_pageid ) {
					/* Otherwise redirect to the page */
					if ( false !== wp_redirect( get_permalink( $lo_pageid ), 302 ) )
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

		/* Otherwise do nothing */

	}

	/**
	 * Users Only Options Page
	 */

	function admin_menu () {
		add_options_page(
			__('Users Only'),
			__('Users Only'),
			'manage_options',
			'users-only',
			array( &$this, 'options_page_cb' )
		);
	}

	function options_page_cb () {

		/* Logged In Options */
		$li_disabledashboard = (array) get_option('wpuo_li_disabledashboard');

		/* Logged Out Options */
		$lo_action = get_option('wpuo_lo_action');
		$lo_pageid = get_option('wpuo_lo_pageid');

		?>
		<div class="wrap">

			<?php screen_icon(); ?>
			<h2><?php _e('Users Only'); ?></h2>

			<form action="<?php echo admin_url('options-general.php?page=users-only'); ?>" method="post">

				<?php wp_nonce_field( plugin_basename( __FILE__ ), 'wpuo_nonce' ); ?>

				<h3 class="title">Logged In</h3>

				<table class="form-table">
					<tr>
						<th class="row"><?php _e('Disable Dashboard'); ?></th>
						<td>
							<?php
							global $wp_roles;
							foreach ( (array) $wp_roles->role_names as $role => $name ) {
								if ( 'administrator' === $role )
									continue;
								echo '<input type="checkbox" name="wpuo_li_disabledashboard[]"', checked( in_array( $role, $li_disabledashboard ) ) ,' value="', $role, '" />';
								echo '&nbsp; <label for="">', __( $name ), '</label><br />';
							}
							?>
						</td>
					</tr>
				</table>

				<h3 class="title">Logged Out</h3>

				<table class="form-table">
					<tr>
						<th class="row"><label for="wpuo_lo_action"><?php _e('Action'); ?></label></th>
						<td>
							<select name="wpuo_lo_action" id="wpuo_lo_action">
								<option value=""
									<?php
									selected('' === $lo_action);
									?>><?php _e('Do nothing'); ?></option>
								<option value="wp-login" <?php
									selected('wp-login' === $lo_action);
									?>><?php _e('Redirect to WordPress login'); ?></option>
								<option value="page" <?php
									selected('page' === $lo_action);
									?>><?php _e('Redirect to page'); ?></option>
								<option value="holding" <?php
									selected('holding' === $lo_action);
									disabled( ! locate_template( array('holding.php') ) );
									?>><?php _e('Display holding page') ?></option>
							</select>&nbsp;
							<?php
							wp_dropdown_pages( array(
								'post_type'   => 'page',
								'name'        => 'wpuo_lo_pageid',
								'sort_column' => 'menu_order, post_title',
								'post_status' => 'publish',
								'selected'    =>  $lo_pageid
							) );
							?>
							<script>
							(function(){
								var action = document.getElementById('wpuo_lo_action'),
									pageid = document.getElementById('wpuo_lo_pageid');
								function update () {
									pageid.style.display = ('page' === action.value ? 'inline' : 'none');
								};
								action.onchange = update;
								update();
							}())
							</script>
						</td>
					</tr>
				</table>

				<p class="submit"><input type="submit" name="wpuo_submit" id="wpuo_submit" class="button-primary" value="<?php _e('Save Changes'); ?>"></p>

			</form>
		</div>
		<?php
	}

	function save_options () {

		/* Nonce Validation */
		if ( empty( $_POST['wpuo_nonce'] ) || ! wp_verify_nonce( $_POST['wpuo_nonce'], plugin_basename( __FILE__ ) ) )
			return;

		/* Check if user is allowed */
		if ( ! current_user_can('manage_options') )
			return;

		/* Save options */
		update_option( 'wpuo_li_disabledashboard', empty( $_POST['wpuo_li_disabledashboard'] ) ? array() : $_POST['wpuo_li_disabledashboard'] );
		update_option( 'wpuo_lo_action', empty( $_POST['wpuo_lo_action'] ) ? '' : $_POST['wpuo_lo_action'] );
		update_option( 'wpuo_lo_pageid', empty( $_POST['wpuo_lo_pageid'] ) ? '' : $_POST['wpuo_lo_pageid'] );

		wp_redirect( admin_url('options-general.php?page=users-only&updated=true') );

	}

	function add_settings_link ( $links, $file ) {

		if ( strstr( __FILE__, $file ) )
			array_push( $links, '<a href="' . admin_url('options-general.php?page=users-only') . '">' . __('Settings') . '</a>' );

		return $links;

	}


}

$users_only = new Users_Only;