<?php
$replies   = new WP_Query(
        array(
                'post_parent'   =>  $post->ID,
                'oreder'        =>  'asc',
                'post_type'     =>  array('ts_ticket_reply', 'ts_ticket_history'),
                'post_status'   =>  'publish'
        )
);

if ( $replies->have_posts() ) {
	while ( $replies->have_posts() ) {

		$replies->the_post();

		$class = $post->post_author == get_the_author_meta('ID') ? 'user' : 'admin';

		$ago_time = TS_Post::time_elapsed_string( $post->ID );

		if(     'ts_ticket_reply' == get_post_type() ) {
			require( TS_VIEW.'/metabox/ticket-reply-detail.php' );
		} 
		elseif( 'ts_ticket_history' == get_post_type()) {
			
			require( TS_VIEW.'/metabox/ticket-reply-history.php' );
		}
		//TODO: Add Reply Edit And Delete options.
	}
} ?>
<?php if ( TS_Ticket::get_ticket_status( $_GET['post'] ) != "closed") : ?>
<div class="ts-saved-replies">
    <label for="saved-reply">پاسخ آماده:</label>
    <select name="saved-reply" id="saved-reply">
        <option value="">یک پاسخ آماده انتخاب کنید</option>
        <?php foreach ( TS_SavedReply::get_all_saved_replies() as $sr): ?>
            <option value="<?php echo $sr->term_id ?>"><?php echo $sr->name ?></option>
        <?php endforeach ?>
    </select>
</div>
<?php
$editor_id = "ts-ticket-reply";
//$editor_content = apply_filters( 'the_content', $post->post_content );

$settings = array(
	'media_buttons' => false,
	'teeny'         => true,
	'quicktags'     => false,
	'textarea_rows' => 5,
);

wp_editor( '', $editor_id, $settings );

?>

<div class="wp-clearfix ts-attachment-container">
    <div class="ts-title">ضمیمه</div>
    <input type="file" name="attachment" id="attachment">
</div>

<div class="wp-clearfix">
    <p class="submit">
        <input type="submit" name="submit" id="submit" class="button button-primary" value="ارسال پاسخ">
    </p>
</div>

<?php endif; ?>