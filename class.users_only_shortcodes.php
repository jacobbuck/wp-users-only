<?php
class Users_Only_Shortcodes extends Users_Only {

	static function initialize () {

		add_shortcode( 'wpuo-login-form', array( 'Users_Only_Shortcodes', 'login_form_shortcode' ) );
		add_shortcode( 'wpuo-profile-form', array( 'Users_Only_Shortcodes', 'profile_form_shortcode' ) );

	}

	/**
	 * Login form shortcode - [wpuo-login-form]
	 */

	static function login_form_shortcode () {

		if ( 0 === parent::$current_user->ID )
			return wp_login_form ( array (
				'echo' => false,
				'redirect' => isset( $_GET['ref'] ) ? $_GET['ref'] : home_url()
			) );

		return sprintf(
			__('You\'re arealy logged in as %1$s. <a href="%2$s">Log Out</a>.'),
			parent::$current_user->display_name,
			wp_logout_url( home_url() )
		);

	}

	/**
	 * Profile form shortcode - [wpuo-profile-form]
	 */

	static function profile_form_shortcode () {

		if ( 0 === parent::$current_user->ID )
			return;

		ob_start();

		?>
		<form action="" method="post" class="profile-form">
			<?php

			self::profile_form_shortcode_init();

			wp_nonce_field( plugin_basename( __FILE__ ), 'profile-nonce' );

			?>
			<p class="profile-email">
				<label for="email"><?php _e('Email Address') ?></label>
				<input type="email" name="profile-email" id="profile-email" class="text-input" value="<?php echo parent::$current_user->user_email; ?>" />
			</p>
			<p class="profile-firstname">
				<label for="firstname"><?php _e('First Name') ?></label>
				<input type="text" name="profile-firstname" id="profile-firstname" class="text-input" value="<?php echo parent::$current_user->user_firstname; ?>" />
			</p>
			<p class="profile-lastname">
				<label for="lastname"><?php _e('Last Name') ?></label>
				<input type="text" name="profile-lastname" id="profile-lastname" class="text-input" value="<?php echo parent::$current_user->user_lastname; ?>" />
			</p>
			<p class="profile-password">
				<label for="password"><?php _e('New Password'); ?></label>
				<input type="password" name="profile-password" id="profile-password" class="text-input" />
			</p>
			<p class="profile-password-confirm">
				<label for="profile-password-confirm"><?php _e('Confirm New Password'); ?></label>
				<input type="password" name="profile-password-confirm" id="profile-password-confirm" class="text-input" />
			</p>
			<p class="profile-form-submit"><input type="submit" name="submit" class="submit button" value="<?php _e('Update'); ?>" /></p>
		</form>
		<?php

		return ob_get_clean();

	}

	static function profile_form_shortcode_init () {

		/* Check nonce */
		if (
			! isset( $_POST['profile-nonce'] )
			|| ! wp_verify_nonce( $_POST['profile-nonce'], plugin_basename( __FILE__ ) )
		)
			return;

		$userdata = array( 'ID' => parent::$current_user->ID );

		/* Email Address */
		if (
			isset( $_POST['profile-email'] )
			&& $_POST['profile-email'] !== parent::$current_user->user_email
		) {
			$email = $_POST['profile-email'];
			if ( ! is_email( $email ) )
				echo '<p class="message error">', __('Please enter your correct email address.'), '</p>';
			elseif ( email_exists( $email ) )
				echo '<p class="message error">', __('This email is already registered, please choose another one.'), '</p>';
			else
				$userdata['user_email'] = $email;
		}

		/* First Name */
		if (
			isset( $_POST['profile-firstname'] )
			&& $_POST['profile-firstname'] !== parent::$current_user->first_name
		) {
			$firstname = trim( $_POST['profile-firstname'] );
			if ( empty( $firstname ) )
				echo '<p class="message error">', __('Please enter your first name.'), '</p>';
			else
				$userdata['first_name'] = $firstname;
		}

		/* Last Name */
		if (
			isset( $_POST['profile-lastname'] )
			&& $_POST['profile-lastname'] !== parent::$current_user->last_name
		) {
			$lastname = trim( $_POST['profile-lastname'] );
			if ( empty( $lastname ) )
				echo '<p class="message error">', __('Please enter your last name.'), '</p>';
			else
				$userdata['last_name'] = $lastname;
		}

		/* Display Name */
		if ( isset( $userdata['first_name'], $userdata['last_name'] ) ) {
			$userdata['display_name'] = sprintf( _x( '%1$s %2$s', 'Display name based on first name and last name' ), $userdata['first_name'], $userdata['last_name'] );
		}

		/* Password */
		if (
			isset( $_POST['profile-password'], $_POST['profile-password-confirm'] )
			&& ! empty( $_POST['profile-password'] )
		) {
			if ( $_POST['profile-password'] === $_POST['profile-password-confirm'] )
				$userdata['user_pass'] = $_POST['profile-password'];
			else
				echo '<p class="message error">', __('Please enter the same password in the two password fields.'), '</p>';
		}

		/* Save changes */
		if ( wp_update_user( $userdata ) )
			echo '<p class="message">', __('Profile updated.'), '</p>';

		/* Update current user */
		parent::$current_user = wp_get_current_user();

	}

}