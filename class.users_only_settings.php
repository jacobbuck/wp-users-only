<?php
class Users_Only_Settings extends Users_Only {

	public static function initialize () {

		add_action( 'admin_init', array( 'Users_Only_Settings', 'wp_save_options') );
		add_action( 'admin_menu', array( 'Users_Only_Settings', 'wp_admin_menu') );
		add_filter( 'plugin_action_links', array( 'Users_Only_Settings', 'filter_add_settings_link' ), 10, 2 );

	}

	public static function wp_admin_menu () {

		add_options_page(
			__('Users Only'),
			__('Users Only'),
			'manage_options',
			'users-only',
			array( 'Users_Only_Settings', 'options_page_cb' )
		);

	}

	public static function options_page_cb () {

		?>
		<div class="wrap">

			<?php screen_icon(); ?>
			<h2><?php _e('Users Only'); ?></h2>

			<form action="<?php echo admin_url('options-general.php?page=users-only'); ?>" method="post">

				<?php wp_nonce_field( plugin_basename( __FILE__ ), 'wpuo_nonce' ); ?>

				<h3 class="title"><?php _e('Logged In'); ?></h3>

				<table class="form-table">
					<tr>
						<th class="row"><?php _e('Disable Dashboard'); ?></th>
						<td>
							<?php
							global $wp_roles;
							foreach ( (array) $wp_roles->role_names as $role => $name ) {
								if ( 'administrator' === $role )
									continue;
								echo '<label><input type="checkbox" name="wpuo_disable_dashboard[]"';
								echo checked( in_array( $role, self::$options['disable_dashboard'] ) );
								echo ' value="', $role, '" /> &nbsp; ', __( $name ), '</label><br />';
							}
							?>
						</td>
					</tr>
				</table>

				<h3 class="title"><?php _e('Logged Out'); ?></h3>

				<table class="form-table">
					<tr>
						<th class="row"><label for="wpuo_logged_out_action"><?php _e('Action'); ?></label></th>
						<td>
							<select name="wpuo_logged_out_action" id="wpuo_logged_out_action">
								<option value=""
									<?php
									selected( '' === self::$options['logged_out_action'] );
									?>><?php _e('Do nothing'); ?></option>
								<option value="wp-login" <?php
									selected( 'wp-login' === self::$options['logged_out_action'] );
									?>><?php _e('Redirect to WordPress login'); ?></option>
								<option value="page" <?php
									selected( 'page' === self::$options['logged_out_action'] );
									?>><?php _e('Redirect to page'); ?></option>
								<option value="holding" <?php
									selected( 'holding' === self::$options['logged_out_action'] );
									disabled( ! locate_template( array('holding.php') ) );
									?>><?php _e('Display holding page') ?></option>
							</select>&nbsp;
							<?php
							wp_dropdown_pages( array(
								'post_type'        => 'page',
								'name'             => 'wpuo_logged_out_page_id',
								'sort_column'      => 'menu_order, post_title',
								'post_status'      => 'publish',
								'selected'         =>  self::$options['logged_out_page_id'],
								'show_option_none' => __('&mdash; Select &mdash;')
							) );
							?>
							<script>
							(function(){
								var action  = document.getElementById('wpuo_logged_out_action'),
									page_id = document.getElementById('wpuo_logged_out_page_id');
								function update () {
									page_id.disabled = ( 'page' !== action.value );
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

	public static function wp_save_options () {

		/* Nonce Validation */
		if ( empty( $_POST['wpuo_nonce'] ) || ! wp_verify_nonce( $_POST['wpuo_nonce'], plugin_basename( __FILE__ ) ) )
			return;

		/* Check if user is allowed */
		if ( ! current_user_can('manage_options') )
			return;

		/* Save options */
		update_option( 'wp_users_only', array(
			'logged_out_action'  => empty( $_POST['wpuo_logged_out_action'] )  ? ''      : $_POST['wpuo_logged_out_action'],
			'logged_out_page_id' => empty( $_POST['wpuo_logged_out_page_id'] ) ? 0       : (int) $_POST['wpuo_logged_out_page_id'],
			'disable_dashboard'  => empty( $_POST['wpuo_disable_dashboard'] )  ? array() : (array) $_POST['wpuo_disable_dashboard']
		) );

		wp_redirect( admin_url('options-general.php?page=users-only&updated=true') );

	}

	public static function filter_add_settings_link ( $links, $file ) {

		if ( strstr( __FILE__, $file ) )
			array_push( $links, '<a href="' . admin_url('options-general.php?page=users-only') . '">' . __('Settings') . '</a>' );

		return $links;

	}

}