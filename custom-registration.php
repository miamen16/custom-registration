<?php
/**
 * Plugin Name: Custom Registration
 * Description: A lightweight custom WordPress registration form without third-party plugins.
 * Version: 1.0.1
 * Author: Mohamed Ibrahim
 * License: GPL-2.0-or-later
 * Text Domain: custom-registration
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'CR_VERSION', '1.0.1' );
define( 'CR_PLUGIN_FILE', __FILE__ );
define( 'CR_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'CR_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

require_once CR_PLUGIN_DIR . 'includes/class-validator.php';
require_once CR_PLUGIN_DIR . 'includes/class-user.php';
require_once CR_PLUGIN_DIR . 'includes/class-registration.php';
require_once CR_PLUGIN_DIR . 'includes/class-login.php';
require_once CR_PLUGIN_DIR . 'includes/class-email-verification.php';
require_once CR_PLUGIN_DIR . 'includes/class-account.php';

function cr_init() {
	return new CR_Registration();
}

function cr_login_init() {
	return new CR_Login();
}

function cr_account_init() {
	return new CR_Account();
}

cr_init();
cr_login_init();
cr_account_init();
