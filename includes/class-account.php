<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CR_Account {

	private $errors;
	private $message;

	public function __construct() {
		$this->errors  = new WP_Error();
		$this->message = '';

		add_action( 'init', array( $this, 'process_requests' ) );
		add_action( 'wp_ajax_cr_update_profile', array( $this, 'ajax_update_profile' ) );
		add_action( 'wp_ajax_cr_change_password', array( $this, 'ajax_change_password' ) );
		add_action( 'wp_ajax_cr_logout_user', array( $this, 'ajax_logout' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'custom_account', array( $this, 'render_account' ) );
	}

	public function process_requests() {
		$request_method = isset( $_SERVER['REQUEST_METHOD'] )
			? sanitize_key( wp_unslash( $_SERVER['REQUEST_METHOD'] ) )
			: '';

		if ( 'post' !== strtolower( $request_method ) || ! is_user_logged_in() ) {
			return;
		}

		$action = isset( $_POST['cr_account_action'] ) ? sanitize_key( wp_unslash( $_POST['cr_account_action'] ) ) : '';

		if ( 'update_profile' === $action ) {
			$this->process_update_profile();
		} elseif ( 'change_password' === $action ) {
			$this->process_change_password();
		} elseif ( 'logout' === $action ) {
			$this->process_logout();
		}
	}

	private function current_user() {
		return wp_get_current_user();
	}

	private function get_profile_data() {
		$user = $this->current_user();

		return array(
			'username'   => $user->user_login,
			'first_name' => $user->first_name,
			'last_name'  => $user->last_name,
			'email'      => $user->user_email,
		);
	}

	private function update_profile( $data ) {
		$user   = $this->current_user();
		$errors = new WP_Error();

		$first_name = sanitize_text_field( $data['first_name'] ?? '' );
		$last_name  = sanitize_text_field( $data['last_name'] ?? '' );
		$email      = sanitize_email( $data['email'] ?? '' );

		if ( '' === $first_name ) {
			$errors->add( 'first_name_required', __( 'First name is required.', 'custom-registration' ) );
		}

		if ( '' === $last_name ) {
			$errors->add( 'last_name_required', __( 'Last name is required.', 'custom-registration' ) );
		}

		if ( '' === $email || ! is_email( $email ) ) {
			$errors->add( 'email_invalid', __( 'Please enter a valid email address.', 'custom-registration' ) );
		} else {
			$existing = get_user_by( 'email', $email );
			if ( $existing && (int) $existing->ID !== (int) $user->ID ) {
				$errors->add( 'email_exists', __( 'This email address is already registered.', 'custom-registration' ) );
			}
		}

		if ( $errors->has_errors() ) {
			return $errors;
		}

		$email_changed = strtolower( $email ) !== strtolower( $user->user_email );

		$result = wp_update_user(
			array(
				'ID'         => $user->ID,
				'first_name' => $first_name,
				'last_name'  => $last_name,
				'user_email' => $email,
			)
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( $email_changed ) {
			$verification = new CR_Email_Verification();
			$verification->mark_unverified( $user->ID );
			$verification_result = $verification->send_verification( $user->ID );

			if ( is_wp_error( $verification_result ) ) {
				wp_update_user(
					array(
						'ID'         => $user->ID,
						'user_email' => $user->user_email,
					)
				);
				update_user_meta( $user->ID, CR_Email_Verification::META_VERIFIED, 1 );

				return new WP_Error(
					'email_verification_mail',
					__( 'Your profile was not updated because the verification email could not be sent.', 'custom-registration' )
				);
			}
		}

		return true;
	}

	private function change_password( $data ) {
		$user = $this->current_user();
		$current_password = $data['current_password'] ?? '';
		$new_password = $data['password'] ?? '';
		$confirm_password = $data['password_confirm'] ?? '';

		if ( '' === $current_password || ! wp_check_password( $current_password, $user->user_pass, $user->ID ) ) {
			return new WP_Error( 'current_password_invalid', __( 'Your current password is incorrect.', 'custom-registration' ) );
		}

		if ( strlen( $new_password ) < 8 ) {
			return new WP_Error( 'password_short', __( 'Password must be at least 8 characters.', 'custom-registration' ) );
		}

		if ( $new_password !== $confirm_password ) {
			return new WP_Error( 'password_mismatch', __( 'Passwords do not match.', 'custom-registration' ) );
		}

		if ( $current_password === $new_password ) {
			return new WP_Error( 'password_same', __( 'Your new password must be different from your current password.', 'custom-registration' ) );
		}

		wp_set_password( $new_password, $user->ID );
		wp_set_auth_cookie( $user->ID, true );

		return true;
	}

	private function process_update_profile() {
		$nonce = isset( $_POST['cr_profile_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['cr_profile_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'cr_update_profile' ) ) {
			$this->errors->add( 'nonce', __( 'Security check failed. Please refresh the page and try again.', 'custom-registration' ) );
			return;
		}

		$result = $this->update_profile(
			array(
				'first_name' => wp_unslash( $_POST['first_name'] ?? '' ),
				'last_name'  => wp_unslash( $_POST['last_name'] ?? '' ),
				'email'      => wp_unslash( $_POST['email'] ?? '' ),
			)
		);

		if ( is_wp_error( $result ) ) {
			$this->errors = $result;
			return;
		}

		$this->message = __( 'Your profile has been updated successfully.', 'custom-registration' );
	}

	private function process_change_password() {
		$nonce = isset( $_POST['cr_password_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['cr_password_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'cr_change_password' ) ) {
			$this->errors->add( 'nonce', __( 'Security check failed. Please refresh the page and try again.', 'custom-registration' ) );
			return;
		}

		$result = $this->change_password(
			array(
				'current_password' => wp_unslash( $_POST['current_password'] ?? '' ),
				'password'         => wp_unslash( $_POST['password'] ?? '' ),
				'password_confirm' => wp_unslash( $_POST['password_confirm'] ?? '' ),
			)
		);

		if ( is_wp_error( $result ) ) {
			$this->errors = $result;
			return;
		}

		$this->message = __( 'Your password has been changed successfully.', 'custom-registration' );
	}

	private function process_logout() {
		$nonce = isset( $_POST['cr_logout_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['cr_logout_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'cr_logout' ) ) {
			$this->errors->add( 'nonce', __( 'Security check failed. Please refresh the page and try again.', 'custom-registration' ) );
			return;
		}

		wp_logout();

		$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
		wp_safe_redirect( wp_validate_redirect( $redirect, home_url( '/' ) ) );
		exit;
	}

	public function ajax_update_profile() {
		$this->verify_logged_in_ajax();
		check_ajax_referer( 'cr_update_profile', 'nonce' );

		$result = $this->update_profile(
			array(
				'first_name' => wp_unslash( $_POST['first_name'] ?? '' ),
				'last_name'  => wp_unslash( $_POST['last_name'] ?? '' ),
				'email'      => wp_unslash( $_POST['email'] ?? '' ),
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'errors' => $result->get_error_messages() ), 422 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Your profile has been updated successfully.', 'custom-registration' ),
				'user'    => $this->get_profile_data(),
			)
		);
	}

	public function ajax_change_password() {
		$this->verify_logged_in_ajax();
		check_ajax_referer( 'cr_change_password', 'nonce' );

		$result = $this->change_password(
			array(
				'current_password' => wp_unslash( $_POST['current_password'] ?? '' ),
				'password'         => wp_unslash( $_POST['password'] ?? '' ),
				'password_confirm' => wp_unslash( $_POST['password_confirm'] ?? '' ),
			)
		);

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'errors' => $result->get_error_messages() ), 422 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'Your password has been changed successfully.', 'custom-registration' ),
			)
		);
	}

	public function ajax_logout() {
		$this->verify_logged_in_ajax();
		check_ajax_referer( 'cr_logout', 'nonce' );

		wp_logout();

		$redirect = isset( $_POST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_to'] ) ) : '';
		$redirect = wp_validate_redirect( $redirect, home_url( '/' ) );

		wp_send_json_success( array( 'redirect' => esc_url_raw( $redirect ) ) );
	}

	private function verify_logged_in_ajax() {
		if ( ! is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'You are not logged in.', 'custom-registration' ) ), 401 );
		}
	}

	public function enqueue_assets() {
		if ( ! is_singular() ) {
			return;
		}

		global $post;

		if ( ! $post || ! has_shortcode( $post->post_content, 'custom_account' ) ) {
			return;
		}

		wp_enqueue_style(
			'cr-account',
			CR_PLUGIN_URL . 'assets/css/account.css',
			array(),
			CR_VERSION
		);

		wp_enqueue_script(
			'cr-account',
			CR_PLUGIN_URL . 'assets/js/account.js',
			array(),
			CR_VERSION,
			true
		);

		wp_localize_script(
			'cr-account',
			'crAccount',
			array(
				'ajaxUrl'      => admin_url( 'admin-ajax.php' ),
				'profileNonce' => wp_create_nonce( 'cr_update_profile' ),
				'passwordNonce' => wp_create_nonce( 'cr_change_password' ),
				'logoutNonce' => wp_create_nonce( 'cr_logout' ),
			)
		);
	}

	public function render_account() {
		if ( ! is_user_logged_in() ) {
			return '<p class="cr-account-notice">' . esc_html__( 'You must be logged in to view your account.', 'custom-registration' ) . '</p>';
		}

		$data    = $this->get_profile_data();
		$errors  = $this->errors;
		$message = $this->message;

		ob_start();
		include CR_PLUGIN_DIR . 'templates/account-form.php';
		return ob_get_clean();
	}
}
