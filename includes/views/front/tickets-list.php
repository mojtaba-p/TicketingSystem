<?php get_header(); ?>
<?php require TS_VIEW . "front/errors.php"; ?>

<div class="container text-right">
    <div class="row">
        <div class="col-md-6">
            <h3>لیست تیکت ها</h3>
        </div>
        <div class="col-md-6">
            <a href="<?php echo get_the_guid(TicketingSystem::$options['ticket-submit-page']); ?>" class="btn btn-primary float-left">ثبت تیکت جدید</a>
        </div>

    </div>
    <hr>
    <div class="row">
        <table id="tickets-table" class="table">
            <?php if (have_posts()) : ?>
                <thead>
                    <th>شناسه</th>
                    <th>عنوان</th>
                    <th>وضعیت</th>
                    <th>اولویت</th>
                    <th>دسته بندی</th>
                </thead>
                <tbody>
                    <?php while (have_posts()) : the_post(); ?>

                        <tr>
                            <td><?php the_ID() ?></td>
                            <td><a href="<?php the_permalink() ?>"><?php the_title() ?></a></td>
                            <td><?php echo TS_PostType::ts_get_post_status(get_the_ID()) ?></td>
                            <?php
                            $priority_index = null != get_post_meta(get_the_ID(), TS_TICKET_PRIORITY, true) ? get_post_meta(get_the_ID(), TS_TICKET_PRIORITY, true) - 1 : 0;
                            $priority       = TS_Ticket::$priorities[$priority_index]["text"];
                            ?>
                            <td><?php echo $priority ?></td>
                            <td>
                                <?php if (get_the_terms(get_the_ID(), "ticket_type")) : ?>
                                    <?php foreach (get_the_terms(get_the_ID(), "ticket_type") as $term) : ?>
                                        <?php echo $term->name; ?>
                                    <?php endforeach; ?>
                                <?php endif ?>

                            </td>
                        </tr>

                    <?php endwhile ?>
                </tbody>

            <?php else : ?>
                <td>
                    <tr>شما هیچ تیکت ثبت شده ای ندارید</tr>
                </td>
            <?php endif ?>
        </table>
        <?php echo TS_Ticket::ticket_paginate_links() ?>
    </div>
</div>

<?php get_footer(); ?>