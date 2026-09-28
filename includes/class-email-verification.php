<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CR_Email_Verification {

	const META_VERIFIED = '_cr_email_verified';
	const META_TOKEN    = '_cr_email_verification_token';
	const META_EXPIRES  = '_cr_email_verification_expires';

	public function __construct() {
		add_shortcode( 'custom_verify_email', array( $this, 'render_verification' ) );
	}

	public function mark_unverified( $user_id ) {
		update_user_meta( $user_id, self::META_VERIFIED, 0 );
	}

	public function is_verified( $user_id ) {
		return '1' === (string) get_user_meta( $user_id, self::META_VERIFIED, true );
	}

	public function send_verification( $user_id ) {
		$user = get_userdata( $user_id );

		if ( ! $user ) {
			return new WP_Error( 'verification_user', __( 'Unable to send the verification email.', 'custom-registration' ) );
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
			'token'     => rawurlencode( $token ),
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
			return new WP_Error( 'verification_mail', __( 'Unable to send the verification email. Please try again.', 'custom-registration' ) );
		}

		return true;
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
}
