<?php

// if this file directly called by user then die!!
defined( 'ABSPATH' ) or die( 'No Access.' );

class TS_User {

	private $error;

	/**
	 * begin to create user.
	 *
	 * @param $first_name
	 * @param $last_name
	 * @param $username
	 * @param $email
	 * @param $password
	 *
	 * @return int. user id on success, zero on error
	 */
	public function create_user( $first_name, $last_name, $username, $email, $password ) {

		$user_can_register = TicketingSystem::$options['user-can-register'];
		if ( ! $user_can_register ) {
			wp_die( "User Cant register " );
		}

		if ( ( null == trim( $first_name ) ) || null == trim( $last_name ) || null == trim( $email ) || null == trim( $password ) ) {

			$this->error = new \WP_Error( 'fill_error', "لطفا تمامی فیلد ها را پر کنید" );

			return 0;
		}
		
		
		if ( ! is_email( $email ) ) {
			$this->error = new \WP_Error( 'invalid_email', 'ایمیل وارد شده نامعتبر است' );

			return 0;
		}

		$username = isset($username) ? $username : $email;

		$userdata = array(
			'user_pass'            => $password,
			'user_login'           => $username,
			'user_nicename'        => "{$first_name}",
			'user_email'           => $email,
			'display_name'         => "{$first_name} {$last_name}",
			'first_name'           => $first_name,
			'last_name'            => $last_name,
			'show_admin_bar_front' => false,
		);

		$user_id = wp_insert_user( $userdata );

		if ( is_wp_error( $user_id ) ) {
			$this->error = new \WP_Error( $user_id->get_error_code(), $user_id->get_error_message($user_id->get_error_code()) );
			return 0;
		}

		$user = new \WP_User( $user_id );
		$user->set_role( 'ts_customer' );

		return $user->ID;

	}

	/**
	 * specify that is ticket belongs to this user or not.
	 *
	 * @param $ticket_id
	 *
	 * @return boolean
	 */
	public static function user_can_modify_ticket( $ticket_id ) {

		if ( get_post( $ticket_id )->post_author == get_current_user_id() ) {
			return true;
		}

		if ( is_user_admin() ) {
			return true;
		}

		return false;

	}

	/**
	 * create new roles to manage or create ticket
	 */
	public static function add_roles() {
		$subscriber = get_role( "subscriber" );
		add_role( 'ts_agent_admin', "مدیر پشتیبانی", $subscriber->capabilities );
		add_role( 'ts_agent', "پشتیبان (پاسخگو)", $subscriber->capabilities );
		add_role( 'ts_customer', 'کاربر', $subscriber->capabilities );
		get_role("ts_customer")->add_cap("ts_read_ticket");
	}

	public static function remove_roles() {
		remove_role( "ts_agent_admin" );
		remove_role( "ts_agent" );
		remove_role( "ts_customer" );
	}

	/**
	 * add ticket related capabilities to administrator and ts_agent_admin
	 */
	public static function add_admin_capabilities() {
		$admin        = get_role( "administrator" );
		$agent_admin  = get_role( "ts_agent_admin" );
		$capabilities = array(
			'edit_others_posts'      => 'ts_edit_others_tickets',
			'delete_others_posts'    => 'ts_delete_others_tickets',
			'delete_private_posts'   => 'ts_delete_private_tickets',
			'edit_private_posts'     => 'ts_edit_private_tickets',
			'read_private_posts'     => 'ts_read_private_tickets',
			'edit_published_posts'   => 'ts_edit_published_tickets',
			'publish_posts'          => 'ts_publish_tickets',
			'delete_published_posts' => 'ts_delete_published_tickets',
			'edit_posts'             => 'ts_edit_tickets',
			'delete_posts'           => 'ts_delete_tickets',
			'edit_post'              => 'ts_edit_ticket',
			'read_post'              => 'ts_read_ticket',
			'delete_post'            => 'ts_delete_ticket',
			'manage_terms_sr'        => 'manage_sr',
			'edit_terms_sr'          => 'edit_sr',
			'delete_terms_sr'        => 'delete_sr',
			'manage_terms_tx'        => 'ts_manage_ticket_taxonomies',
			'edit_terms_tx'          => 'ts_edit_ticket_taxonomies',
			'delete_terms_tx'        => 'ts_delete_ticket_taxonomies',
			'assign_terms'           => 'ts_edit_ticket',
			'assign_agent'           => 'ts_assign_agent',
			'modify_priority'        => 'ts_modify_priority',

		);

		foreach ( $capabilities as $key => $value ) {
			$admin->add_cap( $value );
			$agent_admin->add_cap( $value );
		}
	}

