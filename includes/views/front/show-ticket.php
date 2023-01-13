<?php get_header();
wp_head(); ?>
<?php require TS_VIEW . "front/errors.php"; ?>

<div class="container text-right">
    <div class="row">
        <div class="col-md-4">
            <ul class="ticket-options">
                <li>
                    <a href="<?= get_post_type_archive_link( "ts_ticket" ); ?>" class="ticket-option-item">
                        <span class="glyphicon glyphicon-align-left"></span>
                        لیست تیکت ها
                    </a>
                </li>
                <li>
                    <a href="<?= get_the_guid(TicketingSystem::$options['ticket-submit-page']); ?> " class="ticket-option-item">
                        ایجاد تیکت جدید
                    </a>
                </li>
                <li>
                    <form action="<?= home_url( $wp->request ) ?>" method="post" enctype="multipart/form-data">
						<?php wp_nonce_field( "ts_toggle_ticket", "ts_toggle_ticket" ) ?>
                        <input type="hidden" value="<?= get_the_ID() ?>" name="ticket-id">
						<?php if ( TS_Ticket::get_ticket_status( get_the_ID() ) != "closed" ) : ?>
                            <input type="submit" value="بستن تیکت" class="ticket-option-item">
						<?php else: ?>
                            <input type="submit" value="بازکردن تیکت" class="ticket-option-item">
						<?php endif ?>
                    </form>
                </li>
            </ul>
        </div>
        <div class="col-md-8">
            <div class="row">
                <h3><?php the_title() ?></h3>
				<?php the_post() ?>

                <div class="ts-main-ticket-content container">
                    <div class="row ticket-content">
						<?php the_content(); ?>
                    </div>

					<?php require TS_VIEW . "front/show-ticket-replies.php"; ?>

                    <hr>

					<?php if ( TS_Ticket::get_ticket_status( get_the_ID() ) != "closed" ): ?>
                        <form method="post" enctype="multipart/form-data">
                            <input type="hidden" value="<?php echo get_the_ID() ?>" name="ticket-id">
                            <?php wp_nonce_field( 'ticket_reply_by_user', 'ticket_reply_by_user' ); ?>
                            <div class="form-group">
								<?php
								$settings = array(
									'media_buttons' => false,
									'teeny'         => true,
									'quicktags'     => false,
									'editor_class'  => 'ts-edittextarea form-control',
									'textarea_name' => 'ts_content',
									'textarea_rows' => 5,
								);
								wp_editor( '', 'ts_content', $settings );
								?>
                            </div>

                            <div class="form-group" id="attachments">
                                <label for="attachment">ضمیمه</label>
                                <input type="file" name="attachment" id="attachment">
                                <br>
								<?php
								printf( "شما می توانید حداکثر تا مقدار %s آپلود نمایید", ( wp_max_upload_size() / 1024 / 1024 ) . "MB" );
								// <input type="button" value="+" id="add_attachment"> ?>
                            </div>

                            <input type="submit" value="ارسال" class="btn btn-primary">

                        </form>
					<?php endif ?>

                </div>
            </div>
        </div>
    </div>
</div>

<?php get_footer();
wp_footer(); ?>
