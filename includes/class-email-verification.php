<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CR_Email_Verification {

	const META_VERIFIED = '_cr_email_verified';
	const META_TOKEN    = '_cr_email_verification_token';
	const META_EXPIRES  = '_cr_email_verification_expires';
	const RESEND_LIMIT  = 300;

	public function __construct() {
		add_shortcode( 'custom_verify_email', array( $this, 'render_verification' ) );
		add_shortcode( 'custom_resend_verification', array( $this, 'render_resend_form' ) );
		add_action( 'wp_ajax_nopriv_cr_resend_verification', array( $this, 'ajax_resend_verification' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	public function mark_unverified( $user_id ) {
		update_user_meta( $user_id, self::META_VERIFIED, 0 );
	}

	public function is_verified( $user_id ) {
		return '1' === (string) get_user_meta( $user_id, self::META_VERIFIED, true );
	}

	private function get_rate_limit_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		return 'cr_verify_resend_' . md5( $ip );
	}

	private function is_rate_limited() {
		return false !== get_transient( $this->get_rate_limit_key() );
	}

	private function set_rate_limit() {
		set_transient( $this->get_rate_limit_key(), 1, self::RESEND_LIMIT );
	}

	public function send_verification( $user_id ) {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_Error( 'verification_user', __( 'Unable to send the verification email.', 'custom-registration' ) );
		}

		if ( $this->is_verified( $user_id ) ) {
			return new WP_Error( 'already_verified', __( 'This email address is already verified.', 'custom-registration' ) );
		}

		$token   = wp_generate_password( 64, false, false );
		$expires = time() + DAY_IN_SECONDS;

		update_user_meta( $user_id, self::META_TOKEN, wp_hash( $token ) );
		update_user_meta( $user_id, self::META_EXPIRES, $expires );

		$page_url = apply_filters( 'cr_email_verification_url', home_url( '/' ), $user );
		$page_url = wp_validate_redirect( $page_url, home_url( '/' ) );

		$url = add_query_arg(
			array(
				'cr_verify' => '1',
				'user'      => $user_id,
				'token'     => $token,
			),
			$page_url
		);

		$subject = __( 'Verify your email address', 'custom-registration' );
		$message = sprintf(
			__( "Welcome to %s.\n\nPlease verify your email address by opening this link:\n%s\n\nThis link expires in 24 hours.", 'custom-registration' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			$url
		);

		if ( ! wp_mail( $user->user_email, $subject, $message ) ) {
			return new WP_Error( 'verification_mail', __( 'Unable to send the verification email. Please try again later.', 'custom-registration' ) );
		}

		return true;
	}

	public function resend_verification( $identifier ) {
		if ( $this->is_rate_limited() ) {
			return new WP_Error( 'rate_limited', __( 'Please wait a few minutes before requesting another verification email.', 'custom-registration' ) );
		}

		$identifier = sanitize_text_field( $identifier );
		$user = is_email( $identifier ) ? get_user_by( 'email', $identifier ) : get_user_by( 'login', $identifier );

		$this->set_rate_limit();

		if ( ! $user || $this->is_verified( $user->ID ) ) {
			return true;
		}

		return $this->send_verification( $user->ID );
	}

	public function ajax_resend_verification() {
		check_ajax_referer( 'cr_resend_verification', 'nonce' );

		$identifier = isset( $_POST['user_login'] ) ? wp_unslash( $_POST['user_login'] ) : '';

		if ( '' === trim( $identifier ) ) {
			wp_send_json_error(
				array( 'message' => __( 'Please enter your username or email address.', 'custom-registration' ) ),
				422
			);
		}

		$result = $this->resend_verification( $identifier );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'message' => $result->get_error_message() ), 429 === (int) $result->get_error_code() ? 429 : 400 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'If an unverified account matches that information, a verification email has been sent.', 'custom-registration' ),
			)
		);
	}

	public function verify( $user_id, $token ) {
		$user_id = absint( $user_id );
		$token   = sanitize_text_field( $token );

		if ( ! $user_id || '' === $token ) {
			return new WP_Error( 'invalid_verification', __( 'This verification link is invalid or has expired.', 'custom-registration' ) );
		}

		if ( $this->is_verified( $user_id ) ) {
			return true;
		}

		$stored_hash = get_user_meta( $user_id, self::META_TOKEN, true );
		$expires     = (int) get_user_meta( $user_id, self::META_EXPIRES, true );

		if ( ! $stored_hash || ! $expires || time() > $expires || ! hash_equals( $stored_hash, wp_hash( $token ) ) ) {
			return new WP_Error( 'invalid_verification', __( 'This verification link is invalid or has expired.', 'custom-registration' ) );
		}

		update_user_meta( $user_id, self::META_VERIFIED, 1 );
		delete_user_meta( $user_id, self::META_TOKEN );
		delete_user_meta( $user_id, self::META_EXPIRES );

		return true;
	}

	public function render_verification() {
		if ( ! isset( $_GET['cr_verify'], $_GET['user'], $_GET['token'] ) || '1' !== sanitize_text_field( wp_unslash( $_GET['cr_verify'] ) ) ) {
			return '';
		}

		$result = $this->verify(
			absint( $_GET['user'] ),
			wp_unslash( $_GET['token'] )
		);

		if ( is_wp_error( $result ) ) {
			return '<div class="cr-auth-errors" role="alert"><p>' . esc_html( $result->get_error_message() ) . '</p></div>';
		}

		return '<div class="cr-auth-success" role="status"><p>' . esc_html__( 'Your email has been verified successfully. You can now log in.', 'custom-registration' ) . '</p></div>';
	}

	public function render_resend_form() {
		ob_start();
		?>
		<div class="cr-verification-resend">
			<form class="cr-verification-resend__form" method="post">
				<label for="cr-resend-verification-login"><?php esc_html_e( 'Username or Email', 'custom-registration' ); ?></label>
				<input type="text" id="cr-resend-verification-login" name="user_login" autocomplete="username" required>
				<button type="submit"><?php esc_html_e( 'Resend Verification Email', 'custom-registration' ); ?></button>
				<div class="cr-verification-resend__message" aria-live="polite"></div>
			</form>
		</div>
		<?php
		return ob_get_clean();
	}

	public function enqueue_assets() {
		if ( ! is_singular() ) {
			return;
		}

		global $post;

		if ( ! $post || ( ! has_shortcode( $post->post_content, 'custom_resend_verification' ) && ! has_shortcode( $post->post_content, 'custom_verify_email' ) ) ) {
			return;
		}

		wp_enqueue_script(
			'cr-email-verification',
			CR_PLUGIN_URL . 'assets/js/email-verification.js',
			array(),
			CR_VERSION,
			true
		);

		wp_localize_script(
			'cr-email-verification',
			'crEmailVerification',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cr_resend_verification' ),
			)
		);
	}
}
