<?php if ( current_user_can( "ts_assign_agent" ) ): ?>
    <select name="agent" id="agent-selector-top">
        <option value="">پشتیبان</option>
		<?php foreach ( $agents as $agent ) : ?>
            <option value="<?php echo $agent->ID; ?>" <?php selected( $_GET['agent'] ?? null , $agent->ID ) ?>><?php echo $agent->display_name; ?></option>
		<?php endforeach; ?>
    </select>
<?php endif; ?>

<select name="user" id="user-selector-top">
    <option value="">ارسال کننده</option>
	<?php foreach ( $users as $user ) : ?>
        <option value="<?php echo $user->ID; ?>" <?php selected( $_GET['user'] ?? null, $user->ID ) ?>><?php echo $user->display_name; ?></option>
	<?php endforeach; ?>
</select>

<select name="status" id="status-selector-top">
    <option value="">وضعیت</option>
	<?php foreach ( $statuses as $status ) : ?>
        <option value="<?php echo $status[0] ?>" <?php selected( $_GET['status'] ?? null, $status[0] ) ?>><?php echo $status[1]; ?></option>
	<?php endforeach; ?>
</select>

<select name="priority" id="priority-selector-top">
    <option value="">اولویت</option>
	<?php foreach ( $priorities as $priority ) : ?>
        <option value="<?php echo $priority['id']; ?>" <?php selected( $_GET['priority'] ?? null, $priority['id'] ) ?>><?php echo $priority['text']; ?></option>
	<?php endforeach; ?>
</select>


<label for="ticket-id">شناسه تیکت</label>
<input type="search" id="ticket-id" name="code" value="<?php isset($_GET['code']) ? print($_GET['code']) : print('') ?>">

