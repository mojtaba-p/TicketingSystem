<?php

// if this file directly called by user then die!!
defined( 'ABSPATH' ) or die( 'No Access.' );

class TS_Ticket extends TS_Post {

	/**
	 * ID of ticket.
	 *
	 * @var integer
	 */
	private $ID;

	/**
	 * taxonomy id for ticket.
	 *
	 * @var integer
	 */
	protected $taxonomy;

	/**
	 * replies of ticket.
	 *
	 * @var array|TS_TicketReply
	 */
	private $replies;


	/**
	 * notes of ticket.
	 *
	 * @var TS_Note[]
	 */
	private $notes;

	/**
	 * priority of ticket
	 *
	 * @var integer
	 */
	private $priority;

	/**
	 * list of priorities.
	 *
	 * @var array
	 */
	public static $priorities = array(
		[ "id" => 1, "name" => "normal", "text" => "عادی" ],
		[ "id" => 2, "name" => "important", "text" => "مهم" ],
		[ "id" => 3, "name" => "instantaneous", "text" => "فوری" ],
		[ "id" => 4, "name" => "immediate", "text" => "آنی" ],
	);

	/**
	 * list of statuses
	 *
	 * @var array
	 */
	public static $statuses = array(
		array( "open", "باز" ),
		array( "answered", "جواب داده شده" ),
		array( "closed", "بسته" ),
	);

	/**
	 * TS_Ticket constructor.
	 */
	public function __construct() {
		parent::__construct();
		$this->post_type = "ts_ticket";

	}

