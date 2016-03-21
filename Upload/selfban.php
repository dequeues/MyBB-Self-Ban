<?php

define('IN_MYBB', 1);

require_once('./global.php');

// if user is not logged in or the plugin is not enabled or allowed groups is empty, show no permission error
if($mybb->user['uid'] == 0 || !isset($mybb->settings['selfban_enabled']) || $mybb->settings['selfban_enabled'] == '0' || empty($mybb->settings['selfban_groupsallowed']))
{
	error_no_permission();
}

// check if the users group is allowed in this page
$groups_allowed = explode(',', $mybb->settings['selfban_groupsallowed']);
if(!in_array($mybb->user['usergroup'], $groups_allowed))
{
	error_no_permission();
}

if(isset($mybb->input['submit']))
{
	verify_post_check($mybb->get_input('my_post_key'));

	//day, month, year
	$ban_duration_strings = explode(',', $mybb->get_input('duration'));
	$ban_duration = array_map(create_function('$v', 'return (int)$v;'), $ban_duration_strings);

	$check_duration = array_filter($ban_duration);
	if(empty($check_duration))
	{
		error("There was an error submitting your self ban request. Try again or contact an administrator");
	}

	$distring = "P{$ban_duration[2]}Y{$ban_duration[1]}M{$ban_duration[0]}D";

	$date = new DateTime();
	$date->add(new DateInterval($distring));

	//day-month-year
	$bantime = (int)$ban_duration[0] . '-' . (int)$ban_duration[1] . '-' . (int)$ban_duration[2];
	$ban_insert = array(
		'uid' => $mybb->user['uid'],
		'gid' => $mybb->settings['selfban_bannedgroup'],
		'oldgroup' => $mybb->user['usergroup'],
		'oldadditionalgroups' => $mybb->user['additionalgroups'],
		'olddisplaygroup' => $mybb->user['displaygroup'],
		'admin' => $mybb->user['uid'],
		'dateline' => TIME_NOW,
		'bantime' => $bantime,
		'lifted' => $date->getTimestamp(),
		'reason' => 'Self ban'
	);
	$db->insert_query('banned', $ban_insert);

	$update_user = array(
		'usergroup' => $mybb->settings['selfban_bannedgroup'],
		'displaygroup' => 0,
		'additionalgroups' => ''
	);
	$db->update_query('users', $update_user, "uid = '{$mybb->user['uid']}'");

	$cache->update_banned();

	header('Location: selfban.php');
}

eval("\$html = \"".$templates->get('selfban')."\";");
output_page($html);


?>
