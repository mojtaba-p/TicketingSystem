<?php
/**
 * Plugin Name:       Ticketing System
 * Version:           0.1.0
 * Author:            Mojtaba Pirhorilar
 * Description:       This plugin helps you to get better connection with your users.
 * Author URI:        m.pirhorilar@gmail.com
 * Text Domain:       ticketing-system
 */

// if this file directly called by user then die!!
defined( 'ABSPATH' ) or die( 'No Access.' );

final class TicketingSystem {

	private static $instance;

	public static $options;

	/**
	 * only one instance need not more!
	 *
	 * @return mixed
	 */
	public static function getInstance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new TicketingSystem();
		}

		return self::$instance;

	}

	/**
	 * TicketingSystem constructor.
	 */
	private function __construct() {
		$this->setup_consts();
		// enqueue js must be after plugins loaded
		$this->do_includes();
		self::$options = ts_ticket_get_options();
		TS_PostType::getInstance();
		TS_MetaBox::getInstance();
		TS_User::do_add_action();
		TS_SavedReply::getInstance();


		add_action( 'init', array( $this, 'do_enqueue' ) );
		add_action( 'init', array( TS_Ticket::class, 'toggle_ticket_status' ) );
		add_action( 'save_post_ts_ticket', array( TS_Ticket::class, 'after_save' ), 10, 3 );
		add_action( 'pre_get_posts', array( TS_TicketReply::class, "set_replies_order" ) );
		add_action( 'pre_get_posts', array( TS_Ticket::class, "set_ticket_author" ) );
		add_action( 'pre_get_posts', array( TS_Ticket::class, "set_ticket_posts_per_page" ) );
		add_action( 'content_edit_pre', array( TS_Ticket::class, "close_ticket_by_url" ), 10, 2 );
		add_action( 'content_edit_pre', array( TS_Ticket::class, "open_ticket_by_url" ), 10, 2 );

		$this->setup();
	}


	/**
	 * declaring main paths. (includes, assets, etc.)
	 */
	private function setup_consts() {

		define( 'TS_PATH', plugin_dir_path( __FILE__ ) );
		define( 'TS_URL', plugin_dir_url( __FILE__ ) );

		// php file paths
		define( 'TS_INC', trailingslashit( TS_PATH . 'includes' ) );

		// spell checking files folder
		define( 'TS_SPL', trailingslashit( TS_INC . 'spell' ) );


		// views path
		define( 'TS_VIEW', trailingslashit( TS_INC . 'views' ) );

		// class paths
		define( 'TS_CLASS', trailingslashit( TS_INC . 'classes' ) );
		define( 'TS_VENDOR', trailingslashit( TS_INC . 'vendor' ) );
		//		define( 'TS_CLASS_ADMIN', trailingslashit( TS_CLASS . 'admin' ) );

		// assets paths
		define( 'TS_ASSETS', trailingslashit( TS_URL . 'assets' ) );
		define( 'TS_IMG', trailingslashit( TS_ASSETS . 'img' ) );
		define( 'TS_CSS', trailingslashit( TS_ASSETS . 'css' ) );
		define( 'TS_JS', trailingslashit( TS_ASSETS . 'js' ) );

		// system variables
		define( "TS_TICKET_PRIORITY", "_ts_priority" );
		define( "TS_TICKET_AGENT", "_ts_agent" );
		define( "TS_TICKET_STATUS", "_ts_status" );

	}

	/**
	 * importing necessary classes and functions.
	 */
	private function do_includes() {
		include( TS_INC . 'ts-config.php' );
		include( TS_INC . 'helpers.php' );

		try {
			spl_autoload_register( array( $this, 'autoload' ) );
		} catch ( Exception $e ) {
			die( $e->getMessage() );
		}

		include( TS_INC . 'shortcode-generator.php' );
		include( TS_INC . 'phpspreadshit-generator.php' );
		include( TS_INC . 'functions-setting.php' );
		include( TS_INC . 'functions-report.php' );
		include( TS_SPL . 'functions-spell.php' );
		include( TS_INC . 'install.php' );

	}

	/**
	 * include classes that exists in class folder.
	 *
	 * @param $class_name
	 */
	public function autoload( $class_name ) {
		$class_name = str_replace( 'TicketingSystem', '', $class_name );

		if ( file_exists( TS_CLASS . $class_name . '.php' ) ) {
			include( TS_CLASS . $class_name . '.php' );
		}
	}

	public function do_enqueue() {
		//		dd(wp_get_current_user());
		if ( is_admin() ) {
			wp_enqueue_style( 'bootstrap', TS_CSS . 'bootstrap-wrapper.css' );
			wp_enqueue_style( 'ticket-css', TS_CSS . 'ticket-admin-post.css', array() );
			wp_enqueue_script( 'post-ticket', TS_JS . 'post-ticket.js', '', '2', true );
			// wp_enqueue_script( 'bootstrap-hack', TS_JS . 'bootstrap-hack.js', '', '1', true );
			wp_enqueue_script( 'select2', TS_JS . 's2.min.js', '', '4', true );
			wp_enqueue_style( 'select2', TS_CSS . 's2.min.css', array() );
		}

		// enqueue after others
		add_action( 'wp_enqueue_scripts', array( $this, "do_front_enqueue" ), PHP_INT_MAX );
	}

	/**
	 * loads front side assets.
	 */
	public function do_front_enqueue() {
		wp_enqueue_style( 'ts_ticket', TS_CSS . 'ts-plugin.css' );
		wp_enqueue_script( 'ts_ticket', TS_JS . 'ts-plugin.js', array( 'jquery' ), '2', true );
	}

	public static function reload_options(){
		self::$options = ts_ticket_get_options();
	}

	private function setup(  ) {
		$is_setted = get_option('wpts_is_setup', 0);

		if ( $is_setted ){
			return 0;
		}
		TicketingSystem::$options['ticket-list-page'] = get_bloginfo('url').'/ts_ticket';
		add_action( 'admin_notices', 'wpts_ask_setup_wizard', 1 );
	}
}

/**
 * Main Method Fire Plugin.
 *
 * @return mixed
 */
function main() {
	return TicketingSystem::getInstance();
}

register_activation_hook( __FILE__, 'wpts_activate_ticketing_system' );
function wpts_activate_ticketing_system() {
	TS_User::add_roles();
	flush_rewrite_rules();
}

register_deactivation_hook( __FILE__, 'wpts_deactivate_ticketing_system' );
function wpts_deactivate_ticketing_system() {
	TS_User::remove_roles();
}

// Start Plugin.
main();

