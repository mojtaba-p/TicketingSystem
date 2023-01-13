<?php require TS_VIEW . "front/errors.php"; ?>
<div class="container-fluid text-right">
    <div class="row">
        <div class="col-12">
            <a href="<?= get_post_type_archive_link( "ts_ticket" ); ?>" class="btn btn-primary float-left"
            > لیست تیکت ها </a>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">

            <form method="post" enctype="multipart/form-data">
				<?php
				wp_nonce_field( 'ts_user_new_ticket', 'new_ticket_by_user' );
				?>
                <div class="form-group">
                    <label for="subject" class="col-form-label">موضوع تیکت:</label>

                    <input type="text" id="subject" name="subject" class="form-control">
                </div>

                <div class="form-group">
                    <label for="content">متن تیکت:</label>
					<?php
					$settings = array(
						'media_buttons' => false,
						'teeny'         => true,
						'quicktags'     => false,
						'editor_class'  => 'ts-edittextarea form-control',
						'textarea_name' => 'ts_content',
						'textarea_rows' => 20,
					);
					wp_editor( '', 'ts_content', $settings );
					?>
                </div>

                <div class="form-group text-right">
                    <label for="priority">اولویت پیام</label>

                    <select name="priority" id="priority" class="form-control">
						<?php foreach ( TS_Ticket::$priorities as $priority ): ?>
                            <option value="<?php echo $priority["id"] ?>"> <?php echo $priority["text"] ?> </option>
						<?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group text-right">
                    <label for="category">دسته بندی</label>
                    <select name="category" id="category" class="form-control">
						<?php foreach ( TS_PostType::get_all_ts_ticket_terms() as $term ): ?>
                            <option value="<?php echo $term->term_id ?>"> <?php echo $term->name; ?> </option>
						<?php endforeach; ?>
                    </select>
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
        </div>
    </div>
</div>

<script>
    $ = jQuery;
    $(document).ready(function () {
        $("#category").select2({language: "fa", dir: "rtl",});
        $("#priority").select2({language: "fa", dir: "rtl",});
    })
</script>