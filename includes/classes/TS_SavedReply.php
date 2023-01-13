<?php

// if this file directly called by user then die!!
defined( 'ABSPATH' ) or die( 'No Access.' );

class TS_SavedReply {

	private static $instance;

	private static $taxonomy_name = 'ts_ticket_saved_reply';

	public static function getInstance() {
		if ( ! isset( self::$instance ) ) {
			self::$instance = new TS_SavedReply();
		}

		return self::$instance;
	}


	/**
	 * TS_SavedReply constructor.
	 */
	private function __construct() {
		self::do_add_action();
	}

	/**
	 * register saved reply taxonomy.
	 * we don't use as taxonomy.
	 */
	public static function register_ts_ticket_saved_reply_taxonomy(): void {

		$labels = array(
			'name'              => 'پاسخ های آماده',
			'singular_name'     => 'پاسخ آماده',
			'search_items'      => 'جستجوی پاسخ های آماده',
			'popular_items'     => 'پاسخ های آماده محبوب',
			'all_items'         => 'تمام پاسخ های آماده',
			'parent_item'       => null,
			'parent_item_colon' => null,
			'edit_item'         => 'ویرایش پاسخ آماده',
			'update_item'       => 'بروزرسانی پاسخ آماده',
			'add_new_item'      => 'افزودن پاسخ آماده',
			'new_item_name'     => 'نام پاسخ جدید',
			'not_found'         => 'هیچ پاسخی پیدا نشد',
			'menu_name'         => 'پاسخ های آماده',
			'back_to_items'     => 'بازگشت به پاسخ های آماده'
		);

		$args = array(
			'labels'            => $labels,
			'hierarchical'      => false,
			'show_ui'           => true,
			'show_admin_column' => false,
			'query_var'         => true,
			'capabilities'      => array(
				'manage_terms' => 'manage_sr',
				'edit_terms'   => 'edit_sr',
				'delete_terms' => 'delete_sr',
				'assign_terms' => 'ts_edit_ticket'
			)
		);

		register_taxonomy( self::$taxonomy_name, array( "ts_ticket" ), $args );

	}

	/**
	 * @param $taxonomy
	 * start buffering output for attach select box to description textarea
	 */
	public static function begin_form_buffer( $taxonomy ) {
		ob_start( array( 'self', 'add_variable_to_description' ) );
	}

	/**
	 * get buffered text and attach select box
	 *
	 * @param $buffer
	 *
	 * @return mixed
	 */
	public static function add_variable_to_description( $buffer ) {

		$wp_desc_editor   = '<textarea';
		$ts_ticket_editor = '
					<select name="variables" id="variables">
						<option value="">درج متغیر</option>
						<optgroup label="کاربر">
							<option value="{user.fname}"> نام کاربر</option>
							<option value="{user.lname}"> نام خانوادگی</option>
							<option value="{user.email}"> ایمیل کاربر</option>
						</optgroup>
						<hr>
						<optgroup label="پشتیبان">
							<option value="{agent.fname}"> نام پشتیبان</option>
							<option value="{agent.lname}"> نام خانوادگی پشتیبان</option>
						</optgroup>
						<hr>
						<optgroup label="تیکت">
							<option value="{ticket.id}"> شناسه تیکت</option>
							<option value="{ticket.date}">تاریخ شروع تیکت</option>
						</optgroup>
						
					</select>
					<textarea';

		$buffer = str_replace( $wp_desc_editor, $ts_ticket_editor, $buffer );

		$description_label   = 'tag-description">' . __( 'Description' );
		$buffer              = str_replace( $description_label, 'tag-description">' .__( "Reply" ), $buffer );
		$under_textarea_desc = __( 'The description is not prominent by default; however, some themes may show it.' );
		$buffer              = str_replace( $under_textarea_desc, "", $buffer );

		return $buffer;
	}

	/**
	 * stop buffering data
	 *
	 * @param $taxonomy
	 */
	public static function end_form_buffer( $taxonomy ) {
		ob_end_flush();
	}

	/**
	 * remove saved replies tag like metabox from post page
	 */
	public static function remove_sr_metabox() {
		remove_meta_box( "tagsdiv-ts_ticket_saved_reply", "ts_ticket", "side" );
	}

	/*
	 * return all saved replies
	 */
	public static function get_all_saved_replies() {

		$args  = array( "taxonomy" => self::$taxonomy_name, "hide_empty" => false );
		$terms = get_terms( $args );

		return $terms;

	}

	/**
	 * replace variables in saved reply text with real information
	 *
	 * @param $text
	 * @param $ticket_id
	 *
	 * @return mixed
	 */
	public static function replace_variables( $text, $ticket_id ) {

		$ticket   = get_post( $ticket_id );
		$user_id  = $ticket->post_author;
		$user     = get_userdata( $user_id );
		$agent_id = get_post_meta( $ticket_id, TS_TICKET_AGENT, true );
		$agent    = get_userdata( $agent_id );


		$variables_data = array(
			"user.fname"  => $user->first_name,
			"user.lname"  => $user->last_name,
			"user.email"  => $user->user_email,
			"agent.fname" => $agent->first_name,
			"agent.lname" => $agent->last_name,
			"ticket.id"   => $ticket->ID,
			"ticket.date" => $ticket->post_date
		);

		if ( empty($agent->first_name) || empty($agent->last_name) ){
			$variables_data["agent.fname"] = $agent->nickname;
		}

		foreach ( $variables_data as $key => $value ) {
			$text = str_replace( "{" . $key . "}", "{$value}", $text );
		}

		return $text;
	}

	/**
	 * responds to ajax request
	 */
	public static function get_saved_reply() {
		$reply_id  = $_POST["term"];
		$ticket_id = $_POST["ticket"];
		$term      = get_term( $reply_id, self::$taxonomy_name );

		$text = self::replace_variables( $term->description, $ticket_id );
		echo $text;
		wp_die();
	}

	public function do_add_action() {

		$taxonomy = self::$taxonomy_name;

		add_action( "init", array( self::class, "register_ts_ticket_saved_reply_taxonomy" ) );
		add_action( "admin_menu", array( self::class, "remove_sr_metabox" ) );

		add_action( "{$taxonomy}_pre_add_form", array( self::class, 'begin_form_buffer' ), 10, 1 );
		add_action( "{$taxonomy}_add_form", array( self::class, 'end_form_buffer' ), 10, 1 );

		add_action( "{$taxonomy}_term_edit_form_top", array( self::class, 'begin_form_buffer' ), 10, 1 );
		add_action( "{$taxonomy}_edit_form_fields", array( self::class, 'end_form_buffer' ), 10, 1 );

		add_action( "wp_ajax_get_saved_reply", array( self::class, "get_saved_reply" ) );
	}


}