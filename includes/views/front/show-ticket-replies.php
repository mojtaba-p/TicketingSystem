<?php foreach ( TS_TicketReply::load_all_replies( get_the_ID() ) as $reply ) : ?>
    <div class="row reply-item <?php  if ( $post->post_author != $reply->post_author ) {	    echo "agent";    } ?>">
        <div class="col-12 author"> <?php echo get_the_author_meta( "display_name", $reply->post_author ) ?><br>
            <small class="date"><?php echo $reply->post_date ?></small>
            <small class="human-date"><?php echo TS_Post::time_elapsed_string( $reply->ID ) ?></small>
        </div>
        <div class="col-12 content reply-content">
			<?php echo apply_filters( "the_content", $reply->post_content ) ?>
			<?php if ( TS_Post::get_attachment_count( $reply->ID ) ): $attachment = TS_Post::get_attachment_guid( $reply->ID ) ?>
                <a href="<?php echo $attachment ?>" target="_blank">
                    <img src="<?php echo TS_IMG . "attachment.svg" ?>" alt="" class="attachment-icon">
                </a>
			<?php endif ?>
        </div>
    </div>
<?php endforeach; ?>