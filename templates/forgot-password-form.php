<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$reset_mode = isset( $_GET['cr_reset'], $_GET['key'], $_GET['login'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['cr_reset'] ) );
$reset_complete = isset( $_GET['cr_reset_complete'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['cr_reset_complete'] ) );
$reset_sent = isset( $_GET['cr_reset_sent'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['cr_reset_sent'] ) );
$current_url = remove_query_arg( array( 'cr_reset_sent', 'cr_reset_complete' ) );
?>
<div class="cr-auth">
	<?php if ( $reset_complete ) : ?>
		<div class="cr-auth-success" role="status">
			<?php esc_html_e( 'Your password has been reset successfully. You can now log in.', 'custom-registration' ); ?>
		</div>
	<?php elseif ( $reset_mode && $reset_user ) : ?>
		<form class="cr-auth__form cr-reset-form" method="post">
			<div class="cr-auth__messages" aria-live="polite">
				<?php if ( $errors->has_errors() ) : ?>
					<div class="cr-auth-errors" role="alert"><ul><?php foreach ( $errors->get_error_messages() as $message ) : ?><li><?php echo esc_html( $message ); ?></li><?php endforeach; ?></ul></div>
				<?php endif; ?>
			</div>

			<p><?php echo esc_html( sprintf( __( 'Choose a new password for %s.', 'custom-registration' ), $reset_user->user_login ) ); ?></p>

			<div class="cr-auth-field">
				<label for="cr-reset-password"><?php esc_html_e( 'New Password', 'custom-registration' ); ?></label>
				<input type="password" id="cr-reset-password" name="password" autocomplete="new-password" minlength="8" required>
			</div>

			<div class="cr-auth-field">
				<label for="cr-reset-password-confirm"><?php esc_html_e( 'Confirm New Password', 'custom-registration' ); ?></label>
				<input type="password" id="cr-reset-password-confirm" name="password_confirm" autocomplete="new-password" minlength="8" required>
			</div>

			<input type="hidden" name="key" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['key'] ) ) ); ?>">
			<input type="hidden" name="login" value="<?php echo esc_attr( sanitize_text_field( wp_unslash( $_GET['login'] ) ) ); ?>">
			<input type="hidden" name="cr_auth_action" value="reset_password">
			<?php wp_nonce_field( 'cr_reset_password', 'cr_reset_nonce' ); ?>

			<button type="submit" class="cr-auth-submit">
				<span class="cr-auth-submit__text"><?php esc_html_e( 'Reset Password', 'custom-registration' ); ?></span>
				<span class="cr-auth-submit__loading" aria-hidden="true"><?php esc_html_e( 'Resetting...', 'custom-registration' ); ?></span>
			</button>
		</form>
	<?php else : ?>
		<form class="cr-auth__form cr-forgot-form" method="post">
			<div class="cr-auth__messages" aria-live="polite">
				<?php if ( $reset_sent ) : ?>
					<div class="cr-auth-success" role="status"><?php esc_html_e( 'If an account matches that information, a password reset email has been sent.', 'custom-registration' ); ?></div>
				<?php elseif ( $errors->has_errors() ) : ?>
					<div class="cr-auth-errors" role="alert"><ul><?php foreach ( $errors->get_error_messages() as $message ) : ?><li><?php echo esc_html( $message ); ?></li><?php endforeach; ?></ul></div>
				<?php endif; ?>
			</div>

			<div class="cr-auth-field">
				<label for="cr-user-login"><?php esc_html_e( 'Username or Email', 'custom-registration' ); ?></label>
				<input type="text" id="cr-user-login" name="user_login" autocomplete="username" required>
			</div>

			<input type="hidden" name="reset_page" value="<?php echo esc_url( $current_url ); ?>">
			<input type="hidden" name="cr_auth_action" value="forgot_password">
			<?php wp_nonce_field( 'cr_forgot_password', 'cr_forgot_nonce' ); ?>

			<button type="submit" class="cr-auth-submit">
				<span class="cr-auth-submit__text"><?php esc_html_e( 'Send Reset Link', 'custom-registration' ); ?></span>
				<span class="cr-auth-submit__loading" aria-hidden="true"><?php esc_html_e( 'Sending...', 'custom-registration' ); ?></span>
			</button>

			<p class="cr-auth-links">
				<a href="<?php echo esc_url( remove_query_arg( 'cr_forgot_password' ) ); ?>"><?php esc_html_e( 'Back to Login', 'custom-registration' ); ?></a>
			</p>
		</form>
	<?php endif; ?>
</div>
