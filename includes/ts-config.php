<?php

/**
 * priority types configuration
 */


/**
 * user role permissions
 */
$ts_permissions = array();

$ts_permissions[ 'user' ] = array(
	'add_ticket',
	'add_reply',
	'view_ticket',
	'view_reply',
	'upload_attachment'
);

$ts_permissions[ 'agent' ] = array(
	$ts_permissions
);

