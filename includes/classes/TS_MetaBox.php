<?php

// if this file directly called by user then die!!
defined( 'ABSPATH' ) or die( 'No Access.' );

class TS_MetaBox {

	private static $instance;

	/**
	 * singleton pattern implementation.
	 * @return TS_MetaBox
	 */
	public static function getInstance(): TS_MetaBox {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new TS_MetaBox();
		}

		return self::$instance;
	}


	private function __construct() {

		$this->do_add_actions();

	}

	/**
	 * loading views.
	 * @return void
	 */
	public static function load_ts_ticket_meta_box( $post, $args ): void {
		$view = $args['args']['view'] . '.php';
		include( TS_VIEW."metabox/".$view );
	}


	/**
	 * adding metabox to ts_ticket post type
	 * @return void
	 */
	public function add_ticket_metabox(): void {

		if ( isset( $_GET['post'] ) ) {
			add_meta_box( 'ts_ticket_content_metabox', 'تیکت', array(
				self::class,
				'load_ts_ticket_meta_box'
			), 'ts_ticket', 'normal', 'high', [ 'view' => 'ticket-content' ] );

			add_meta_box( 'ts_ticket_meta_box', 'پاسخ تیکت', array(
				self::class,
				'load_ts_ticket_meta_box'
			), 'ts_ticket', 'normal', 'high', [ 'view' => 'ticket-reply' ] );

			add_meta_box( 'ts_ticket_detail_meta_box', 'مشخصات', array(
				self::class,
				'load_ts_ticket_meta_box'
			), 'ts_ticket', 'side', 'high',
				[
					'view'     => 'ticket-detail',
					'agents'   => TS_User::get_agents(),
					'status'   => get_post_meta( $_GET["post"], TS_TICKET_STATUS, true ),
					'agent'    => get_post_meta( $_GET["post"], TS_TICKET_AGENT, true ),
					'priority' => get_post_meta( $_GET["post"], TS_TICKET_PRIORITY, true ),

				] );

			add_meta_box( 'ts_ticket_user_meta_box', 'ارسال کننده', array(
				self::class,
				'load_ts_ticket_meta_box'
			), 'ts_ticket', 'side', 'high', [ 'view' => 'ticket-user-detail' ] );


			$notes = new TS_Note( array( "ticket_id" => $_GET['post'] ) );
			add_meta_box( 'ts_ticket_note_meta_box', 'یادداشت ها', array(
				self::class,
				'load_ts_ticket_meta_box'
			), 'ts_ticket', 'side', 'low', [
				'view'  => 'ticket-note',
				'notes' => $notes->get_notes_by_ticket()
			] );
		}

	}

	// remove publish metabox
	public function remove_publish_metabox() {

		if ( isset( $_GET['post'] ) ) {
			remove_meta_box( 'submitdiv', 'ts_ticket', 'side' );
		}
	}


	/**
	 * execute add_action function for meta boxes.
	 * @return void
	 */
	private function do_add_actions(): void {
		add_action( 'screen_layout_columns', array( $this, 'add_ticket_metabox' ) );
		add_action( 'admin_menu', array( $this, 'remove_publish_metabox' ) );
	}

}