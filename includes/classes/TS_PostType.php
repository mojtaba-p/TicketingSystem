<?php
// if this file directly called by user then die!!
defined( 'ABSPATH' ) or die( 'No Access.' );

/**
 * Class PostTypes
 * create and manage post types.
 */
class TS_PostType {

	private static $instance;

	private function __construct() {

		$this->do_add_actions();

	}

	/**
	 * execute add_action function for post types.
	 */
	private function do_add_actions() {

		add_action( 'init', array( $this, 'create_ticket_post_type' ) );
		add_action( 'init', array( $this, 'create_ticket_reply_post_type' ) );
		add_action( 'init', array( $this, 'create_ticket_history_post_type' ) );
		add_action( 'init', array( $this, 'create_ticket_note_post_type' ) );
		add_action( 'init', array( $this, 'register_ts_ticket_taxonomy' ) );


		add_action( 'restrict_manage_posts', array( $this, 'display_tickets_filter' ), 10, 2 );
		add_action( 'manage_posts_extra_tablenav', array( $this, 'display_tickets_export' ), 10, 1 );

		add_action( 'single_template', array( $this, 'set_ts_ticket_post_template' ) );
		add_action( 'archive_template', array( $this, 'set_ts_ticket_archive_template' ) );

		add_action( 'manage_ts_ticket_posts_columns', array( $this, 'modify_ts_ticket_post_type_columns' ), 10 );
		add_action( 'manage_ts_ticket_posts_custom_column', array(
			$this,
			'set_ts_ticket_post_type_columns_data',
		), 10, 2 );

		add_filter( 'views_edit-ts_ticket', array( $this, "edit_subsubsub" ) );
		add_filter( 'post_row_actions', array( $this, 'edit_ticket_actions' ), 10, 2 );

		add_filter( 'manage_edit-ts_ticket_sortable_columns', array(
			$this,
			'modify_ts_ticket_sortable_columns',
		) );

		add_filter( 'pre_get_posts', array( $this, 'filter_ts_ticket_columns' ) );
		add_filter( 'pre_get_posts', array( $this, 'get_current_user_tickets' ) );

		add_action( 'post_edit_form_tag', array( $this, 'post_edit_form_tag' ) );
		add_action( 'admin_menu', array( $this, 'add_pending_count' ) );

		add_filter( "disable_months_dropdown", array( $this, "disable_default_month_dropdown" ), 10, 2 );
	}

	/**
	 * singleton pattern implementation.
	 *
	 * @return mixed
	 */
	public static function getInstance(): TS_PostType {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new TS_PostType();
		}

