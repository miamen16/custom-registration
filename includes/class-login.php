<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CR_Login {

	private $errors;
	private $old_data;

	public function __construct() {
		$this->errors   = new WP_Error();
		$this->old_data = array();

		add_action( 'init', array( $this, 'process_requests' ) );
		add_action( 'wp_ajax_nopriv_cr_login_user', array( $this, 'ajax_login' ) );
		add_action( 'wp_ajax_nopriv_cr_request_password_reset', array( $this, 'ajax_request_password_reset' ) );
		add_action( 'wp_ajax_nopriv_cr_reset_password', array( $this, 'ajax_reset_password' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'custom_login_form', array( $this, 'render_login_form' ) );
		add_shortcode( 'custom_forgot_password_form', array( $this, 'render_forgot_password_form' ) );
	}

	public function process_requests() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}

		$action = isset( $_POST['cr_auth_action'] ) ? sanitize_key( wp_unslash( $_POST['cr_auth_action'] ) ) : '';

		if ( 'login' === $action ) {
			$this->process_login();
		} elseif ( 'forgot_password' === $action ) {
			$this->process_forgot_password();
		} elseif ( 'reset_password' === $action ) {
			$this->process_reset_password();
		}
	}

	private function process_login() {
		if ( is_user_logged_in() ) {
			return;
		}

		$nonce = isset( $_POST['cr_login_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['cr_login_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'cr_login' ) ) {
			$this->errors->add( 'nonce', __( 'Security check failed. Please refresh the page and try again.', 'custom-registration' ) );
			return;
		}

		$data = $this->get_login_data();
		$this->old_data = array( 'login' => $data['login'], 'remember' => $data['remember'] );

		$result = $this->authenticate( $data );

		if ( is_wp_error( $result ) ) {
			$this->errors = $result;
			return;
		}

		$redirect = $this->get_login_redirect( $result );
		wp_safe_redirect( $redirect );
		exit;
	}

	private function get_login_data() {
		return array(
			'login'    => sanitize_text_field( wp_unslash( $_POST['login'] ?? '' ) ),
			'password' => wp_unslash( $_POST['password'] ?? '' ),
			'remember' => ! empty( $_POST['remember'] ),
		);
	}

	private function authenticate( $data ) {
		if ( '' === $data['login'] || '' === $data['password'] ) {
			return new WP_Error( 'login_required', __( 'Username/email and password are required.', 'custom-registration' ) );
		}

		$user = wp_authenticate( $data['login'], $data['password'] );

		if ( is_wp_error( $user ) ) {
			return new WP_Error( 'login_failed', __( 'Invalid username/email or password.', 'custom-registration' ) );
		}

		wp_set_auth_cookie( $user->ID, $data['remember'] );

		return $user;
	}

	public function ajax_login() {
		if ( is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Already logged in.', 'custom-registration' ) ), 400 );
		}

		check_ajax_referer( 'cr_login', 'nonce' );

		$data = $this->get_login_data();
		$user = $this->authenticate( $data );

		if ( is_wp_error( $user ) ) {
			wp_send_json_error( array( 'errors' => $user->get_error_messages() ), 401 );
		}

		wp_send_json_success( array( 'redirect' => esc_url_raw( $this->get_login_redirect( $user ) ) ) );
	}

	private function get_login_redirect( $user ) {
		$redirect = isset( $_REQUEST['redirect_to'] ) ? esc_url_raw( wp_unslash( $_REQUEST['redirect_to'] ) ) : '';

		if ( ! $redirect ) {
			$redirect = home_url( '/' );
		}

		return apply_filters( 'cr_login_redirect_url', $redirect, $user );
	}

	private function process_forgot_password() {
		if ( is_user_logged_in() ) {
			return;
		}

		$nonce = isset( $_POST['cr_forgot_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['cr_forgot_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'cr_forgot_password' ) ) {
			$this->errors->add( 'nonce', __( 'Security check failed. Please refresh the page and try again.', 'custom-registration' ) );
			return;
		}

		$identifier = sanitize_text_field( wp_unslash( $_POST['user_login'] ?? '' ) );

		if ( '' === $identifier ) {
			$this->errors->add( 'missing', __( 'Please enter your username or email address.', 'custom-registration' ) );
			return;
		}

		$reset_page = isset( $_POST['reset_page'] ) ? esc_url_raw( wp_unslash( $_POST['reset_page'] ) ) : '';
		$result = $this->send_password_reset( $identifier, $reset_page );

		if ( is_wp_error( $result ) ) {
			$this->errors = $result;
			return;
		}

		wp_safe_redirect( add_query_arg( 'cr_reset_sent', '1', wp_get_referer() ?: home_url( '/' ) ) );
		exit;
	}

	private function get_safe_local_url( $url ) {
		if ( ! $url ) {
			return '';
		}

		$url = esc_url_raw( $url );
		$site_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$url_host  = wp_parse_url( $url, PHP_URL_HOST );

		return $url_host && $site_host && strtolower( $url_host ) === strtolower( $site_host ) ? $url : '';
	}

	private function find_user( $identifier ) {
		$user = is_email( $identifier ) ? get_user_by( 'email', $identifier ) : get_user_by( 'login', $identifier );
		return $user;
	}

	private function send_password_reset( $identifier, $reset_page = '' ) {
		$user = $this->find_user( $identifier );

		if ( ! $user ) {
			return new WP_Error( 'reset_failed', __( 'If an account matches that information, a password reset email has been sent.', 'custom-registration' ) );
		}

		$key = get_password_reset_key( $user );

		if ( is_wp_error( $key ) ) {
			return new WP_Error( 'reset_failed', __( 'Unable to create a password reset request. Please try again.', 'custom-registration' ) );
		}

		$reset_page = $this->get_safe_local_url( $reset_page );
		if ( ! $reset_page ) {
			$reset_page = home_url( '/' );
		}

		$reset_url = add_query_arg(
			array(
			'cr_reset' => '1',
			'key'      => rawurlencode( $key ),
			'login'    => rawurlencode( $user->user_login ),
		),
		apply_filters( 'cr_password_reset_url', home_url( '/' ), $user )
		);

		$subject = __( 'Password Reset', 'custom-registration' );
		$message = sprintf(
			__( "Someone requested a password reset for your account on %s.\n\nUse this link to choose a new password:\n%s\n\nIf you did not request this, you can ignore this email.", 'custom-registration' ),
			wp_specialchars_decode( get_bloginfo( 'name' ), ENT_QUOTES ),
			$reset_url
		);

		if ( ! wp_mail( $user->user_email, $subject, $message ) ) {
			return new WP_Error( 'reset_failed', __( 'Unable to send the password reset email. Please try again.', 'custom-registration' ) );
		}

		return true;
	}

	public function ajax_request_password_reset() {
		if ( is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Already logged in.', 'custom-registration' ) ), 400 );
		}

		check_ajax_referer( 'cr_forgot_password', 'nonce' );

		$identifier = sanitize_text_field( wp_unslash( $_POST['user_login'] ?? '' ) );
		$reset_page = isset( $_POST['reset_page'] ) ? esc_url_raw( wp_unslash( $_POST['reset_page'] ) ) : '';
		$result     = $this->send_password_reset( $identifier, $reset_page );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'errors' => $result->get_error_messages() ), 400 );
		}

		wp_send_json_success(
			array(
				'message' => __( 'If an account matches that information, a password reset email has been sent.', 'custom-registration' ),
			)
		);
	}

	private function get_reset_data() {
		return array(
			'key'             => sanitize_text_field( wp_unslash( $_POST['key'] ?? $_GET['key'] ?? '' ) ),
			'login'           => sanitize_text_field( wp_unslash( $_POST['login'] ?? $_GET['login'] ?? '' ) ),
			'password'        => wp_unslash( $_POST['password'] ?? '' ),
			'password_confirm'=> wp_unslash( $_POST['password_confirm'] ?? '' ),
		);
	}

	private function validate_reset_key( $key, $login ) {
		if ( '' === $key || '' === $login ) {
			return new WP_Error( 'invalid_key', __( 'This password reset link is invalid or has expired.', 'custom-registration' ) );
		}

		$user = check_password_reset_key( $key, $login );

		if ( is_wp_error( $user ) ) {
			return new WP_Error( 'invalid_key', __( 'This password reset link is invalid or has expired.', 'custom-registration' ) );
		}

		return $user;
	}

	private function validate_new_password( $data ) {
		$errors = new WP_Error();

		if ( strlen( $data['password'] ) < 8 ) {
			$errors->add( 'password_short', __( 'Password must be at least 8 characters.', 'custom-registration' ) );
		}

		if ( ! hash_equals( $data['password'], $data['password_confirm'] ) ) {
			$errors->add( 'password_mismatch', __( 'Passwords do not match.', 'custom-registration' ) );
		}

		return $errors;
	}

	private function reset_password( $data ) {
		$user = $this->validate_reset_key( $data['key'], $data['login'] );

		if ( is_wp_error( $user ) ) {
			return $user;
		}

		$errors = $this->validate_new_password( $data );

		if ( $errors->has_errors() ) {
			return $errors;
		}

		reset_password( $user, $data['password'] );
		return true;
	}

	private function process_reset_password() {
		if ( is_user_logged_in() ) {
			return;
		}

		$nonce = isset( $_POST['cr_reset_nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['cr_reset_nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'cr_reset_password' ) ) {
			$this->errors->add( 'nonce', __( 'Security check failed. Please refresh the page and try again.', 'custom-registration' ) );
			return;
		}

		$result = $this->reset_password( $this->get_reset_data() );

		if ( is_wp_error( $result ) ) {
			$this->errors = $result;
			return;
		}

		wp_safe_redirect( add_query_arg( 'cr_reset_complete', '1', wp_get_referer() ?: home_url( '/' ) ) );
		exit;
	}

	public function ajax_reset_password() {
		if ( is_user_logged_in() ) {
			wp_send_json_error( array( 'message' => __( 'Already logged in.', 'custom-registration' ) ), 400 );
		}

		check_ajax_referer( 'cr_reset_password', 'nonce' );

		$result = $this->reset_password( $this->get_reset_data() );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( array( 'errors' => $result->get_error_messages() ), 422 );
		}

		wp_send_json_success(
			array(
				'redirect' => esc_url_raw( apply_filters( 'cr_login_redirect_url', home_url( '/' ), null ) ),
			)
		);
	}

	public function enqueue_assets() {
		wp_enqueue_style(
			'cr-auth',
			CR_PLUGIN_URL . 'assets/css/auth.css',
			array(),
			CR_VERSION
		);

		wp_enqueue_script(
			'cr-auth',
			CR_PLUGIN_URL . 'assets/js/auth.js',
			array(),
			CR_VERSION,
			true
		);

		wp_localize_script(
			'cr-auth',
			'crAuth',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'loginNonce' => wp_create_nonce( 'cr_login' ),
				'forgotNonce' => wp_create_nonce( 'cr_forgot_password' ),
				'resetNonce' => wp_create_nonce( 'cr_reset_password' ),
			)
		);
	}

	public function render_login_form() {
		if ( is_user_logged_in() ) {
			return '<p class="cr-auth-notice">' . esc_html__( 'You are already logged in.', 'custom-registration' ) . '</p>';
		}

		$data = $this->old_data;
		$errors = $this->errors;
		$redirect_to = isset( $_GET['redirect_to'] ) ? esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ) : '';

		ob_start();
		include CR_PLUGIN_DIR . 'templates/login-form.php';
		return ob_get_clean();
	}

	public function render_forgot_password_form() {
		if ( is_user_logged_in() ) {
			return '<p class="cr-auth-notice">' . esc_html__( 'You are already logged in.', 'custom-registration' ) . '</p>';
		}

		$data = array();
		$errors = $this->errors;

		if ( isset( $_GET['cr_forgot_password'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['cr_forgot_password'] ) ) && ! isset( $_GET['cr_reset'] ) ) {
			ob_start();
			include CR_PLUGIN_DIR . 'templates/forgot-password-form.php';
			return ob_get_clean();
		}
		$reset_user = null;
		$reset_mode = isset( $_GET['cr_reset'], $_GET['key'], $_GET['login'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['cr_reset'] ) );

		if ( $reset_mode ) {
			$reset_user = $this->validate_reset_key(
				sanitize_text_field( wp_unslash( $_GET['key'] ) ),
				sanitize_text_field( wp_unslash( $_GET['login'] ) )
			);
			if ( is_wp_error( $reset_user ) ) {
				$errors = $reset_user;
				$reset_user = null;
			}
		}

		ob_start();
		include CR_PLUGIN_DIR . 'templates/forgot-password-form.php';
		return ob_get_clean();
	}
}
