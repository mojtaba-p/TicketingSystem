<?php

$agents          = $args['args']['agents'];
$ticket_agent    = $args['args']['agent'];
$status          = $args['args']['status'];
$ticket_priority = $args['args']['priority'];
?>
<?php $author = get_user_by( 'ID', $post->post_author ); ?>

<div class="component-panel" id="ticket-detail">
	<?php wp_nonce_field( "ts_set_ticket_status", "ts_set_ticket_status" ); ?>
    <input type="hidden" name="current_agent" value="<?php echo $ticket_agent ?>">
    <input type="hidden" name="current_priority" value="<?php echo $ticket_priority ?>">
    <div class="form-group">

		<?php if ( current_user_can( 'ts_assign_agent' ) ): ?>
            <label for="agent" class="form-element">پاسخگو:</label>
            <select name="agent" id="agent" class="form-element">
				<?php foreach ( $agents as $a ): ?>
                    <option value="<?php echo $a->ID ?>" <?php if ( $ticket_agent == $a->ID ) {
						echo "selected";
					} ?> ><?php echo $a->display_name ?> - <?php echo TS_User::get_user_role( $a->ID ) ?></option>
				<?php endforeach; ?>
            </select>
		<?php else : ?>
            <label for="agent" class="form-element">پاسخگو:</label>
            <span class="inline"><?php echo get_userdata( $ticket_agent )->display_name ?></span>
		<?php endif ?>
    </div>
    <div class="form-group">
        <span>اولویت:  </span>
		<?php if ( current_user_can( 'ts_assign_agent' ) ): ?>
            <select name="priority" id="priority">
				<?php foreach ( TS_Ticket::$priorities as $priority ) : ?>
                    <option value="<?php echo $priority["id"]; ?>" <?php if ( ( $priority["id"] ) == $ticket_priority ) {
						echo "selected";
					} ?> >
						<?php echo $priority["text"] ?>
                    </option>
				<?php endforeach ?>
            </select>
		<?php else: ?>
            <span class="bold"><?php echo TS_Ticket::$priorities[ $ticket_priority ]['text'] ?> </span>
		<?php endif ?>
    </div>

    <div class="form-group">
        وضعیت:

        <span class="bold"><?php echo TS_Ticket::get_ticket_status( $post->ID, "text" ); ?></span>
    </div>

    <div class="misc-pub-section curtime misc-pub-curtime form-group">
        <span id="timestamp">دریافت شده در: <b><?php echo get_the_date( '', $post->ID ) ?></b></span>
    </div>
    <div class="wp-clearfix">
        <?php if ( TS_Ticket::get_ticket_status( $post->ID ) != "closed") : ?>
        <div class="bootstrap-wrapper">
            <p class="submit float-right">
                <input type="submit" name="submit" id="submit" class="button button-primary" value="بروزرسانی">
            </p>

            <a class="text-danger float-left btn"
               href="<?php echo wp_nonce_url( get_edit_post_link( $post->ID ) ) . "&close-ticket" ?>">بستن</a>

        </div>
        <?php else: ?>
            <a class="text-danger float-left btn"
               href="<?php echo wp_nonce_url( get_edit_post_link( $post->ID ) ) . "&open-ticket" ?>">بازکردن</a>
        <?php endif ?>
    </div>
</div>
