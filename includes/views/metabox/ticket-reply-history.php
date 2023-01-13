
<?php
$need_to_show_items = array( "ticket_created", "ticket_closed", "ticket_opened" );
if (  in_array ( get_the_content(), $need_to_show_items ) ): ?>
    <div class="reply-item reply-history <?=$class ?>-history" id="history-<?= get_the_ID() ?>">
        <div class="reply-content">
            <?= get_the_title() ?>
        </div>
        <div class="reply-date">
            <?= get_the_time() ?> <small style="color:lightgrey">(<?=$ago_time ?>)</small>
        </div>
    </div>
<?php endif ?>