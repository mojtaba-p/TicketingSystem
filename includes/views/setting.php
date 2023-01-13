<?php
$agents = TS_User::get_agents();
?>
<div class="wrap ts-setting">
    <h1 class="wp-heading-inline">تنظیمات</h1>

	<?php /**
	 * <nav class="nav-tab-wrapper wp-clearfix" aria-label="فهرست ثانویه">
	 * <a href="http://localhost:8080/ticketing-system/wp-admin/setting-items.php" class="nav-tab nav-tab-active"
	 * aria-current="page">ویرایش فهرست‌ها</a>
	 * <a href="http://localhost:8080/ticketing-system/wp-admin/setting-items.php?action=locations" class="nav-tab">مدیریت
	 * جایگاه‌ها</a>
	 * </nav>
	 */
	?>
    <form method="post" action="<?php echo $action_url; ?>" novalidate="novalidate">
		<?php wp_nonce_field( "ts_setting_page", "ts_setting_page_nonce" ); ?>
        <hr>
        <br>
        <div class="menu-edit ">
            <div id="setting-item-header">
                <div class="major-publishing-actions wp-clearfix">
                    <h3>تنظیمات عمومی</h3>

                </div><!-- END .major-publishing-actions -->
            </div><!-- END .setting-item-header -->
            <div id="post-body">
                <div id="post-body-content" class="wp-clearfix">
                    <table class="form-table">
                        <tbody>
                        <tr>
                            <th scope="row"><label for="default-agent">پشتیبان پیش فرض</label></th>
                            <td>
                                <select id="default-agent" name="default-agent">
									<?php foreach ( $agents as $agent ): ?>
                                        <option value="<?php echo $agent->ID ?>" <?php selected( $current_options["default-agent"], $agent->ID ) ?>><?php echo $agent->display_name ?></option>
									<?php endforeach; ?>
                                </select>
                                <p class="description" id="default_agent-description">
                                    کاربری که به صورت پیش فرض تیکت ها به او انتصاب می شوند را انتخاب کنید
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="ticket-submit-page">صفحه ارسال تیکت</label></th>
                            <td>
								<?php wp_dropdown_pages( [ "name"     => "ticket-submit-page",
								                           "selected" => $current_options["ticket-submit-page"]
								] ); ?>
                                <p class="description" id="submit-page-description">
                                    صفحه ای که کاربر برای ارسال تیکت باید به آن مراجعه کند را انتخاب کنید. در نظر داشته
                                    باشید که شورت کد [ts_ticket_submit] باید در آن صفحه وجود داشته باشد
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="tickets-per-page">تعداد تیکت ها در هر صفحه</label></th>
                            <td>
                                <input type="number" id="tickets-per-page" name="tickets-per-page"
                                       value="<?php echo $current_options['tickets-per-page'] ?>">
                                <p class="description" id="tickets-per-page-description">
                                    در هر صفحه لیست تیکت ها چند تیکت نمایش داده شود؟
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="reply-order">ترتیب چینش مکالمات</label></th>
                            <td>
                                <input type="radio" id="replies-order-asc" value="asc"
                                       name="replies-order" <?php checked( $current_options["replies-order"], "ASC" ) ?> >
                                <label
                                        for="replies-order-asc">قدیم به جدید</label>
                                <br>
                                <input type="radio" id="replies-order-desc" value="desc"
                                       name="replies-order" <?php checked( $current_options["replies-order"], "DESC" ) ?> ><label
                                        for="replies-order-desc">جدید به قدیم</label>
                                <p class="description" id="reply-order-description">
                                    نحوه نمایش مکالمات پشتیبان با کاربر از نظر زمانی
                                </p>
                            </td>
                        </tr>

                        <tr>
                            <th scope="row"><label for="user-can-register">اجازه ثبت نام</label></th>
                            <td>
                                <input type="radio" id="user-can-register" value="yes"
                                       name="user-can-register" <?php checked( $current_options["user-can-register"], 1 ) ?> >
                                <label
                                        for="user-can-register">بله</label>
                                <br>
                                <input type="radio" id="user-cant-register" value="no"
                                       name="user-can-register" <?php checked( $current_options["user-can-register"], 0 ) ?> ><label
                                        for="user-cant-register">خیر</label>
                                <p class="description" id="user-registration-description">
                                    صفحه ثبت نام به کاربران تازه نمایش داده شود یا خیر
                                </p>
                            </td>
                        </tr>

                        </tbody>
                    </table>
                </div><!-- /#post-body-content -->
            </div><!-- /#post-body -->
            <div id="setting-item-footer">
                <div class="major-publishing-actions wp-clearfix">
                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary"
                               value="ذخیرهٔ تغییرات">
                    </p>
    </form>
    <!-- END .publishing-action -->
</div><!-- END .major-publishing-actions -->
</div><!-- /#setting-item-footer -->
</div>
</div>