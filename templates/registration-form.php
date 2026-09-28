<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cr-registration">
	<form class="cr-registration__form" method="post" action="<?php echo esc_url( $form_action ); ?>">
		<?php if ( $errors->has_errors() ) : ?>
			<div class="cr-errors" role="alert">
				<ul>
					<?php foreach ( $errors->get_error_messages() as $message ) : ?>
						<li><?php echo esc_html( $message ); ?></li>
					<?php endforeach; ?>
				</ul>
			</div>
		<?php endif; ?>

		<p class="cr-field">
			<label for="cr-first-name"><?php esc_html_e( 'First Name', 'custom-registration' ); ?></label>
			<input type="text" id="cr-first-name" name="first_name" value="<?php echo esc_attr( $data['first_name'] ?? '' ); ?>" autocomplete="given-name" required>
		</p>

		<p class="cr-field">
			<label for="cr-last-name"><?php esc_html_e( 'Last Name', 'custom-registration' ); ?></label>
			<input type="text" id="cr-last-name" name="last_name" value="<?php echo esc_attr( $data['last_name'] ?? '' ); ?>" autocomplete="family-name" required>
		</p>

		<p class="cr-field">
			<label for="cr-username"><?php esc_html_e( 'Username', 'custom-registration' ); ?></label>
			<input type="text" id="cr-username" name="username" value="<?php echo esc_attr( $data['username'] ?? '' ); ?>" autocomplete="username" required>
		</p>

		<p class="cr-field">
			<label for="cr-email"><?php esc_html_e( 'Email', 'custom-registration' ); ?></label>
			<input type="email" id="cr-email" name="email" value="<?php echo esc_attr( $data['email'] ?? '' ); ?>" autocomplete="email" required>
		</p>

		<p class="cr-field">
			<label for="cr-password"><?php esc_html_e( 'Password', 'custom-registration' ); ?></label>
			<input type="password" id="cr-password" name="password" autocomplete="new-password" required>
		</p>

		<p class="cr-field">
			<label for="cr-password-confirm"><?php esc_html_e( 'Confirm Password', 'custom-registration' ); ?></label>
			<input type="password" id="cr-password-confirm" name="password_confirm" autocomplete="new-password" required>
		</p>

		<?php wp_nonce_field( 'cr_register', 'cr_register_nonce' ); ?>
		<input type="hidden" name="cr_action" value="register">

		<button type="submit" class="cr-submit">
			<?php esc_html_e( 'Create Account', 'custom-registration' ); ?>
		</button>
	</form>
</div>
