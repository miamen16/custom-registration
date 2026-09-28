<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CR_Validator {

	public static function validate( $data ) {
		$errors = new WP_Error();

		$first_name = sanitize_text_field( $data['first_name'] ?? '' );
		$last_name  = sanitize_text_field( $data['last_name'] ?? '' );
		$username   = sanitize_user( $data['username'] ?? '', true );
		$email      = sanitize_email( $data['email'] ?? '' );
		$password   = $data['password'] ?? '';
		$confirm    = $data['password_confirm'] ?? '';

		if ( '' === $first_name ) {
			$errors->add( 'first_name_required', __( 'First name is required.', 'custom-registration' ) );
		}

		if ( '' === $last_name ) {
			$errors->add( 'last_name_required', __( 'Last name is required.', 'custom-registration' ) );
		}

		if ( '' === $username ) {
			$errors->add( 'username_required', __( 'Username is required.', 'custom-registration' ) );
		} elseif ( ! validate_username( $username ) ) {
			$errors->add( 'username_invalid', __( 'Please enter a valid username.', 'custom-registration' ) );
		} elseif ( username_exists( $username ) ) {
			$errors->add( 'username_exists', __( 'This username is already registered.', 'custom-registration' ) );
		}

		if ( '' === $email || ! is_email( $email ) ) {
			$errors->add( 'email_invalid', __( 'Please enter a valid email address.', 'custom-registration' ) );
		} elseif ( email_exists( $email ) ) {
			$errors->add( 'email_exists', __( 'This email address is already registered.', 'custom-registration' ) );
		}

		if ( strlen( $password ) < 8 ) {
			$errors->add( 'password_short', __( 'Password must be at least 8 characters.', 'custom-registration' ) );
		}

		if ( $password !== $confirm ) {
			$errors->add( 'password_mismatch', __( 'Passwords do not match.', 'custom-registration' ) );
		}

		return $errors;
	}
}
