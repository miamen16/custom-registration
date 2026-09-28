<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CR_Registration {

	private $errors;
	private $old_data;

	public function __construct() {
		$this->errors   = new WP_Error();
		$this->old_data = array();

		add_action( 'init', array( $this, 'process_registration' ) );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_shortcode( 'custom_register_form', array( $this, 'render_form' ) );
	}

	public function process_registration() {
		if ( 'POST' !== strtoupper( $_SERVER['REQUEST_METHOD'] ?? '' ) ) {
			return;
		}

		if ( empty( $_POST['cr_action'] ) || 'register' !== $_POST['cr_action'] ) {
			return;
		}

		if ( ! isset( $_POST['cr_register_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['cr_register_nonce'] ) ), 'cr_register' ) ) {
			$this->errors->add( 'nonce', __( 'Security check failed. Please try again.', 'custom-registration' ) );
			return;
		}

		$this->old_data = array(
			'first_name' => sanitize_text_field( wp_unslash( $_POST['first_name'] ?? '' ) ),
			'last_name'  => sanitize_text_field( wp_unslash( $_POST['last_name'] ?? '' ) ),
			'username'   => sanitize_user( wp_unslash( $_POST['username'] ?? '' ), true ),
			'email'      => sanitize_email( wp_unslash( $_POST['email'] ?? '' ) ),
		);

		$data = array(
			'first_name'       => $this->old_data['first_name'],
			'last_name'        => $this->old_data['last_name'],
			'username'         => $this->old_data['username'],
			'email'            => $this->old_data['email'],
			'password'         => wp_unslash( $_POST['password'] ?? '' ),
			'password_confirm' => wp_unslash( $_POST['password_confirm'] ?? '' ),
		);

		$this->errors = CR_Validator::validate( $data );

		if ( $this->errors->has_errors() ) {
			return;
		}

		$user_id = CR_User::create( $data );

		if ( is_wp_error( $user_id ) ) {
			$this->errors = $user_id;
			return;
		}

		wp_set_auth_cookie( $user_id, true );

		$redirect_url = apply_filters( 'cr_registration_redirect_url', home_url( '/' ), $user_id );

		wp_safe_redirect( $redirect_url );
		exit;
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
