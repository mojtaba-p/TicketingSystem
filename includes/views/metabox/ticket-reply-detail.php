<div class="reply-item <?= $class ?>-reply" id="reply-<?= get_the_ID() ?>">
    <div class="reply-content">
		<?php echo apply_filters( "the_content", get_the_content() ) ?>
    </div>
    <div class="reply-date">
		<?= get_the_time() ?>
        <small style="color:lightgrey">(<?= $ago_time ?>)</small>
		<?php if ( TS_Post::get_attachment_count( get_the_ID() ) ): ?>
            <a href=" <?php echo TS_Post::get_attachment_guid( get_the_ID() ) ?>" target="_blank">
            <img src=" <?php echo TS_IMG . "attachment.svg" ?>" alt="" class="attachment-icon"/ >
            </a>
		<?php endif ?>
    </div>
</div>
<div class="wp-clearfix"></div>