		return self::$instance;
	}

	/**
	 * generate subsubsub links that was in top of tickets lists
	 *
	 * @param $views
	 *
	 * @return mixed
	 */
	public static function edit_subsubsub( $views ) {
		global $wp_query;

		// delete publish item from list
		unset( $views['publish'] );

		// declare new items
		$view_items = array(
			'open'     => array( "status" => "open" ),
			'closed'   => array( "status" => "closed" ),
			'answered' => array( "status" => "answered" ),
			'mine'     => array( "agent" => get_current_user_id() ),
		);

		// declare translations of items
		$t = array(
			'open'     => "تیکت های باز",
			'closed'   => "تیکت های بسته",
			'answered' => "تیکت های پاسخ داده شده",
			'mine'     => "تیکت های من",
		);

		// check if user cant see all agents tickets then remove mine and all items
		// because agents can only see tickets that assigned to them
		if ( ! current_user_can( "ts_assign_agent" ) ) {
			unset( $view_items['mine'] );
			unset( $views['all'] );
			unset( $views['trash'] );
		}

		foreach ( $view_items as $name => $val ) {
			// add post_type query to url
			$link = array( "post_type" => "ts_ticket" );
			array_push( $link, $val );
			// create url
			$link  = add_query_arg( $link, admin_url( "edit.php" ) );
			$class = '';
			// check current page to specify which item belong to this page
			if ( isset( $_GET['status'] ) ) {
				if ( $_GET['status'] == $name ) {
					$class = 'class = "current" ';
				}
			}
			// check if user is admin get only admin tickets count for mine item number
			if ( $name == 'mine' ) {
				$count = TS_User::get_agent_tickets_count( get_current_user_id() );
				if ( isset($_GET['agent']) && $_GET['agent'] == get_current_user_id() ) {
					$class = 'class = "current"';
				}
			} else {

				if ( current_user_can( "ts_assign_agent" ) ) {
					$count = TS_Ticket::get_tickets_count_by_status( $name );
				} else {
					// count only tickets that assigned to this agent.
					$count = TS_User::get_agent_tickets_count_by_status( get_current_user_id(), $name );
				}
			}

			$views[ $name ] = sprintf( '<a href="%1$s" %2$s>%3$s <span class="count">(%4$s)</span></a>', $link, $class,
				$t[ $name ], $count );

		}

		return $views;

	}

	/**
	 * @return array
	 */
	public static function get_all_ts_ticket_terms(): array {

		$terms = get_terms( array(
			'taxonomy'   => 'ticket_type',
			'hide_empty' => false,
		) );

		return $terms;
	}

	/**
	 * create ticket post type.
	 *
	 * @register post type
	 */
	public function create_ticket_post_type(): void {
		$supports = array( 'title' );
		if ( ! isset( $_GET['post'] ) ) {
			array_push( $supports, 'editor' );
		}

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
		);

		$args = array(
			'labels'             => array(
				'name'           => 'تیکت ها',
				'singular_name'  => 'تیکت',
				'menu_name'      => 'تیکت ها',
				'name_admin_bar' => 'تیکت',
				'add_new'        => 'تیکت جدید',
				'add_new_item'   => 'افزودن تیکت',
				'new_item'       => 'تیکت جدید',
				'edit_item'      => 'ویرایش تیکت',
				'view_item'      => 'نمایش تیکت',
				'all_items'      => 'تمامی تیکت ها',
			),
			'public'             => false,
			'publicly_queryable' => true,
			'show_ui'            => true,
			'show_in_menu'       => true,
			'query_var'          => true,
			'rewrite'            => array( 'slug' => 'ts_ticket' ),
			'has_archive'        => true,
			'capabilities'       => $capabilities,
			'map_meta_cap'       => true,
			'hierarchical'       => false,
			'menu_position'      => null,
			'supports'           => $supports,
			'menu_icon'          => 'dashicons-tickets-alt',
		);

		register_post_type( 'ts_ticket', $args );
	}

	/**
	 * create ticket reply post type.
	 *
	 * @register post type
	 */
	public function create_ticket_reply_post_type(): void {

		$args = [
			'labels'              => array(
				'name'           => 'پاسخ ها',
				'singular_name'  => 'پاسخ',
				'menu_name'      => 'پاسخ ها',
				'name_admin_bar' => 'پاسخ',
				'add_new'        => 'پاسخ جدید',
				'add_new_item'   => 'افزودن پاسخ',
				'new_item'       => 'پاسخ جدید',
				'edit_item'      => 'ویرایش پاسخ',
				'view_item'      => 'نمایش پاسخ',
				'all_items'      => 'تمامی پاسخ ها',
			),
			'public'              => false,
			'exclude_from_search' => true,
			'supports'            => array( 'editor' ),
			'capability_type'     => 'ts_ticket_reply',
		];

		register_post_type( 'ts_ticket_reply', $args );

	}

	/**
	 * create ticket history post type
	 * for log each event.
	 *
	 * @register post type
	 */
	public function create_ticket_history_post_type(): void {

		$args = [
			'labels'              => array(
				'name'           => 'رویداد ها',
				'singular_name'  => 'رویداد',
				'menu_name'      => 'رویداد ها',
				'name_admin_bar' => 'رویداد',
				'add_new'        => 'رویداد جدید',
				'add_new_item'   => 'افزودن رویداد',
				'new_item'       => 'رویداد جدید',
				'edit_item'      => 'ویرایش رویداد',
				'view_item'      => 'نمایش رویداد',
				'all_items'      => 'تمامی رویداد ها',
			),
			'public'              => false,
			'exclude_from_search' => true,
			'supports'            => array(),
		];

		register_post_type( 'ts_ticket_history', $args );

	}

	/**
	 * create ticket note post type.
	 */
	public function create_ticket_note_post_type(): void {

		$args = [
			'labels'              => array(
				'name'           => 'یادداشت ها',
				'singular_name'  => 'یادداشت',
				'menu_name'      => 'یادداشت ها',
				'name_admin_bar' => 'یادداشت',
				'add_new'        => 'یادداشت جدید',
				'add_new_item'   => 'افزودن یادداشت',
				'new_item'       => 'یادداشت جدید',
				'edit_item'      => 'ویرایش یادداشت',
				'view_item'      => 'نمایش یادداشت',
				'all_items'      => 'تمامی یادداشت ها',
			),
			'public'              => false,
			'exclude_from_search' => true,
			'supports'            => array(),
		];

		register_post_type( 'ts_ticket_note', $args );

	}

	/**
	 * modify posts list columns title
	 *
	 * @param $defaults
	 *
	 * @return array columns title
	 */

	public function modify_ts_ticket_post_type_columns( $defaults ): array {

		$date                 = array_pop( $defaults );
		$defaults['ID']       = __( "شناسه" );
		$defaults['author']   = __( "ارسال کننده" );
		$defaults['status']   = __( "وضعیت" );
		$defaults['priority'] = __( "اولویت" );
		$defaults['agent']    = __( "پشتیبان" );
		$defaults['date']     = $date;

		return $defaults;

	}

	/**
	 * set each column data
	 */
	public function set_ts_ticket_post_type_columns_data( $column, $post_id ) {

		switch ( $column ) {

			case "ID":
				echo $post_id;
				break;

			case "status":
				$status_text = self::ts_get_post_status( $post_id );
				$status      = TS_Ticket::get_ticket_status( $post_id );
				echo "<a href=\"" . admin_url( "edit.php?post_type=ts_ticket&status=" . $status ) . "\">" . $status_text . "</a>";;
				break;

			case "agent":
				$agent      = get_user_by( 'ID', get_post_meta( $post_id, TS_TICKET_AGENT, true ) );
				$agent_name = isset( $agent->display_name ) ? $agent->display_name : $agent->ID;
				echo "<a href=\"" . admin_url( "edit.php?post_type=ts_ticket&agent=" . $agent->ID ) . "\">" . $agent_name . "</a>";
				break;

			case "priority":
				$ticket_priority = TS_Ticket::get_ticket_priority( $post_id );
				$priority_index  = null != $ticket_priority ? $ticket_priority : 1;
				$priority_name   = TS_Ticket::get_ticket_priority( $post_id, "text" );
				echo "<a href=\"" . admin_url( "edit.php?post_type=ts_ticket&priority=" . $priority_index ) . "\">" . $priority_name . "</a>";
				break;

		}

	}

	/**
	 * give post status in persian.
	 *
	 * @param $post_id
	 *
	 * @return string
	 */
	public static function ts_get_post_status( $post_id ) {
		global $wpdb;
		$post_status = TS_Ticket::get_ticket_status( $post_id, "text" );

		return $post_status;
	}

	/**
	 * specify which columns are sortable
	 *
	 * @param $columns
	 *
	 * @return mixed
	 */
	public function modify_ts_ticket_sortable_columns( $columns ) {

		$columns['ID']       = "ID";
		$columns['status']   = "status";
		$columns['agent']    = "agent";
		$columns['priority'] = "priority";

		return $columns;

	}

	/**
	 * filter tickets in admin dashboard
	 *
	 * @param $query
	 */
	public function filter_ts_ticket_columns( $query ) {


		if ( ! is_admin() ) {
			return;
		}

		// don't modify other queries
		if ( ! $query->is_main_query() ) {
			return;
		}

		$keys = array_keys( $_GET );

		// if code isset only filter ticket
		if ( isset( $_GET['code'] ) && ! empty( $_GET['code'] ) ) {
			$query->set( 'p', $_GET['code'] );

			return $query;
		}

		if ( isset( $_GET['user'] ) && ! empty( $_GET['user'] ) ) {
			$query->set( "author__in", [ $_GET['user'] ] );

		}

		if ( isset( $_GET["status"] ) || isset( $_GET["priority"] ) || isset( $_GET["agent"] ) ) {
			$meta_query = [ "relation" => "AND" ];
			foreach ( $keys as $key ) {

				if ( empty( $_GET[ $key ] ) ) {
					continue;
				}

				if ( ! in_array( $key, array( "status", "priority", "agent" ) ) ) {
					continue;
				}

				$value = $_GET["$key"];

				$meta_query[] = array(
					"key"     => "_ts_" . $key,
					"value"   => $value,
					"compare" => "=",
				);


				$query->set( "meta_query", $meta_query );
			}
		}


		return $query;
	}

	/**
	 *
	 */
	public function get_current_user_tickets( $query ) {

		// if not called in admin side.
		if ( ! is_admin() ) {
			return;
		}

		// don't modify other queries.
		if ( ! $query->is_main_query() ) {
			return;
		}

		$user_is_administrator = TS_User::user_has_role( get_current_user_id(), "administrator" );
		$user_is_agent_admin   = TS_User::user_has_role( get_current_user_id(), "ts_agent_admin" );
		// if current user is administrator or agent_admin show all tickets.
		if ( $user_is_administrator || $user_is_agent_admin ) {
			return;
		}

		$meta_query = $query->get( 'meta_query' );

		if ( is_array( $meta_query ) ) {
			$meta_query[] = [
				"relation" => "AND",
				array(
					"key"     => "_ts_agent",
					"value"   => get_current_user_id(),
					"compare" => "=",
				),
			];
		} else {
			$meta_query = [
				"relation" => "AND",
				array(
					"key"     => "_ts_agent",
					"value"   => get_current_user_id(),
					"compare" => "=",
				),
			];
		}
		$query->set( 'meta_query', $meta_query );

		return $query;
	}

	/**
	 *
	 */
	public function register_ts_ticket_taxonomy(): void {

		$args = array(
			'hierarchical'      => true,
			'show_ui'           => true,
			'show_admin_column' => true,
			'query_var'         => true,
			'capabilities'      => array(
				'manage_terms' => 'ts_manage_ticket_taxonomies',
				'edit_terms'   => 'ts_edit_ticket_taxonomies',
				'delete_terms' => 'ts_delete_ticket_taxonomies',
				'assign_terms' => 'ts_edit_ticket',
			),
		);

		register_taxonomy( "ticket_type", array( "ts_ticket" ), $args );

	}

	/**
	 * set template for ts ticket post to show custom data.
	 *
	 * @param $template
	 *
	 * @return string
	 */
	public function set_ts_ticket_post_template( $template ) {

		global $post, $ts_form_errors;

		if ( $post->post_type != "ts_ticket" ) {
			return $template;
		}

		if ( ! is_user_logged_in() ) {
			$ts_form_errors->add( "login_error", "دسترسی به این صفحه با ثبت نام امکان پذیر است" );

			return TS_VIEW . "front/login-template.php";
		}

		if ( ! TS_User::user_can_modify_ticket( $post->ID ) ) {
			$ts_form_errors->add( "permission_error", "دسترسی به این صفحه غیرمجاز است" );

			return TS_VIEW . "front/error-template.php";
		}

		return TS_VIEW . "front/show-ticket.php";

	}

	/**
	 * set template for ts ticket post to show custom data.
	 *
	 * @param $template
	 *
	 * @return string
	 */
	public function set_ts_ticket_archive_template( $template ) {

		global $post, $ts_form_errors;

		if ( ! is_post_type_archive( "ts_ticket" ) ) {
			return $template;
		}

		// if user not logged in
		if ( ! is_user_logged_in() ) {
			$ts_form_errors->add( "login_error", "دسترسی به این صفحه با ثبت نام امکان پذیر است" );

			return TS_VIEW . "front/login-template.php";
		}

		return TS_VIEW . "front/tickets-list.php";
		//		if ( current_user_can( "ts_read_ticket" ) ) {
		//		} else {
		//			$ts_form_errors->add( "access_error", "دسترسی به این صفحه برای شما امکان پذیر نیست" );
		//
		//			return TS_VIEW . "front/error-template.php";
		//		}
		// TODO:fix capability error
	}

	public function post_edit_form_tag() {
		echo ' enctype="multipart/form-data" ';
	}

	/**
	 * add number of open tickets to menu cpt menu
	 */
	public function add_pending_count() {
		// TODO: check to show count or no
		global $current_user, $menu, $wpdb;
		foreach ( $menu as $key => $value ) {
			if ( $value[2] != "edit.php?post_type=ts_ticket" ) {
				continue;
			}
			$ts_ticket_menu_index = $key;
		}

		$pending_tickets_count = TS_User::get_agent_tickets_count_by_status( $current_user->ID, "open" );
		$count                 = '';
		if ( $pending_tickets_count > 0 ) {
			$count = "<span class='bootstrap-wrapper'><span class=\"badge badge-warning mx-2\">{$pending_tickets_count}</span></span>";
		}

		$menu[ $ts_ticket_menu_index ][0] = $menu[ $ts_ticket_menu_index ][0] . " " . $count;
	}

	/**
	 * edit post actions displayed under post title in admin screen
	 *
	 * @param $actions
	 * @param $post
	 *
	 * @return mixed
	 */
	public function edit_ticket_actions( $actions, $post ) {
		// don't touch other posts
		if ( $post->post_type != "ts_ticket" ) {
			return $actions;
		}

		$actions['edit'] = str_replace( __( 'Edit' ), __( "View" ), $actions['edit'] );
		unset( $actions['view'] );
		unset( $actions['inline hide-if-no-js'] );

		return $actions;
	}

	public function display_tickets_filter( $post_type, $which ) {

		if ( "ts_ticket" != $post_type ) {
			return;
		}

		$agents     = TS_User::get_agents();
		$users      = TS_User::get_others();
		$statuses   = TS_Ticket::$statuses;
		$priorities = TS_Ticket::$priorities;

		require_once TS_VIEW . "ticket-filters.php";
	}

	public static function display_tickets_export( $which ) {
		global $post_type, $wp;
		if ( $post_type != "ts_ticket" ) {
			return $which;
		}
		//		$_SERVER['HTTP_HOST'].
		$current_page = $_SERVER['REQUEST_URI'];
		require_once TS_VIEW . "ticket-export.php";
	}

	public function disable_default_month_dropdown( $disabled = false, $post_type ) {
		if ( "ts_ticket" != $post_type ) {
			return $disabled;
		}

		// check wp-persian plugin is active
		$wpp_dir = "wp-persian/wp-persian.php";
		if ( ! is_plugin_active( $wpp_dir ) ) {
			return $disabled;
		}

		$disabled = true;

		return $disabled;

	}


}