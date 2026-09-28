<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cr-account">
	<div class="cr-account__header">
		<h2><?php esc_html_e( 'My Account', 'custom-registration' ); ?></h2>
		<p><?php esc_html_e( 'Manage your profile and password.', 'custom-registration' ); ?></p>
	</div>

	<div class="cr-account__messages" aria-live="polite">
		<?php if ( $message ) : ?>
			<div class="cr-account-success" role="status"><?php echo esc_html( $message ); ?></div>
		<?php endif; ?>

		<?php if ( $errors->has_errors() ) : ?>
			<div class="cr-account-errors" role="alert">
				<ul>
					<?php foreach ( $errors->get_error_messages() as $error ) : ?>
						<li><?php echo esc_html( $error ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>
	</div>

	<form class="cr-account__form cr-profile-form" method="post">
		<h3><?php esc_html_e( 'Profile Information', 'custom-registration' ); ?></h3>

		<div class="cr-account-field">
			<label for="cr-account-username"><?php esc_html_e( 'Username', 'custom-registration' ); ?></label>
			<input type="text" id="cr-account-username" value="<?php echo esc_attr( $data['username'] ); ?>" readonly>
		</div>

		<div class="cr-account-field">
			<label for="cr-account-first-name"><?php esc_html_e( 'First Name', 'custom-registration' ); ?></label>
			<input type="text" id="cr-account-first-name" name="first_name" value="<?php echo esc_attr( $data['first_name'] ); ?>" autocomplete="given-name" required>
		</div>

		<div class="cr-account-field">
			<label for="cr-account-last-name"><?php esc_html_e( 'Last Name', 'custom-registration' ); ?></label>
			<input type="text" id="cr-account-last-name" name="last_name" value="<?php echo esc_attr( $data['last_name'] ); ?>" autocomplete="family-name" required>
		</div>

		<div class="cr-account-field">
			<label for="cr-account-email"><?php esc_html_e( 'Email', 'custom-registration' ); ?></label>
			<input type="email" id="cr-account-email" name="email" value="<?php echo esc_attr( $data['email'] ); ?>" autocomplete="email" required>
		</div>

		<?php wp_nonce_field( 'cr_update_profile', 'cr_profile_nonce' ); ?>
		<input type="hidden" name="cr_account_action" value="update_profile">

		<button type="submit" class="cr-account-submit">
			<span class="cr-account-submit__text"><?php esc_html_e( 'Save Changes', 'custom-registration' ); ?></span>
			<span class="cr-account-submit__loading" aria-hidden="true"><?php esc_html_e( 'Saving...', 'custom-registration' ); ?></span>
		</button>
	</form>

	<form class="cr-account__form cr-password-form" method="post">
		<h3><?php esc_html_e( 'Change Password', 'custom-registration' ); ?></h3>

		<div class="cr-account-field">
			<label for="cr-current-password"><?php esc_html_e( 'Current Password', 'custom-registration' ); ?></label>
			<input type="password" id="cr-current-password" name="current_password" autocomplete="current-password" required>
		</div>

		<div class="cr-account-field">
			<label for="cr-account-password"><?php esc_html_e( 'New Password', 'custom-registration' ); ?></label>
			<input type="password" id="cr-account-password" name="password" autocomplete="new-password" minlength="8" required>
		</div>

		<div class="cr-account-field">
			<label for="cr-account-password-confirm"><?php esc_html_e( 'Confirm New Password', 'custom-registration' ); ?></label>
			<input type="password" id="cr-account-password-confirm" name="password_confirm" autocomplete="new-password" minlength="8" required>
		</div>

		<?php wp_nonce_field( 'cr_change_password', 'cr_password_nonce' ); ?>
		<input type="hidden" name="cr_account_action" value="change_password">

		<button type="submit" class="cr-account-submit">
			<span class="cr-account-submit__text"><?php esc_html_e( 'Change Password', 'custom-registration' ); ?></span>
			<span class="cr-account-submit__loading" aria-hidden="true"><?php esc_html_e( 'Changing...', 'custom-registration' ); ?></span>
		</button>
	</form>

	<form class="cr-account__logout-form" method="post">
		<?php wp_nonce_field( 'cr_logout', 'cr_logout_nonce' ); ?>
		<input type="hidden" name="cr_account_action" value="logout">
		<input type="hidden" name="redirect_to" value="<?php echo esc_attr( home_url( '/' ) ); ?>">

		<button type="submit" class="cr-account-logout">
			<span><?php esc_html_e( 'Log Out', 'custom-registration' ); ?></span>
		</button>
	</form>
</div>
