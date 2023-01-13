<?php
function wpts_ask_setup_wizard() {

	if ( isset($_GET['page']) && $_GET['page'] == 'wpts_setup' ) {
		return 0;
	}
	if ( is_super_admin() && !get_option('wpts_is_setup') ) {
		?>
		<div class="updated">
			<p>
				از اینکه ts ticket را انتخاب کردید ممنونیم. لطفا برای انجام پروسه نصب
				<a href=" <?php echo admin_url( 'index.php?page=wpts_setup' ); ?>" class="button button-primary">کلیک کنید</a>
			</p>
		</div>
		<?php
	}
}

add_action( 'admin_menu', 'wpts_add_setup_page' );
function wpts_show_install_page() {

	$step = $_GET['step'] ?? 1;
	
	if($step == 4){
		update_option('wpts_is_setup', 1);
	}

	if ( 5 > $step && $step > 0 ) {
		require_once TS_VIEW . "setup/step-" . $step . ".php";
	}
}

function wpts_add_setup_page() {
	add_dashboard_page( '', '', 'read', 'wpts_setup', 'wpts_show_install_page' );
}

add_action( "admin_post_save_ticket_menu", "wpts_install_save_ticket_menu" );
function wpts_install_save_ticket_menu() {

	$menu = $_POST['wpts_ticket_menu'];
	$post = get_post( TicketingSystem::$options['ticket-submit-page'] ); // etc

	wp_update_nav_menu_item( $menu, 0, array(
		'menu-item-title'     => 'ثبت تیکت',
		'menu-item-object-id' => $post->ID,
		'menu-item-object'    => 'page',
		'menu-item-status'    => 'publish',
		'menu-item-type'      => 'post_type',
	) );

	wp_update_nav_menu_item( $menu, 0, array(
		'menu-item-title'  => 'لیست تیکت ها',
		'menu-item-url'    => TicketingSystem::$options['ticket-list-page'],
		'menu-item-status' => 'publish',
		'menu-item-type'   => 'custom', // optional
	) );

	wp_safe_redirect( admin_url( 'index.php?page=wpts_setup&step=4' ) );

}

add_action( "admin_post_save_submit_page", "wpts_install_save_submit_page" );
function wpts_install_save_submit_page() {
	$submit_page                                    = $_POST['submit_page'];
	TicketingSystem::$options['ticket-submit-page'] = $submit_page;
	ts_ticket_save_options( TicketingSystem::$options );
	wp_safe_redirect( admin_url( 'index.php?page=wpts_setup&step=3' ) );
	exit();
}

add_action( 'wp_loaded', 'wpts_add_submit_page');
function wpts_add_submit_page() {
	TicketingSystem::reload_options();
	if ( TicketingSystem::$options['ticket-submit-page'] > 0 ) {
		return 0;
	}

	$submit_page = wp_insert_post( [
		"post_title"   => "ایجاد تیکت",
		"post_status"  => "publish",
		"post_type"    => "page",
		"post_name"    => "submit-page",
		"post_content" => "[ts_ticket_submit]",
	] );

	if ( is_wp_error( $submit_page ) ) {
		return 0;
	}
	TicketingSystem::$options['ticket-submit-page'] = $submit_page;
	ts_ticket_save_options(TicketingSystem::$options);
}