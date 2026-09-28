<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CR_User {

	public static function create( $data ) {
		$user_id = wp_insert_user(
			array(
				'user_login' => $data['username'],
				'user_pass'  => $data['password'],
				'user_email' => $data['email'],
				'first_name' => $data['first_name'],
				'last_name'  => $data['last_name'],
				'role'       => 'subscriber',
			)
		);

		return $user_id;
	}
}
