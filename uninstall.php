<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

$meta_keys = array(
	'_cr_email_verified',
	'_cr_email_verification_token',
	'_cr_email_verification_expires',
);

$users = get_users(
	array(
		'fields' => 'ID',
	)
);

foreach ( $users as $user_id ) {
	foreach ( $meta_keys as $meta_key ) {
		delete_user_meta( $user_id, $meta_key );
	}
}
