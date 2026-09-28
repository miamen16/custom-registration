<?php

use PHPUnit\Framework\TestCase;

class WP_Error {
    private $errors = array();
    public function add( $code, $message ) { $this->errors[ $code ][] = $message; }
    public function has_errors() { return ! empty( $this->errors ); }
    public function get_error_codes() { return array_keys( $this->errors ); }
}

function sanitize_text_field( $value ) { return trim( strip_tags( (string) $value ) ); }
function sanitize_user( $value ) { return preg_replace( '/[^a-zA-Z0-9_.-]/', '', (string) $value ); }
function sanitize_email( $value ) { return strtolower( trim( (string) $value ) ); }
function is_email( $value ) { return false !== filter_var( $value, FILTER_VALIDATE_EMAIL ); }
function validate_username( $value ) { return '' !== $value; }
function username_exists( $value ) { return false; }
function email_exists( $value ) { return false; }
function __( $text ) { return $text; }

require_once __DIR__ . '/includes/class-validator.php';

final class CRValidatorTest extends TestCase {
    public function test_valid_data_passes(): void {
        $errors = CR_Validator::validate(array(
            'first_name' => 'Mohamed',
            'last_name' => 'Ibrahim',
            'username' => 'mohamed_dev',
            'email' => 'mohamed@example.com',
            'password' => 'StrongPass1!',
            'password_confirm' => 'StrongPass1!',
        ));

        $this->assertFalse($errors->has_errors());
    }

    public function test_password_mismatch_fails(): void {
        $errors = CR_Validator::validate(array(
            'first_name' => 'Mohamed',
            'last_name' => 'Ibrahim',
            'username' => 'mohamed_dev',
            'email' => 'mohamed@example.com',
            'password' => 'StrongPass1!',
            'password_confirm' => 'DifferentPass1!',
        ));

        $this->assertContains('password_mismatch', $errors->get_error_codes());
    }
}