	public static function add_agent_capabilities() {
		$agent        = get_role( "ts_agent" );
		$capabilities = array(
			'edit_others_posts'    => 'ts_edit_others_tickets',
			'edit_private_posts'   => 'ts_edit_private_tickets',
			'read_private_posts'   => 'ts_read_private_tickets',
			'edit_published_posts' => 'ts_edit_published_tickets',
			'edit_posts'           => 'ts_edit_tickets',
			'edit_post'            => 'ts_edit_ticket',
			'read_post'            => 'ts_read_ticket',
			'delete_post'          => 'ts_delete_ticket',
		);

		foreach ( $capabilities as $key => $value ) {
			$agent->add_cap( $value );
		}
		//		dd($agent->capabilities);
	}

	public static function reset_cap( $user_type ) {
		$subscriber = get_role( "subscriber" );
		$caps       = $user_type->capabilities;

		foreach ( $caps as $key => $value ) {
			$user_type->remove_cap( $key );
		}
		foreach ( $subscriber->capabilities as $c => $v ) {
			$user_type->add_cap( $c );
		}
	}

	public static function do_add_action() {
		add_action( 'admin_init', array( self::class, "add_admin_capabilities" ) );
		add_action( 'init', array( self::class, "add_agent_capabilities" ) );
		add_action( 'init', array( self::class, "add_roles" ) );
		add_action( 'init', array( self::class, 'insert_user_ticket' ) );
		add_action( 'init', array( self::class, 'insert_user_reply' ) );
		add_action( 'init', array( self::class, 'signup' ) );
		add_action( 'init', array( self::class, 'signin' ) );
	}

	/**
	 * get users that have admin side roles
	 *
	 * @return array
	 */
	public static function get_agents() {
		$roles = array(
			'administrator',
			'ts_agent_admin',
			'ts_agent',
		);

		return get_users( [ 'role__in' => $roles ] );
	}

	/**
	 * get users that doesn't have admin side roles
	 *
	 * @return array
	 */
	public static function get_others() {
		$roles = array(
			'administrator',
			'ts_agent_admin',
			'ts_agent',
		);

		return get_users( [ 'role__not_in' => $roles ] );
	}

	public static function get_user_role( $user_id ) {
		global $wp_roles;
		$roles = array();
		$user  = new \WP_User( $user_id );
		if ( ! empty( $user->roles ) && is_array( $user->roles ) ) {
			foreach ( $user->roles as $role ) {
				$roles[] .= translate_user_role( $wp_roles->roles[ $role ]['name'] );
			}
		}

		return implode( ', ', $roles );
	}

	public static function get_user_tickets_by_status( $user_id, $ticketstatus, $get_posts = false ) {
		$args                         = [
			"post_type"      => "ts_ticket",
			"post_status"    => "publish",
			"author"         => $user_id,
			"meta_query"     => array(
				"relation" => "AND",
				array(
					"key"     => TS_TICKET_STATUS,
					"value"   => $ticketstatus,
					"compare" => "=",

				),
			),
			"posts_per_page" => -1,
		];
		$ts_agent_tickets_count_query = new \WP_Query( $args );

		if ( ! $get_posts ) {
			return $ts_agent_tickets_count_query->found_posts;
		} else {
			return $ts_agent_tickets_count_query->posts;
		}

	}

	public static function get_agent_tickets_count_by_status( $agent_id, $ticketstatus ) {
		$args                         = [
			"posts_per_page" => -1,
			"post_type"      => "ts_ticket",
			"post_status"    => "publish",
			"meta_query"     => array(
				"relation" => "AND",
				array(
					"key"     => TS_TICKET_AGENT,
					"value"   => $agent_id,
					"compare" => "=",
				),
				array(
					"key"     => TS_TICKET_STATUS,
					"value"   => $ticketstatus,
					"compare" => "=",
				),
			),
		];
		$ts_agent_tickets_count_query = new \WP_Query( $args );

		return $ts_agent_tickets_count_query->found_posts;
	}


	public static function get_agent_tickets_count( $agent_id ) {

		$args = [
			"posts_per_page" => -1,
			"post_type"      => "ts_ticket",
			"post_status"    => "publish",
			"meta_query"     => array(
				"relation" => "AND",
				array(
					"key"     => TS_TICKET_AGENT,
					"value"   => $agent_id,
					"compare" => "=",
				),
			),
		];

		$ts_agent_tickets_count_query = new \WP_Query( $args );

		return $ts_agent_tickets_count_query->found_posts;

	}

	/**
	 * helps to check what is user role.
	 *
	 * @param $user_id
	 * @param $role
	 *
	 * @return bool
	 */
	public static function user_has_role( $user_id, $role ) {
		$user      = get_user_by( "ID", $user_id );
		$user_rols = $user->roles;
		if ( in_array( $role, $user_rols ) ) {
			return true;
		}

		return false;
	}


