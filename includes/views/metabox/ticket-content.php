<?php
$post_object = TS_Post::class;
echo apply_filters( 'the_content' , $post->post_content);
$post_attachments_count = $post_object::get_attachment_count( $post->ID );
if ( $post_attachments_count > 0 ):
	print("<hr>");
	printf( "پیوست ها: %d عدد", $post_attachments_count );
	print("<br>");
	printf( "<a href='%s'>نمایش پیوست</a>" , $post_object::get_attachment_guid( $post->ID ) );
endif;