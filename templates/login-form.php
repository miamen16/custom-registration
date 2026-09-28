<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cr-auth">
	<form class="cr-auth__form cr-login-form" method="post">
		<div class="cr-auth__messages" aria-live="polite">
			<?php if ( $errors->has_errors() ) : ?>
				<div class="cr-auth-errors" role="alert">
					<ul>
						<?php foreach ( $errors->get_error_messages() as $message ) : ?>
							<li><?php echo esc_html( $message ); ?></li>
						<?php endforeach; ?>
					</ul>
				</div>
			<?php endif; ?>
		</div>

		<div class="cr-auth-field">
			<label for="cr-login"><?php esc_html_e( 'Username or Email', 'custom-registration' ); ?></label>
			<input type="text" id="cr-login" name="login" value="<?php echo esc_attr( $data['login'] ?? '' ); ?>" autocomplete="username" required>
		</div>

		<div class="cr-auth-field">
			<label for="cr-login-password"><?php esc_html_e( 'Password', 'custom-registration' ); ?></label>
			<div class="cr-auth-password">
				<input type="password" id="cr-login-password" name="password" autocomplete="current-password" required>
				<button type="button" class="cr-auth-password__toggle" data-cr-auth-toggle="cr-login-password" aria-pressed="false"><?php esc_html_e( 'Show', 'custom-registration' ); ?></button>
			</div>
		</div>

		<label class="cr-auth-remember">
			<input type="checkbox" name="remember" value="1" <?php checked( ! empty( $data['remember'] ) ); ?>>
			<span><?php esc_html_e( 'Remember Me', 'custom-registration' ); ?></span>
		</label>

		<?php wp_nonce_field( 'cr_login', 'cr_login_nonce' ); ?>
		<input type="hidden" name="cr_auth_action" value="login">
		<?php if ( $redirect_to ) : ?>
			<input type="hidden" name="redirect_to" value="<?php echo esc_attr( $redirect_to ); ?>">
		<?php endif; ?>

		<button type="submit" class="cr-auth-submit">
			<span class="cr-auth-submit__text"><?php esc_html_e( 'Log In', 'custom-registration' ); ?></span>
			<span class="cr-auth-submit__loading" aria-hidden="true"><?php esc_html_e( 'Logging in...', 'custom-registration' ); ?></span>
		</button>

		<p class="cr-auth-links">
			<a href="<?php echo esc_url( add_query_arg( 'cr_forgot_password', '1' ) ); ?>"><?php esc_html_e( 'Forgot Password?', 'custom-registration' ); ?></a>
		</p>
	</form>
</div>
