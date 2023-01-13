<?php

use TicketingSystem\User;

$author  = get_user_by( "ID", $post->post_author );
$tickets = count_user_posts( $post->post_author, "ts_ticket" );
$posts   = get_posts( array( "author" => $post->post_author, "post_type" => "ts_ticket", "posts_per_page" => - 1 ) );


function echo_posts_list( $status, $posts, $current_post ) { ?>
    <ul class="tickets p-0 m-0">
		<?php foreach ( $posts as $ticket ) : ?>
			<?php if ( TS_Ticket::get_ticket_status( $ticket->ID ) !== $status )
				continue ?>
            <li>
				<?php if ( $current_post == $ticket->ID ): ?>
					<?php echo $ticket->post_title ?> - (همین تیکت)
				<?php else: ?>
                    <a href="<?php echo admin_url( "post.php?post=" . $ticket->ID . "&action=edit" ) ?>"> <?php echo $ticket->post_title ?></a>
				<?php endif ?>
            </li>
		<?php endforeach ?>
    </ul>
<?php }

?>
<div class="bootstrap-wrapper">
    <div class="container">
        <br>
        <div class="row text-center">
            <div class="col-12">
                <b>ارسال شده توسط:</b>

                <a href="<?php echo admin_url( "edit.php?post_type=ts_ticket&author=" . $post->post_author ) ?>"
                   target="_blank">
					<?php echo $author->display_name ?>
                </a></div>
        </div>
        <hr>
        <div class="row">
            <div class="col-12">
                <b>کل تیکت ها</b>: <?php echo $tickets ?><br>
                <div class="border border-primary p-1 m-2">
                    <b>تکیت های باز</b>: <?php echo TS_User::get_user_tickets_by_status( $post->post_author, "open" ) ?>
					<?php echo_posts_list( "open", $posts, $post->ID ) ?>
                </div>

                <div class="border border-secondary p-1 m-2">
                    <b>پاسخ داده
                        شده</b>: <?php echo TS_User::get_user_tickets_by_status( $post->post_author, "answered" ) ?>
                    <br>
					<?php echo_posts_list( "answered", $posts, $post->ID ) ?>
                </div>

                <div class="border border-danger p-1 m-2">
                    <b>تکیت های
                        بسته</b>: <?php echo TS_User::get_user_tickets_by_status( $post->post_author, "closed" ) ?>
					<?php echo_posts_list( "closed", $posts, $post->ID ) ?>
                </div>
            </div>
        </div>
    </div>
</div>