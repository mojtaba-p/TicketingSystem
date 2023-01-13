<?php

add_action( 'plugins_loaded', 'init_form_error_variable' );
function init_form_error_variable() {
	global $ts_form_errors;
	$ts_form_errors = new WP_Error();
}

add_shortcode( 'ts_ticket_submit', 'ticket_submit' );
/**
 * function that generate ticket submit page.
 */
function ticket_submit() {
	global $ts_form_errors;
	ob_start();
	if ( ! is_user_logged_in() ) {
		include( TS_VIEW . 'front/login.php' );
	} else {
		include( TS_VIEW . 'front/submit-ticket.php' );
	}
	return ob_get_clean();
}

function ts_shortcode_check_nonce( $name, $ts_form_errors ) {

	if ( ! wp_verify_nonce( $_POST["ts_{$name}"], $name ) ) {
		$error = new WP_ERROR( 'ts_nonce_error', 'لطفا دوباره سعی کنید' );

		return $error;
	}

	return 0;

}