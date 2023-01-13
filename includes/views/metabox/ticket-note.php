<?php if ( isset( $args['args']['notes'] ) ): ?>
	<?php if ( count( $args['args']['notes'] ) > 0 ) : ?>
        <h4>لیست یادداشت ها</h4>
		<?php foreach ( $args['args']['notes'] as $note ): ?>
            <div class="note-item">
                <strong><?php echo get_the_author_meta( "display_name", $note->post_author ) ?></strong><br>
                <small> <?php echo get_the_date( '', $note->ID ); ?></small>
                <br>
                <div class="note-content">
					<?php echo $note->post_content ?>
                </div>
            </div>
		<?php endforeach; ?>
        <hr>
	<?php endif; ?>
<?php endif; ?>
<h4>افزودن یادداشت جدید</h4>
<textarea name="note" id="" cols="30" rows="10"></textarea>