<style>
	.setup-desc{
		max-width: 800px;
	}
	<?php $defualt_page =  TicketingSystem::$options['ticket-submit-page'] ?>
</style>
<div class="bootstrap-wrapper" dir="rtl">

	<div class="setup-desc px-3 py-3 pt-md-5 pb-md-4 mx-auto text-justify rtl">
		<h1 class="display-4">انتخاب صفحه ارسال تیکت</h1>
		<p class="lead">
			:آدرس صفحه لیست تیکت های هر کاربر ( برای نمایش تیکت ها به کاربران )
			<?php echo get_post_type_archive_link("ts_ticket") ?>
		</p>

		<p class="lead">
			صفحه ای که میخواهید صفحه ارسال تیکت باشد را انتخاب کنید. لطفا دقت کنید که این صفحه باید حاوی کد کوتاه [ts_ticket_submit] باشد. در غیر اینصورت با اشکال مواجه می شوید
		</p>


		<p>		هنگام فعال سازی ما صفحه ای ساخته ایم که به صورت پیش فرض این صفحه انتخاب شده است. اما اگر صفحه دیگری دارید می توانید آنرا انتخاب کنید</p>

		<form action="<?php echo admin_url('admin-post.php'); ?>" method="post">
			<?php wp_nonce_field() ?>
			<input type="hidden" name="nextstep" value="3">
			<input type="hidden" name="action" value="save_submit_page">
			<?php wp_dropdown_pages(['id' => 'pages', 'name' => 'submit_page', 'selected' => $defualt_page ]); ?>
			<input type="submit" value="بعدی >" class="btn btn-primary float-left"">
		</form>
	</div>
	<script>
		jQuery("#pages").select2();
	</script>
</div>