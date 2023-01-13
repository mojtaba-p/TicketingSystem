<?php require TS_VIEW . "front/errors.php"; ?>
<div class="container">
    <div class="row">
        <h4 class="text-center" id="title">لطفا برای استفاده از سیستم تیکتینگ ثبت نام کنید</h4>
        <hr>
    </div>
    <p class="text-center">

        <small id="passwordHelpInline" class="text-muted"> اگرقبلا ثبت نام کرده اید وارد شوید</small>
    </p>
    <div class="row text-right">
		<?php if ( TicketingSystem::$options['user-can-register'] ): ?>
            <div class="col-md-6">
                <form role="form" method="post">
					<?php wp_nonce_field( 'signup', 'ts_signup' ); ?>
                    <fieldset>
                        <p class="text-uppercase pull-center"> ثبت نام </p>
                        <div class="form-group">
                            <input type="text" name="fname" id="fname" class="form-control input-lg" placeholder="نام"
                                   required>
                        </div>

                        <div class="form-group">
                            <input type="text" name="lname" id="email" class="form-control input-lg"
                                   placeholder="نام خانوادگی"
                                   required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="username" id="username" class="form-control input-lg"
                                   placeholder="نام کاربری"
                                   required>
                        </div>
                        <div class="form-group">
                            <input type="email" name="email" id="email" class="form-control input-lg"
                                   placeholder="ایمیل"
                                   required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="password" id="password" class="form-control input-lg"
                                   placeholder="رمز عبور" required>
                        </div>

                        <div>

                            <input type="submit" class="btn btn-lg btn-default" style="font-size: 12pt" value="ثبت نام">
                        </div>
                    </fieldset>
                </form>
            </div>
		<?php endif ?>

        <div class="col-md-6">
            <form role="form" method="post" action="<?php echo get_post_type_archive_link( "ts_ticket" ) ?>">
				<?php wp_nonce_field( 'signin', 'ts_signin' ); ?>
                <input type="hidden" name="action" value="signin">
                <fieldset>
                    <p class="text-uppercase">ورود به حساب کاربری </p>

                    <div class="form-group">
                        <input type="text" name="email" id="email" class="form-control input-lg"
                               placeholder="ایمیل و یا نام کاربری" required>
                    </div>
                    <div class="form-group">
                        <input type="password" name="password" id="password" class="form-control input-lg"
                               placeholder="رمز عبور" required>
                    </div>
                    <div>
                        <input type="checkbox" id="remember" name="remember">
                        <label for="remember">مرا به خاطر بسپار</label>
                    </div>
                    <br>
                    <div>
                        <input type="submit" class="btn btn-md btn-primary" style="font-size: 12pt"
                               value="ورود به حساب">
                    </div>

                </fieldset>
            </form>
        </div>

    </div>
