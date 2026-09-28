<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="cr-verification-notice" role="status">
	<h3><?php esc_html_e( 'Check your email', 'custom-registration' ); ?></h3>
	<p><?php echo esc_html( $message ); ?></p>
</div>
