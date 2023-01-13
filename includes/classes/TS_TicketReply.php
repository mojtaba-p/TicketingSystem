<?php

// if this file directly called by user then die!!
defined( 'ABSPATH' ) or die( 'No Access.' );

class TS_TicketReply extends TS_Post {

	/**
	 * Reply ID.
	 *
	 * @var integer
	 */
	private $ID;

	public function __construct() {
		parent::__construct();
		$this->post_type = "ts_ticket_reply";
	}

	/**
	 * set filed if declared.
	 *
	 * @param $name
	 * @param $value
	 */
	public function __set( $name, $value ) {
		if ( in_array( $name, array( 'author', 'parent', 'title', 'content' ) ) ) {
			$this->$name = $value;
		}
	}

	/**
	 * get field value;.
	 *
	 * @param $name
	 *
	 * @return mixed
	 */
	public function __get( $name ) {
		if ( in_array( $name, array( 'author', 'parent', 'title', 'content' ) ) ) {

			return $this->$name;
		}

		return false;
	}

	/**
	 * save ticket reply.
	 */
	public function insert() {
		$this->title = $this->parent . " پاسخ به تیکت شماره: ";
		$this->ID    = parent::insert();

		$ticket      = new TS_Ticket();
		$post_author = $ticket->load( $this->parent )->author;
		if ( $this->author != $post_author ) {
			update_post_meta( $this->parent, TS_TICKET_STATUS, "answered" );
		} else {
			update_post_meta( $this->parent, TS_TICKET_STATUS, "open" );
		}



		return $this->ID;
	}

	/**
	 * load all replies of a ticket
	 *
	 * @return array
	 */
	public static function load_all_replies( $ticket_id ) {

		$replies = get_posts( [
			'post_parent'    => $ticket_id,
			'post_type'      => "ts_ticket_reply",
			'posts_per_page' => - 1,
			'orderby'        => 'date',
			'order'          => TicketingSystem::$options['replies-order'],
		] );

		return $replies;

	}


	/**
	 * @param $query
	 */
	public static function set_replies_order( $query ) {

		if ( isset($query->query_vars["post_type"]) && "ts_ticket_reply" != $query->query_vars["post_type"] ) {
			return;
		}

		$args  = array( 'post_date' => TicketingSystem::$options['replies-order'] );
		$query->set( 'orderby', $args );

	}

}