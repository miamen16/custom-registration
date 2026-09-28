<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CR_Registration {

	private $errors;
	private $old_data;
	private $email_verification;

	const REGISTRATION_MAX_ATTEMPTS = 10;
	const REGISTRATION_LOCKOUT = 900;
	const AVAILABILITY_MAX_ATTEMPTS = 60;
	const AVAILABILITY_LOCKOUT = 60;

	public function __construct() {
		$this->errors             = new WP_Error();
		$this->old_data           = array();
		$this->email_verification = new CR_Email_Verification();

		add_action( 'init', array( $this, 'process_registration' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'custom_register_form', array( $this, 'render_form' ) );
		add_action( 'wp_ajax_nopriv_cr_register_user', array( $this, 'ajax_register' ) );
		add_action( 'wp_ajax_nopriv_cr_check_availability', array( $this, 'ajax_check_availability' ) );
	}

	private function get_registration_throttle_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		return 'cr_registration_' . md5( $ip );
	}

	private function registration_is_throttled() {
		return (int) get_transient( $this->get_registration_throttle_key() ) >= self::REGISTRATION_MAX_ATTEMPTS;
	}

	private function record_registration_attempt() {
		$key   = $this->get_registration_throttle_key();
		$count = (int) get_transient( $key ) + 1;
		set_transient( $key, $count, self::REGISTRATION_LOCKOUT );
	}

	private function get_availability_throttle_key() {
		$ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : 'unknown';
		return 'cr_availability_' . md5( $ip );
	}

	private function availability_is_throttled() {
		return (int) get_transient( $this->get_availability_throttle_key() ) >= self::AVAILABILITY_MAX_ATTEMPTS;
	}

	private function record_availability_attempt() {
		$key   = $this->get_availability_throttle_key();
		$count = (int) get_transient( $key ) + 1;
		set_transient( $key, $count, self::AVAILABILITY_LOCKOUT );
	}

	private function get_registration_data() {
		return array(
			'first_name'       => sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ),
			'last_name'        => sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ),
			'username'         => sanitize_user( wp_unslash( $_POST['username'] ?? '' ), true ),
			'email'            => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
			'password'         => wp_unslash( $_POST['password'] ?? '' ),
			'password_confirm' => wp_unslash( $_POST['password_confirm'] ?? '' ),
		);
	}

	private function create_user( $data ) {
		$user_data = apply_filters( 'cr_registration_user_data', $data );

		if ( ! is_array( $user_data ) ) {
			return new WP_Error(
				'registration_data',
				__( 'Registration could not be completed. Please try again.', 'custom-registration' )
			);
		}

		$user_id = CR_User::create( $user_data );

		if ( is_wp_error( $user_id ) ) {
			return $user_id;
		}

		$this->email_verification->mark_unverified( $user_id );

		$verification = $this->email_verification->send_verification( $user_id );

		if ( is_wp_error( $verification ) ) {
			wp_delete_user( $user_id );
			return $verification;
		}

		return $user_id;
	}

	public function process_registration() {
		if ( is_user_logged_in() ) {
			return;
		}

		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}

		$action = isset( $_POST['cr_action'] )
			? sanitize_key( wp_unslash( $_POST['cr_action'] ) )
			: '';

		if ( 'register' !== $action ) {
			return;
		}

		$nonce = isset( $_POST['cr_register_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['cr_register_nonce'] ) )
			: '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'cr_register' ) ) {
			$this->errors->add(
				'nonce',
				__( 'Security check failed. Please refresh the page and try again.', 'custom-registration' )
			);
			return;
		}

		if ( $this->registration_is_throttled() ) {
			$this->errors->add( 'registration_throttled', __( 'Too many registration attempts. Please try again later.', 'custom-registration' ) );
			return;
		}

		$this->record_registration_attempt();

		$data = $this->get_registration_data();

		$this->old_data = array(
			'first_name' => $data['first_name'],
			'last_name'  => $data['last_name'],
			'username'   => $data['username'],
			'email'      => $data['email'],
		);

		$this->errors = CR_Validator::validate( $data );

		if ( $this->errors->has_errors() ) {
			return;
		}

		$user_id = $this->create_user( $data );

		if ( is_wp_error( $user_id ) ) {
			$this->errors = $user_id;
			return;
		}

		$verification_url = apply_filters( 'cr_email_verification_url', home_url( '/' ), get_userdata( $user_id ) );
		wp_safe_redirect( add_query_arg( 'cr_verification_sent', '1', $verification_url ) );
		exit;
	}

	public function ajax_register() {
		if ( is_user_logged_in() ) {
			wp_send_json_error(
				array( 'message' => __( 'Already logged in.', 'custom-registration' ) ),
				400
			);
		}

		check_ajax_referer( 'cr_register', 'nonce' );

		if ( $this->registration_is_throttled() ) {
			wp_send_json_error( array( 'message' => __( 'Too many registration attempts. Please try again later.', 'custom-registration' ) ), 429 );
		}

		$this->record_registration_attempt();

		$data   = $this->get_registration_data();
		$errors = CR_Validator::validate( $data );

		if ( $errors->has_errors() ) {
			wp_send_json_error( array( 'errors' => $errors->get_error_messages() ), 422 );
		}

		$user_id = $this->create_user( $data );

		if ( is_wp_error( $user_id ) ) {
			wp_send_json_error( array( 'errors' => $user_id->get_error_messages() ), 400 );
		}

		$verification_url = apply_filters( 'cr_email_verification_url', home_url( '/' ), get_userdata( $user_id ) );

		wp_send_json_success(
			array(
				'verification_required' => true,
				'redirect'              => esc_url_raw( add_query_arg( 'cr_verification_sent', '1', $verification_url ) ),
				'message'               => __( 'Registration successful. Please check your email to verify your account before logging in.', 'custom-registration' ),
			)
		);
	}

	public function ajax_check_availability() {
		if ( is_user_logged_in() ) {
			wp_send_json_error(
				array( 'message' => __( 'Already logged in.', 'custom-registration' ) ),
				400
			);
		}

		check_ajax_referer( 'cr_register', 'nonce' );

		if ( $this->availability_is_throttled() ) {
			wp_send_json_error( array( 'message' => __( 'Too many availability checks. Please try again later.', 'custom-registration' ) ), 429 );
		}

		$this->record_availability_attempt();

		$type  = isset( $_POST['type'] ) ? sanitize_key( wp_unslash( $_POST['type'] ) ) : '';
		$value = isset( $_POST['value'] ) ? wp_unslash( $_POST['value'] ) : '';

		if ( 'username' === $type ) {
			$value = sanitize_user( $value, true );

			if ( '' === $value || ! validate_username( $value ) ) {
				wp_send_json_success(
					array(
						'available' => false,
						'valid'     => false,
						'message'   => __( 'Please enter a valid username.', 'custom-registration' ),
					)
				);
			}

			$exists = username_exists( $value );

			wp_send_json_success(
				array(
					'available' => ! $exists,
					'valid'     => true,
					'message'   => $exists
						? __( 'This username is already registered.', 'custom-registration' )
						: __( 'Username is available.', 'custom-registration' ),
				)
			);
		}

		if ( 'email' === $type ) {
			$value = sanitize_email( $value );

			if ( '' === $value || ! is_email( $value ) ) {
				wp_send_json_success(
					array(
						'available' => false,
						'valid'     => false,
						'message'   => __( 'Please enter a valid email address.', 'custom-registration' ),
					)
				);
			}

			$exists = email_exists( $value );

			wp_send_json_success(
				array(
					'available' => ! $exists,
					'valid'     => true,
					'message'   => $exists
						? __( 'This email address is already registered.', 'custom-registration' )
						: __( 'Email address is available.', 'custom-registration' ),
				)
			);
		}

		wp_send_json_error(
			array( 'message' => __( 'Invalid availability check.', 'custom-registration' ) ),
			400
		);
	}

	public function enqueue_assets() {
		if ( ! is_singular() ) {
			return;
		}

		global $post;

		if ( ! $post || ! has_shortcode( $post->post_content, 'custom_register_form' ) ) {
			return;
		}

		wp_enqueue_style(
			'cr-registration',
			CR_PLUGIN_URL . 'assets/css/registration.css',
			array(),
			CR_VERSION
		);

		wp_enqueue_script(
			'cr-registration',
			CR_PLUGIN_URL . 'assets/js/registration.js',
			array(),
			CR_VERSION,
			true
		);

		wp_localize_script(
			'cr-registration',
			'crRegistration',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'cr_register' ),
			)
		);
	}

	public function render_form() {
		if ( is_user_logged_in() ) {
			return '<p class="cr-notice">' . esc_html__( 'You are already registered and logged in.', 'custom-registration' ) . '</p>';
		}

		$form_action = esc_url( remove_query_arg( array( 'cr_registered', 'cr_error' ) ) );
		$errors      = $this->errors;
		$data        = $this->old_data;

		ob_start();

		include CR_PLUGIN_DIR . 'templates/registration-form.php';

		return ob_get_clean();
	}
}