	/**
	 * set filed if declared.
	 *
	 * @param $name
	 * @param $value
	 */
	public function __set( $name, $value ) {
		if ( in_array( $name, array( 'author', 'parent', 'title', 'content', "taxonomy", "priority" ) ) ) {
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
		if ( in_array( $name, array( 'author', 'parent', 'title', 'content', "taxonomy", "priority" ) ) ) {

			return $this->$name;
		}

		return false;
	}

	/**
	 * @override
	 */
	public function load( $id ) {
		parent::load( $id );
		$this->ID      = $id;
		$this->replies = TS_TicketReply::load_all_replies( $id );
		$notes         = new TS_Note( array( "ticket_id" => $id ) );
		$this->notes   = $notes->get_notes_by_ticket();

		return $this;
	}

	/**
	 * save ticket reply.
	 */
	public function insert() {
		$this->ID = parent::insert();
		add_post_meta( $this->ID, TS_TICKET_PRIORITY, $this->priority, true );

		return $this->ID;

	}


	/**
	 * run when a ticket updated or created
	 *
	 * @param $post_id
	 * @param $post
	 * @param $update
	 *
	 * @return int
	 */
	public static function after_save( $post_id, $post, $update ) {

		// Don't save on revisions
		if ( wp_is_post_revision( $post_id ) ) {
			return false;
		}

		// Don't save on autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		$current_ticket = new TS_Ticket();
		$current_ticket->load( $post_id );

		if ( $update ) {
			$current_ticket->after_update();

			return 0;
		}

		$current_ticket->after_insert();

		return 0;
	}


	/**
	 * run when ticket want to update.
	 */
	private function after_update() {
		$this->save_ticket_note( $this->ID, $_POST['note'] );
		$this->save_ticket_reply( $this->ID, $_POST['ts-ticket-reply'] );
		$this->update_ticket_agent( $_POST['current_agent'], $_POST['agent'] );
		$this->update_ticket_priority( $_POST['current_priority'], $_POST['priority'] );
	}

	/**
	 *
	 */
	private function after_insert() {

		$default_agent = TicketingSystem::$options['default-agent'];

		add_post_meta( $this->ID, TS_TICKET_STATUS, "open", true );
		add_post_meta( $this->ID, TS_TICKET_AGENT, $default_agent, true );

		// log history
		$history = new TS_Post();

		$history->title     = "تیکت ایجاد شد";
		$history->content   = "ticket_created";
		$history->author    = 1;
		$history->parent    = $this->ID;
		$history->post_type = "ts_ticket_history";

		$HID = $history->insert();


		if ( $HID < 1 ) {
			die( "Error On Create Event" );
		}

	}

	/**
	 * check if reply sent then save that
	 *
	 * @param $post_id
	 * @param $reply_content
	 */
	private function save_ticket_reply( $post_id, $reply_content ) {
		// check is reply sent
		if ( isset( $reply_content ) && ( '' != trim( $reply_content ) ) ) {
			$reply = new TS_TicketReply();

			$reply->author  = get_current_user_id();
			$reply->content = $reply_content;
			$reply->parent  = $post_id;

			$reply_id = $reply->insert();

			if ( 0 != $reply_id ) {
				$attachment_id = TS_Post::upload_attachment( $reply_id );
			}
		}

	}

	/**
	 * save notes that belong to this ticket.
	 *
	 * @param $post_id
	 * @param $note_text
	 *
	 * @return void
	 */
	private function save_ticket_note( $post_id, $note_text ) {
		if ( strlen( $note_text ) > 1 ) {
			$note_content = sanitize_text_field( $note_text );
			$note         = new TS_Note( array(
				"parent"  => $post_id,
				"author"  => get_current_user_id(),
				"content" => $note_content,
			) );
		}
	}

	/**
	 * check current agent and agent passed.
	 * if they are different set new agent
	 *
	 * @param $current_agent
	 * @param $new_agent
	 */
	public function update_ticket_agent( $current_agent, $new_agent ) {
		if ( current_user_can( "ts_assign_agent" ) && ( $current_agent != $new_agent ) ) {
			update_post_meta( $this->ID, TS_TICKET_AGENT, $new_agent );

			$history            = new TS_Post();
			$history->title     = "تغییر پشتیبان";
			$history->content   = "ticket_agent_changed";
			$history->author    = get_current_user_id();
			$history->parent    = $this->ID;
			$history->post_type = "ts_ticket_history";

			$HID = $history->insert();
		}
	}

	/**
	 * check current priority and priority passed.
	 * if they are different set new priority
	 *
	 * @param $current_priority
	 * @param $new_priority
	 */
	public function update_ticket_priority( $current_priority, $new_priority ) {
		if ( ( current_user_can( "ts_modify_priority" ) ) && ( $current_priority != $new_priority ) && ( $new_priority > 0 && $new_priority <= 4 ) ) {
			update_post_meta( $this->ID, TS_TICKET_PRIORITY, $new_priority );
		}
	}


	/**
	 * close open ticket and open closed ticket
	 */
	public static function toggle_ticket_status() {

		global $ts_form_errors;

		if ( ! isset( $_POST["ts_toggle_ticket"] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST["ts_toggle_ticket"], "ts_toggle_ticket" ) ) {
			$error = new \WP_ERROR( 'ts_nonce_error', 'لطفا دوباره سعی کنید' );
			$ts_form_errors->add( $error->get_error_code(), $error->get_error_message() );
		}

		if ( ! TS_User::user_can_modify_ticket( $_POST["ticket-id"] ) ) {
			return;
		}

		$ticket_id = get_post( $_POST["ticket-id"] )->ID;

		if ( is_wp_error( $ticket_id ) ) {
			return;
		}

		$status = self::get_ticket_status( $ticket_id );

		if ( $status != "closed" ) {
			self::close_ticket( $ticket_id, get_current_user_id() );
		} else {
			self::open_ticket( $ticket_id, get_current_user_id() );
		}

		wp_safe_redirect( $_POST["_wp_http_referer"] );
		exit();
	}

	/**
	 * filter the tickets to show only specific user tickets.
	 * fires pre get posts and when post type equals to "ts_ticket"
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public static function set_ticket_author( $query ) {
		$post_type = $query->query_vars["post_type"] ?? null;
		if ( ! self::check_front_side_ticket( $post_type ) ) {
			return $query;
		}

		// filter results to get current user tickets
		$query->set( "author", get_current_user_id() );

		return $query;
	}

	/**
	 * set ticket list items count per page
	 *
	 * @param $query
	 *
	 * @return mixed
	 */
	public static function set_ticket_posts_per_page( $query ) {
		$post_type = $query->query_vars["post_type"] ?? null;
		if ( ! self::check_front_side_ticket( $post_type ) ) {
			return $query;
		}

		$tickets_per_page = TicketingSystem::$options['tickets-per-page'];
		$query->set( "posts_per_page", $tickets_per_page );

		return $query;
	}


	/**
	 * close ticket by url
	 *
	 * @param $content . content of post.
	 * @param $post_id .
	 *
	 * @return mixed
	 */
	public static function close_ticket_by_url( $content, $post_id ) {

		if ( ! isset( $_GET['close-ticket'] ) ) {
			return $content;
		}

		if ( ! wp_verify_nonce( $_GET['_wpnonce'] ) ) {
			return $content;
		}

		self::close_ticket( $post_id, get_current_user_id() );

		return $content;

	}


	/**
	 * close ticket by url
	 *
	 * @param $content . content of post.
	 * @param $post_id .
	 *
	 * @return mixed
	 */
	public static function open_ticket_by_url( $content, $post_id ) {

		if ( ! isset( $_GET['open-ticket'] ) ) {
			return $content;
		}

		if ( ! wp_verify_nonce( $_GET['_wpnonce'] ) ) {
			return $content;
		}

		self::open_ticket( $post_id, get_current_user_id() );

		return $content;

	}

	/**
	 * generate paginate links with custom style
	 */
	public static function ticket_paginate_links() {

		global $wp_query;

		/** Stop execution if there's only 1 page */
		if ( $wp_query->max_num_pages <= 1 ) {
			return;
		}

		$paged = get_query_var( 'paged' ) ? absint( get_query_var( 'paged' ) ) : 1;
		$max   = intval( $wp_query->max_num_pages );

		/** Add current page to the array */
		if ( $paged >= 1 ) {
			$links[] = $paged;
		}

		/** Add the pages around the current page to the array */
		if ( $paged >= 3 ) {
			$links[] = $paged - 1;
			$links[] = $paged - 2;
		}

		if ( ( $paged + 2 ) <= $max ) {
			$links[] = $paged + 2;
			$links[] = $paged + 1;
		}

		echo '<div class="navigation"><ul class="pagination">' . "\n";

		/** Previous Post Link */
		if ( get_previous_posts_link() ) {
			add_filter( "previous_posts_link_attributes", function () {
				return 'class="page-link"';
			} );
			printf( '<li class="page-item">%s</li>' . "\n", get_previous_posts_link( "«" ) );
		}

		/** Link to first page, plus ellipses if necessary */
		if ( ! in_array( 1, $links ) ) {
			$class = ' class="page-item ';
			$class .= 1 == $paged ? ' active' : '';
			$class .= '"';
			printf( '<li%s><a href="%s" class="page-link">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( 1 ) ), '1' );

			if ( ! in_array( 2, $links ) ) {
				echo '<li>…</li>';
			}
		}

		/** Link to current page, plus 2 pages in either direction if necessary */
		sort( $links );
		foreach ( (array) $links as $link ) {
			$class = ' class="page-item ';
			$class .= $paged == $link ? ' active' : '';
			$class .= '"';
			printf( '<li%s><a href="%s" class="page-link">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( $link ) ), $link );
		}

		/** Link to last page, plus ellipses if necessary */
		if ( ! in_array( $max, $links ) ) {
			if ( ! in_array( $max - 1, $links ) ) {
				echo '<li>…</li>' . "\n";
			}
			$class = ' class="page-item ';
			$class .= $paged == $max ? ' active' : '';
			$class .= '"';
			printf( '<li%s><a href="%s" class="page-link">%s</a></li>' . "\n", $class, esc_url( get_pagenum_link( $max ) ), $max );
		}

		/** Next Post Link */
		if ( get_next_posts_link() ) {
			add_filter( "next_posts_link_attributes", function () {
				return ' class="page-link" ';
			} );
			printf( '<li class="page-item">%s</li>' . "\n", get_next_posts_link( "»" ) );
		}

		echo '</ul></div>' . "\n";

	}

	/**
	 * check is posts from frontend and post type equal to ts_ticket
	 *
	 * @param $post_type
	 *
	 * @return boolean
	 */
	public static function check_front_side_ticket( $post_type ) {
		// don't touch other queries
		if ( "ts_ticket" != $post_type ) {
			return false;
		}

		// don't touch admin side queries
		if ( is_admin() ) {
			return false;
		}

		return true;
	}

	/**
	 * update post meta _ts_status to open
	 *
	 * @param $ticket_id . post id that meta has been changed.
	 * @param $user_id   . who open the ticket.
	 */
	public static function open_ticket( $ticket_id, $user_id ) {
		$curremt  = TS_Ticket::get_ticket_status($ticket_id);
		if ($curremt == "open"){
			return 0 ;
		}
		update_post_meta( $ticket_id, TS_TICKET_STATUS, "open" );

		// log history
		$history = new TS_Post();

		$history->title     = "تیکت باز شد";
		$history->content   = "ticket_opened";
		$history->author    = $user_id;
		$history->parent    = $ticket_id;
		$history->post_type = "ts_ticket_history";

		$HID = $history->insert();

	}

	/**
	 *  update post meta _ts_status to closed
	 *
	 * @param $ticket_id . post id that meta has been changed
	 * @param $user_id   . who close the ticket.
	 */
	public static function close_ticket( $ticket_id, $user_id ) {

		update_post_meta( $ticket_id, TS_TICKET_STATUS, "closed" );

		// log history
		$history = new TS_Post();

		$history->title     = "تیکت بسته شد";
		$history->content   = "ticket_closed";
		$history->author    = $user_id;
		$history->parent    = $ticket_id;
		$history->post_type = "ts_ticket_history";

		$HID = $history->insert();
	}

	/**
	 * return ticket priority
	 *
	 *
	 * @param        $ticket_id
	 * @param string $field
	 *
	 * @return mixed
	 */
	public static function get_ticket_priority( $ticket_id, $field = "id" ) {
		$priority = get_post_meta( $ticket_id, TS_TICKET_PRIORITY, true );

		switch ( $field ) {
			case "name":
				$value = self::$priorities[ $priority - 1 ]["name"];
				break;

			case "text":
				$value = self::$priorities[ $priority - 1 ]["text"];
				break;

			default:
				$value = $priority;
				break;
		}

		return $value;
	}

	/**
	 * return ticket status
	 *
	 * @param $ticket_id
	 * @param $field
	 *
	 * @return mixed
	 */
	public static function get_ticket_status( $ticket_id, $field = "id" ) {
		$post_status = get_post_meta( $ticket_id, TS_TICKET_STATUS, true );

		if ( $field == "id" ) {
			return $post_status;
		}

		switch ( $post_status ) {
			case "open":
				$status = "باز";
				break;

			case "answered":
				$status = "پاسخ داده شده";
				break;

			case "closed":
				$status = "بسته شده";
				break;

		}

		return $status;
	}

	public static function get_ticket_agent( $ticket_id, $field = "id" ) {
		$post_agent = get_post_meta( $ticket_id, TS_TICKET_AGENT, true );

		if ( $field == "id" ) {
			return $post_agent;
		}

		$agent = get_user_by( "ID", $post_agent )->$field;

		return $agent;
	}

	public static function get_taxonomies( $ticket_id ) {
		$terms = wp_get_post_terms(  $ticket_id, "ticket_type" );
		$taxonomies = '';
		foreach( $terms as $term ){
			$taxonomies .= $term->name.",";
		}
		$taxonomies = rtrim($taxonomies,', ');
		return $taxonomies;
	}

	/**
	 *
	 * @param $status . the status want to count
	 *
	 * @return int|null|string
	 */
	public static function get_tickets_count_by_status( $status ) {
		global $wpdb;
		$query         = "SELECT COUNT(*) FROM {$wpdb->prefix}posts INNER JOIN {$wpdb->prefix}postmeta ON {$wpdb->prefix}postmeta.post_id = {$wpdb->prefix}posts.ID WHERE `meta_key` = '_ts_status' AND `meta_value` = '{$status}' AND `post_status` = 'publish'";
		$tickets_count = $wpdb->get_var( $query );

		return $tickets_count;
	}
}