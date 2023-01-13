<?php

if ( ! function_exists( "ts_display_setting_page" ) ) {

	/**
	 * display setting page of ticketing system plugin.
	 *
	 */
	function ts_display_setting_page() {

		ts_ticket_save_plugin_settings();

		$action_url      = add_query_arg( array( "post_type" => "ts_ticket", "page" => "setting.php" ) );
		$current_options = ts_ticket_get_options();
		require_once TS_VIEW . 'setting.php';
	}


	/**
	 * save TicketingSystem plugin setting
	 * check capabilities and verify nonce
	 */
	function ts_ticket_save_plugin_settings() {
		if ( ! isset( $_POST['ts_setting_page_nonce'] ) ) {
			return;
		}

		if ( ! current_user_can( "manage_options" ) ) {
			wp_die( 'Unauthorized user' );
		}

		if ( ! wp_verify_nonce( $_POST["ts_setting_page_nonce"], "ts_setting_page" ) ) {
			return;
		}

		$ts_plugin_options                       = array();
		$orders                                  = array( "desc" => "DESC", "asc" => "ASC" );
		$allows                                  = array( "yes" => 1, "no" => 0 );
		$ts_plugin_options["default-agent"]      = get_user_by( "ID", $_POST["default-agent"] )->ID;
		$ts_plugin_options["ticket-submit-page"] = get_post( $_POST["ticket-submit-page"] )->ID;
		$ts_plugin_options["tickets-per-page"]   = intval( $_POST["tickets-per-page"] );
		$ts_plugin_options["replies-order"]      = isset( $orders[ $_POST["replies-order"] ] ) ? $orders[ $_POST["replies-order"] ] : "ASC";
		$ts_plugin_options["user-can-register"]  = isset( $allows[ $_POST["user-can-register"] ] ) ? $allows[ $_POST["user-can-register"] ] : 0;

		ts_ticket_save_options( $ts_plugin_options );
	}


	/**
	 * serialize options and save them.
	 *
	 * @param $options . options that must be save.
	 */
	function ts_ticket_save_options( $options ) {
		$ts_ticket_options = serialize( $options );
		update_option( "ts_ticket_options", $ts_ticket_options );
	}


	/**
	 * unserialize options and return them.
	 *
	 * @return array
	 */
	function ts_ticket_get_options() {
		$defaults          = array(
			"default-agent"      => 1,
			"ticket-submit-page" => 0,
			"tickets-list-page"  => 0,
			"tickets-per-page"   => 5,
			"replies-order"      => "ASC",
			"user-can-register"  => 1
		);
		$options           = get_option( "ts_ticket_options" );
		$ts_ticket_options = unserialize( $options );

		return wp_parse_args( $ts_ticket_options, $defaults );
	}
}

if ( ! function_exists( "ts_ticket_define_setting_page" ) ) {

	add_action( 'admin_menu', 'ts_ticket_define_setting_page' );

	/**
	 * create setting page.
	 */
	function ts_ticket_define_setting_page() {
		add_submenu_page(
			"edit.php?post_type=ts_ticket",
			"تنظیمات",
			'تنظیمات',
			'manage_options',
			'setting.php',
			'ts_display_setting_page'
		);
	}

}


