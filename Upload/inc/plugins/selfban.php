<?php

if(!defined("IN_MYBB"))
{
		die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function selfban_info()
{
	return array(
		'name' => 'Self Ban',
		'description' => 'Allows certain groups to self ban',
		'author' => 'Nathan (dequeues)',
		'authorsite' => 'https://github.com/dequeues',
		'version' => '1.0',
		'compatibility' => '18*'
	);
}

function selfban_activate()
{
	global $db;

	$number_groups_query = $db->simple_select('settinggroups', 'COUNT(*) as num_groups');
	$number_groups = (int)$db->fetch_field($number_groups_query, 'num_groups');

	$setting_group = array(
		'name' => 'selfban',
		'title' => 'Self Ban',
		'description' => 'Allows certain groups to self ban',
		'disporder' => ($number_groups + 1)
	);
	$gid = $db->insert_query('settinggroups', $setting_group);

	$banned_group_q = $db->simple_select('usergroups', 'gid', 'title=\'Banned\' OR isbannedgroup=\'1\'', 'LIMIT 1');
	$banned_group = $db->fetch_field($banned_group_q, 'gid');

	$settings = array(
		'selfban_enabled' => array(
			'title' => 'Enabled?',
			'description' => 'Whether the plugin is enabled or not',
			'optionscode' => 'yesno',
			'value' => '1'
		),
		'selfban_groupsallowed' => array(
			'title' => 'Groups allowed to self ban',
			'description' => 'Select which groups are allowed to use the Self Ban feature',
			'optionscode' => 'groupselect'
		),
		'selfban_bannedgroup' => array(
			'title' => 'Banned Group',
			'description' => 'Group to move the banned users to',
			'optionscode' => 'groupselectsingle',
			'value' => $banned_group
		)
	);

	$disporder = 1;
	foreach ($settings as $name => $setting)
	{
		$setting['name'] = $name;
		$setting['gid'] = $gid;
		$setting['disporder'] = $disporder;
		$db->insert_query('settings', $setting);
		$disporder++;
	}

	$insert_template_array = array(
		'title' => 'selfban',
		'template' => $db->escape_string(get_template()),
		'sid' => '-1',
		'version' => '',
		'dateline' => TIME_NOW
	);

	$db->insert_query('templates', $insert_template_array);

	rebuild_settings();
}

function selfban_deactivate()
{
	global $db;

	$db->delete_query('templates', 'title = \'selfban\'');
	$db->delete_query('settinggroups', 'name =\'selfban\'');
	$db->delete_query('settings', 'name LIKE (\'selfban_%\')');

	rebuild_settings();
}

function get_template()
{
	return '<html>
  <head>
    <title>{$main_title}</title>
	  {$headerinclude}
  </head>

  <body>
    {$header}
    <div id=container>
		<table border="0" cellspacing="1" cellpadding="4" class="tborder" style="table-layout:fixed;">
			<tr>
				<td class="thead" colspan=2>
					<span class="smalltext">
						<strong>{$main_title}</strong>
					</span>
				</td>
			</tr>
			<tr style="text-align:center;">
				<td class="trow1" style="overflow:auto;" colspan=2>
					<span style="font-size:20px;"><b>Self Ban</b></span>
					<p><b>NOTE:</b> If you do go ahead and ban your account, please note that there is going back. Once your account is banned, it is banned for however long you submit.</p>
					Duration: <form method="post">
						<input type=hidden name=my_post_key value="{$mybb->post_code}"></input>
						<select name="duration">
							<!-- day, month, year -->
							<option value="1,0,0">1 Day</option>
							<option value="2,0,0">2 Days</option>
							<option value="3,0,0">3 Days</option>
							<option value="7,0,0">1 Week</option>
							<option value="0,1,0">1 Month</option>
						</select><br />
						<input type="submit" value="Ban!" class="button" name="submit"></input>
					</form>
				</td>
			</tr>
    </div>
  </body>
</html>';
}


?>