	/**
	 * check if new_ticket_by_user posted.
	 * if posted check for wp nonce and do new ticket insertion.
	 */
	public static function insert_user_ticket() {
		global $ts_form_errors;

		if ( ! isset( $_POST['new_ticket_by_user'] ) ) {
			return;
		}

		if ( ! in_array( "ts_customer", wp_get_current_user()->roles ) ) {
			wp_die( "no access" );
		}

		if ( ! wp_verify_nonce( $_POST["new_ticket_by_user"], "ts_user_new_ticket" ) ) {
			$error = new \WP_ERROR( 'ts_nonce_error', 'لطفا دوباره سعی کنید' );
			$ts_form_errors->add( $error->get_error_code(), $error->get_error_message() );
		}

		$ticket           = new TS_Ticket();
		$ticket->title    = $_POST['subject'];
		$ticket->content  = $_POST['ts_content'];
		$ticket->author   = get_current_user_id();
		$ticket->taxonomy = $_POST["category"];
		$ticket->priority = ( is_numeric( $_POST['priority'] ) && $_POST['priority'] <= count( TS_Ticket::$priorities ) ) ? $_POST['priority'] : 1;
		$ticket_id        = $ticket->insert();

		self::after_user_insert_post( $ticket, $ticket_id, get_post_type_archive_link( "ts_ticket" ) );

	}


	/**
	 * insert reply to a ticket from front user
	 */
	public static function insert_user_reply() {
		global $ts_form_errors, $wp;

		if ( ! isset( $_POST['ticket_reply_by_user'] ) ) {
			return;
		}

		if ( ! in_array( "ts_customer", wp_get_current_user()->roles ) ) {
			wp_die( "no access" );
		}

		if ( ! wp_verify_nonce( $_POST["ticket_reply_by_user"], "ticket_reply_by_user" ) ) {
			$error = new \WP_ERROR( 'ts_nonce_error', 'لطفا دوباره سعی کنید' );
			$ts_form_errors->add( $error->get_error_code(), $error->get_error_message() );
		}

		if ( ! self::user_can_modify_ticket( $_POST["ticket-id"] ) ) {
			return;
		}

		$reply = new TS_TicketReply();

		$reply->content = $_POST['ts_content'];
		$reply->parent  = $_POST['ticket-id'];
		$reply->author  = get_current_user_id();

		$reply_id = $reply->insert();

		self::after_user_insert_post( $reply, $reply_id, $_POST['_wp_http_referer'] );

	}

	/**
	 * fires after post inserted.
	 * check errors and upload attachment.
	 *
	 * @param TS_Post $post
	 * @param int     $post_id
	 */
	private static function after_user_insert_post( TS_Post $post, int $post_id, $redirect_url ) {
		$post->check_error();

		if ( 0 != $post_id ) {
			TS_Post::upload_attachment( $post_id );
			$post->check_error();
		}

		wp_safe_redirect( $redirect_url );
		exit();
	}

	public static function signin() {
		global $ts_form_errors;

		if ( ! isset( $_POST['ts_signin'] ) ) {
			return;
		}

		$login_page = $_POST['_wp_http_referer'];

		$form_check_result = ts_shortcode_check_nonce( "signin", $ts_form_errors );

		if ( is_wp_error( $form_check_result ) ) {
			$ts_form_errors->add( $form_check_result->get_error_code(), $form_check_result->get_error_message() );
		}


		if ( is_email( $_POST['email'] ) ) {
			$user_login_result = wp_authenticate_email_password( null, $_POST['email'], $_POST['password'] );
		} else {
			$user_login_result = wp_authenticate_username_password( null, $_POST['email'], $_POST['password'] );
		}

		if ( is_wp_error( $user_login_result ) ) {
			foreach ( $user_login_result->errors as $code => $message ) {
				$ts_form_errors->add( $code, $message[0] );
			}

			return;
		}


		wp_set_current_user( $user_login_result->ID, $user_login_result->display_name );

		wp_set_auth_cookie( $user_login_result->ID, $_POST['remember'] );

		return wp_safe_redirect( $login_page );

	}


	public static function signup() {

		global $ts_form_errors;
		if ( ! isset( $_POST['ts_signup'] ) ) {
			return;
		}

		$login_page = $_REQUEST['_wp_http_referer'];

		$form_check_result = ts_shortcode_check_nonce( "signup", $ts_form_errors );

		if ( is_wp_error( $form_check_result ) ) {
			$ts_form_errors->add( $form_check_result->get_error_code(), $form_check_result->get_error_message() );
		}

		$user                = new TS_User();
		$user_cration_result = $user->create_user( $_POST['fname'], $_POST['lname'], $_POST['username'], $_POST['email'], $_POST['password'] );

		if ( ! $user_cration_result ) {
			$error = $user->getError();
			$ts_form_errors->add( $error->get_error_code(), $error->get_error_message() );
		}

		if ( ! $ts_form_errors->has_errors() ) {
			wp_set_current_user( $user_cration_result, get_user_by( 'ID', $user_cration_result )->data->user_email );
			wp_set_auth_cookie( $user_cration_result );

			 wp_safe_redirect( $login_page . '?login' );
			 exit();
		}

		

	}

	/**
	 * @return \WP_Error.
	 */
	public function getError() {
		$error       = $this->error;
		$this->error = null;

		return $error;
	}


}