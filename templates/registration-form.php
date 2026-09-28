<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cr-registration">
	<?php if ( isset( $_GET['cr_verification_sent'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['cr_verification_sent'] ) ) ) : ?>
		<div class="cr-verification-notice" role="status">
			<h3><?php esc_html_e( 'Check your email', 'custom-registration' ); ?></h3>
			<p><?php esc_html_e( 'Registration was successful. We sent a verification link to your email address. Please verify your email before logging in.', 'custom-registration' ); ?></p>
		</div>
	<?php endif; ?>

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

		<div class="cr-field">
			<label for="cr-first-name"><?php esc_html_e( 'First Name', 'custom-registration' ); ?></label>
			<input type="text" id="cr-first-name" name="first_name" value="<?php echo esc_attr( $data['first_name'] ?? '' ); ?>" autocomplete="given-name" required>
		</div>

		<div class="cr-field">
			<label for="cr-last-name"><?php esc_html_e( 'Last Name', 'custom-registration' ); ?></label>
			<input type="text" id="cr-last-name" name="last_name" value="<?php echo esc_attr( $data['last_name'] ?? '' ); ?>" autocomplete="family-name" required>
		</div>

		<div class="cr-field">
			<label for="cr-username"><?php esc_html_e( 'Username', 'custom-registration' ); ?></label>
			<input type="text" id="cr-username" name="username" value="<?php echo esc_attr( $data['username'] ?? '' ); ?>" autocomplete="username" aria-describedby="cr-username-availability" required>
			<div id="cr-username-availability" class="cr-availability" data-cr-availability="username" aria-live="polite"></div>
		</div>

		<div class="cr-field">
			<label for="cr-email"><?php esc_html_e( 'Email', 'custom-registration' ); ?></label>
			<input type="email" id="cr-email" name="email" value="<?php echo esc_attr( $data['email'] ?? '' ); ?>" autocomplete="email" aria-describedby="cr-email-availability" required>
			<div id="cr-email-availability" class="cr-availability" data-cr-availability="email" aria-live="polite"></div>
		</div>

		<div class="cr-field">
			<label for="cr-password"><?php esc_html_e( 'Password', 'custom-registration' ); ?></label>
			<div class="cr-password">
				<input type="password" id="cr-password" name="password" autocomplete="new-password" minlength="8" aria-describedby="cr-password-strength" required>
				<button type="button" class="cr-password__toggle" data-cr-toggle-password="cr-password" aria-pressed="false">
					<?php esc_html_e( 'Show', 'custom-registration' ); ?>
				</button>
			</div>
			<div id="cr-password-strength" class="cr-password-strength" aria-live="polite" data-strength="0">
				<span class="cr-password-strength__text"></span>
			</div>
		</div>

		<div class="cr-field">
			<label for="cr-password-confirm"><?php esc_html_e( 'Confirm Password', 'custom-registration' ); ?></label>
			<div class="cr-password">
				<input type="password" id="cr-password-confirm" name="password_confirm" autocomplete="new-password" minlength="8" required>
				<button type="button" class="cr-password__toggle" data-cr-toggle-password="cr-password-confirm" aria-pressed="false">
					<?php esc_html_e( 'Show', 'custom-registration' ); ?>
				</button>
			</div>
		</div>

		<?php wp_nonce_field( 'cr_register', 'cr_register_nonce' ); ?>
		<input type="hidden" name="cr_action" value="register">

		<button type="submit" class="cr-submit">
			<span class="cr-submit__text"><?php esc_html_e( 'Create Account', 'custom-registration' ); ?></span>
			<span class="cr-submit__loading" aria-hidden="true"><?php esc_html_e( 'Creating account...', 'custom-registration' ); ?></span>
		</button>
	</form>
</div>
