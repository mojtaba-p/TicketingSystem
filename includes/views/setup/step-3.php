<style>
	.setup-desc{
		max-width: 800px;
	}
	<?php $defualt_page =  TicketingSystem::$options['ticket-submit-page'] ?>
</style>
<div class="bootstrap-wrapper">

	<div class="setup-desc px-3 py-3 pt-md-5 pb-md-4 mx-auto text-justify">
		<h1 class="display-4">انتخاب منو</h1>
		<form method="post" action="<?php echo admin_url("admin-post.php"); ?>">
			<?php wp_nonce_field() ?>
			<input type="hidden" name="nextstep" value="4">
			<input type="hidden" name="action" value="save_ticket_menu">
			<p class="lead">انتخاب منو برای درج لینک های ثبت تیکت و لیست تیکت</p>
			<p class="lead">در مرحله قبل شما یک صفحه برای ارسال تیکت انتخاب کردید و یک آدرس صفحه لیست تیکت هم به تایید شما رسید لطفا منویی جهت درج لینک این دو صفحه انتخاب کنید</p>

			<?php $menu_lists = wp_get_nav_menus();
			if( !empty( $menu_lists )){ ?>
				<select name="wpts_ticket_menu">
				<?php foreach ($menu_lists as $key => $menu ) { ?>
					<option value="'.$menu->term_id.'"><?=$menu->name ?></option>
				<?php } ?>
				<select>
				<input type="submit" value="بعدی >" class="btn btn-primary float-left">
			<?php } else { ?>
				بنظر می رسد که تا کنون منویی ساخته نشده است یک منو بسازید و برگردید. 
				<a href="<?=admin_url( 'nav-menus.php') ?>" class="btn btn-primary" target="_blank">ساخت یک منو</a>
				<a class="btn btn-secondary float-left" href='<?=admin_url( 'index.php?page=wpts_setup&step=4' ) ?>' >رد کردن</a>
			<?php } ?>
		</form>
	</div>
	<script>
		jQuery("#pages").select2();
	</script>
</div>