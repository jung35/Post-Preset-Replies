<?php
/**
 * Post Preset Replies 1.0.1

 * Copyright 2012 Jung Oh

 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at

 ** http://www.apache.org/licenses/LICENSE-2.0

 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.

 * Thanks to BlackChaos from http://www.gthreeforums.net for idea
**/

if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$plugins->add_hook('usercp_menu_built', 'postpresetreplies_ucp_nav', -10);
$plugins->add_hook('usercp_start', 'postpresetreplies_ucp');

$plugins->add_hook('showthread_end', 'postpresetreplies_quick_reply');
$plugins->add_hook('newreply_end', 'postpresetreplies_quick_reply');
$plugins->add_hook('editpost_end', 'postpresetreplies_quick_reply');


/** 
Post Preset Replies user PLUGIN info
*/
function postpresetreplies_info()
{
	return array(
		"name" => "Post Preset Replies",
		"description" => "Allows users to pre-set set ammount of replies in their UCP (controlled by ACP) and can use them with just a click!",
		"website" => "",
		"author" => "Jung Oh",
		"authorsite" => "http://jung3o.com",
		"version" => "1.0.1",
		"compatibility" => "16*",
		"guid" => "36cadfc1a950116197a47c213ec51c39"
	);
}


/** 
Post Preset Replies user PLUGIN install
*/
function postpresetreplies_install()
{
	global $db,$mybb;
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

	if(!$db->table_exists("postpresetreplies"))
	{
		$db->write_query("
			CREATE TABLE  " . TABLE_PREFIX . "postpresetreplies (
				`id` SMALLINT(5) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
				`uid` SMALLINT(5) NOT NULL ,
				`title` VARCHAR(255) NOT NULL,
				`post` text NOT NULL,
				`set_date` int(11) NOT NULL
			) ENGINE = MYISAM ;
		");
	}


	$settings_group = array(
		"name" => "postpresetreplies",
		"title" => "Post Preset Replies",
		"description" => "Setting to control Post Preset Replies for users.",
		"disporder" => "99",
		"isdefault" => 0
	);
	$db->insert_query("settinggroups", $settings_group);
	$gid = $db->insert_id();

	$cfg = array();
/*
	$cfg[] = array(
		"name" => "",
		"title" => "",
		"description" => "",
		"optionscode" => "",
		"value" => ""
	);
*/
	$cfg[] = array(
		"name" => "preset_limit",
		"title" => "Preset Limit",
		"description" => "Lets a Global limit for amount of Presets",
		"optionscode" => "text",
		"value" => "5"
	);
	$cfg[] = array(
		"name" => "max_char",
		"title" => "Maximum Characters per Preset",
		"description" => "Maximum numbers of characters allowed in Preset Repply (Leave it blank if infinite)\nForum Default :".$mybb->settings['maxmessagelength']." characters",
		"optionscode" => "text",
		"value" => $mybb->settings['maxmessagelength']
	);
	$cfg[] = array(
		"name" => "disabled_group",
		"title" => "Groups that cannot use this. (UNIVERSAL)",
		"description" => "Type in all the groups that you don't want them using this (Seperated by comma (,))",
		"optionscode" => "text",
		"value" => ""
	);
	$cfg[] = array(
		"name" => "allow_bbcode_view",
		"title" => "Allows users to preview BBcode in their preseted post",
		"description" => "Yes - Allow | No - Not allow",
		"optionscode" => "yesno",
		"value" => 1
	);

	$i = 1;
	foreach($cfg as $setting)
	{
		$insert = array(
			"name" => $db->escape_string("postpresetreplies_".$setting['name']),
			"title" => $db->escape_string($setting['title']),
			"description" => $db->escape_string($setting['description']),
			"optionscode" => $db->escape_string($setting['optionscode']),
			"value" => $db->escape_string($setting['value']),
			"disporder" => $i,
			"gid" => intval($gid),
		);
		$db->insert_query("settings", $insert);
		$i++;
	}

	$templates = array();
/*
	$templates[] = array(
		"title" => "",
		"template" => ""
	);
 */
	$templates[] = array(
		"title" => "ucp_nav",
		"template" => "<tr><td class=\"trow1 smalltext\"><a href=\"usercp.php?action=postpresetreplies\" class=\"usercp_nav_item\">Preset Posts</a></td></tr>"
	);
	$templates[] = array(
		"title" => "ucp",
		"template" => "<html>
<head>
<title>{\$mybb->settings['bbname']} - Post Preset Replies</title>
{\$headerinclude}
</head>
<body>
	{\$header}
	<table width=\"100%\" border=\"0\" align=\"center\">
		<tr>
			{\$usercpnav}
			<td align=\"center\" valign=\"top\">
				{\$preset_insert_result}
				<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
					<tr>
						<td class=\"thead\" colspan=\"21\"><strong>Your Preset Posts</strong></td>
					</tr>
					<tr>
						<td colspan=\"5\" align=\"center\" class=\"tcat\"><strong>Title</strong></td>
						<td colspan=\"10\" align=\"center\" class=\"tcat\"><strong>Message</strong></td>
						<td colspan=\"5\" align=\"center\" class=\"tcat\"><strong>Created Time</strong></td>
						<td colspan=\"1\" class=\"tcat\"></td>
					</tr>
					{\$preset_list}
				</table>
				<br>
				{\$newpreset}
			</td>
		</tr>
	</table>
	{\$footer}
</body>
</html>"
	);
	$templates[] = array(
		"title" => "ucp_new_preset",
		"template" => "
				<form action=\"usercp.php?action=postpresetreplies_new\" method=\"POST\">
					<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
						<tr>
							<td class=\"thead\" colspan=\"2\"><strong>Create a New Preset Post</strong></td>
						</tr>
						<tr>
							<td width=\"50%\" align=\"left\" class=\"tcat\"><strong>Title of Preset Post</strong><br><div align=\"left\" class=\"smalltext\">This is just like any post title</div></td>
							<td width=\"50%\" align=\"center\" class=\"trow1\"><input size=\"40\" maxlength=\"85\" type=\"text\" name=\"title\" /></td>
						</tr>
						<tr>
							<td width=\"50%\" align=\"left\" class=\"tcat\"><strong>Message</strong><br><div align=\"left\" class=\"smalltext\">This is just like any posts, BBcodes are permitted if you the administrator enabled it.</div></td>
							<td width=\"50%\" align=\"center\" class=\"trow2\"><textarea cols=\"70\" rows=\"10\" name=\"message\"></textarea></td>
						</tr>
						<tr>
							<td colspan=\"2\" align=\"center\" class=\"trow1\"><input type=\"submit\" value=\"Make new Preseted Post\"></td>
						</tr>
					</table>
				</form>"
	);
	$templates[] = array(
		"title" => "ucp_edit",
		"template" => "<html>
<head>
<title>{\$mybb->settings['bbname']} - Post Preset Replies</title>
{\$headerinclude}
</head>
<body>
	{\$header}
	<table width=\"100%\" border=\"0\" align=\"center\">
		<tr>
			{\$usercpnav}
			<td align=\"center\" valign=\"top\">
				<form action=\"usercp.php?action=postpresetreplies_edit\" method=\"POST\">
					<table border=\"0\" cellspacing=\"{\$theme['borderwidth']}\" cellpadding=\"{\$theme['tablespace']}\" class=\"tborder\">
						<tr>
							<td class=\"thead\" colspan=\"2\"><strong>Edit Preset Post</strong></td>
						</tr>
						<tr>
							<td width=\"50%\" align=\"left\" class=\"tcat\"><strong>Title of Preset Post</strong><br><div align=\"left\" class=\"smalltext\">This is just like any post title</div></td>
							<td width=\"50%\" align=\"center\" class=\"trow1\"><input size=\"40\" maxlength=\"85\" type=\"text\" name=\"title\" value=\"{\$pretitle}\" /></td>
						</tr>
						<tr>
							<td width=\"50%\" align=\"left\" class=\"tcat\"><strong>Message</strong><br><div align=\"left\" class=\"smalltext\">This is just like any posts, BBcodes are permitted if you the administrator enabled it.</div></td>
							<td width=\"50%\" align=\"center\" class=\"trow2\"><textarea cols=\"70\" rows=\"10\" name=\"message\">{\$prepost}</textarea></td>
						</tr>
						<tr>
							<input type=\"hidden\" name=\"id\" value=\"{\$preid}\" />
							<td colspan=\"2\" align=\"center\" class=\"trow1\"><input type=\"submit\" value=\"Make new Preseted Post\"></td>
						</tr>
					</table>
				</form>
			</td>
		</tr>
	</table>
	{\$footer}
</body>
</html>"
	);

	foreach($templates as $template)
	{
		$insert = array(
			"title" => $db->escape_string("postpresetreplies_".$template['title']),
			"template" => $db->escape_string($template['template']),
			"sid" => "-1",
			"version" => "1600",
			"dateline" => TIME_NOW
		);
		$db->insert_query("templates", $insert);
	}

		find_replace_templatesets("usercp_nav_misc", "#".preg_quote('</tbody>')."#i", '{postpresetreplies_ucp_nav}</tbody>');
		find_replace_templatesets("showthread_quickreply", "#".preg_quote('<span class="smalltext">{$lang->message_note}<br /><br />')."#i", '<span class="smalltext">{$lang->message_note}<br /><br />{postpresetreplies_use}');
		find_replace_templatesets("newreply", "#".preg_quote('{$smilieinserter}')."#i", '{$smilieinserter}{$postpresetreplies_use}');
		find_replace_templatesets("editpost", "#".preg_quote('{$smilieinserter}')."#i", '{$smilieinserter}{$postpresetreplies_use}');

	rebuild_settings();
}


/** 
Post Preset Replies PLUGIN is_installed
*/
function postpresetreplies_is_installed()
{
	global $db;
	
		if($db->table_exists("postpresetreplies")) {
			return true;
		}

	return false;
}


/** 
Post Preset Replies PLUGIN deactivate
*/
function postpresetreplies_deactivate()
{
	global $db,$cache;
	require_once MYBB_ROOT . "inc/adminfunctions_templates.php";

	$db->delete_query("settinggroups", "name = 'postpresetreplies'");
	
	$settings = array(
/* 
		"postpresetreplies_",
*/
		"postpresetreplies_preset_limit",
		"postpresetreplies_disabled_group",
		"postpresetreplies_max_char",
		"postpresetreplies_allow_bbcode_view",
	);
	$settings = "'" . implode("','", $settings) . "'";
	$db->delete_query("settings", "name IN ({$settings})");

	$db->drop_table("postpresetreplies");

	$templates = array(
/*
		"postpresetreplies_",
*/
		"postpresetreplies_ucp",
		"postpresetreplies_ucp_new_preset",
		"postpresetreplies_ucp_nav",
		"postpresetreplies_ucp_edit",
	);
	$templates = "'" . implode("','", $templates) . "'";
	$db->delete_query("templates", "title IN ({$templates})");

	find_replace_templatesets("usercp_nav_misc", "#".preg_quote('{postpresetreplies_ucp_nav}')."#i", '', 0);
	find_replace_templatesets("showthread_quickreply", "#".preg_quote('{postpresetreplies_use}')."#i", '', 0);
	find_replace_templatesets("newreply", "#".preg_quote('{$postpresetreplies_use}')."#i", '', 0);
	find_replace_templatesets("editpost", "#".preg_quote('{$postpresetreplies_use}')."#i", '', 0);

	rebuild_settings();

	$cache->update('postpresetreplies',"");
}


/** 
Post Preset Replies PLUGIN user control panel navigation menu
*/
function postpresetreplies_ucp_nav()
{
	global $mybb, $templates,$usercpnav;

	eval("\$nav_temp = \"".$templates->get("postpresetreplies_ucp_nav")."\";");
	$usercpnav = str_replace("{postpresetreplies_ucp_nav}", $nav_temp, $usercpnav);
}


/** 
Post Preset Replies PLUGIN Quick Reply
*/
function postpresetreplies_quick_reply()
{
	global $mybb, $quickreply, $newreply, $cache, $postpresetreplies_use;

	$ppr = $cache->read('postpresetreplies');
	$user_ppr = $ppr[$mybb->user['uid']];
	$preset_use = "";

	if(!empty($user_ppr))
	{
		if(count($user_ppr) > 0)
		{

			$hidden_preset = "";

			$javascript = '<script type="text/javascript">
function presetpost_selection() {
	var a = document.getElementById("postpresetreplies_select");
	var b = a.options[a.selectedIndex].value;
	var c = document.getElementById("hidden_preset_"+b).innerHTML;
	document.getElementsByTagName("textarea")[0].value = c;
}
</script>';

			$preset_use = $javascript."<div class=\"smalltext\">Select a Preset to use</div>".
							"<select id=\"postpresetreplies_select\" onchange=\"presetpost_selection()\" style=\"width:120px;max-width:120px\"><option value=\"0\" selected=\"selected\"> - - - </option>";
			foreach($user_ppr as $id => $a_preset)
			{
				$hidden_preset .= "<div id=\"hidden_preset_".$a_preset['id']."\" style=\"display:none !important;\">".$a_preset['post']."</div>";
				$preset_use .= "<option value=".$a_preset['id'].">".$a_preset['title']."</option>";
			}
			$preset_use .= "<select><br><br><div id=\"hidden_preset_0\" style=\"display:none !important;\"></div>".$hidden_preset;
		}
	}
	$postpresetreplies_use = "<br><div style=\"text-align:center;\">".$preset_use."</div>";
	if($quickreply)
	{
		$quickreply = str_replace("{postpresetreplies_use}", $preset_use, $quickreply);
	}
}


/** 
Post Preset Replies PLUGIN user control panel page
*/
function postpresetreplies_ucp()
{
	global $mybb, $db, $cache, $lang, $theme, $templates, $headerinclude, $header, $footer, $usercpnav, $newpreset;
	
	require_once MYBB_ROOT . "inc/class_parser.php";
	$postParser = new postParser();
	$parse_options = array();
	if($mybb->settings['postpresetreplies_allow_bbcode_view'])
	{
		$parse_options = array(
			"allow_smilies" => 1,
			"allow_mycode" => 1,
			"nl2br" => 1,
			"filter_badwords" => 1,
			"me_username" => 1,
			"shorten_urls" => 1,
			"highlight" => 1
		);
	}

	$ppr = $cache->read('postpresetreplies');
	$user_ppr = $ppr[$mybb->user['uid']];

	if($mybb->input['action'] == "postpresetreplies")
	{
		if(!empty($mybb->input['e']))
		{
			add_breadcrumb($lang->nav_usercp, "usercp.php");
			add_breadcrumb("Preset Posts", "usercp.php?action=postpresetreplies");
			add_breadcrumb("Edit", "usercp.php?action=postpresetreplies&e=".$mybb->input['e']);

			$pretitle = $user_ppr[$mybb->input['e']]['title'];
			$prepost = $user_ppr[$mybb->input['e']]['post'];
			$preid = $user_ppr[$mybb->input['e']]['id'];

			eval("\$ucp_temp = \"".$templates->get('postpresetreplies_ucp_edit')."\";");

		} elseif (!empty($mybb->input['d'])) {
			$db->query("DELETE FROM ".TABLE_PREFIX."postpresetreplies WHERE id = ".$db->escape_string($mybb->input['d'])." and  uid=".$mybb->user['uid']);
			postpresetreplies_update();
			redirect("usercp.php?action=postpresetreplies");
		} else {
			add_breadcrumb($lang->nav_usercp, "usercp.php");
			add_breadcrumb("Preset Posts", "usercp.php?action=postpresetreplies");

			$preset_list = "";
			if(isset($mybb->input['error'])) {
				if($mybb->input['error']) {
					if(isset($mybb->input['reason'])) {
						$preset_insert_result = "<div class=\"error_message\">Error : {$mybb->input['reason']}</div>";
					}
				} else {
					$preset_insert_result = "<div class=\"success_message\">Success! You're preset post has been submitted.</div>";
				}
			} else {
				$preset_insert_result = "";
			}
			$groups = explode(",",",".$mybb->settings['postpresetreplies_disabled_group']);
			if(!in_array($mybb->user['usergroup'],$groups)) {
				if(!empty($user_ppr))
				{
					foreach($user_ppr as $a_preset)
					{
						$tb_row = alt_trow();
						$date = my_date($mybb->settings['dateformat'], $a_preset['set_date']);

						$post = $postParser->parse_message($a_preset['post'],$parse_options);

						$preset_list .= "
								<tr>
									<td colspan=\"5\" align=\"center\" class=\"{$tb_row}\">{$a_preset['title']}</td>
									<td colspan=\"10\" align=\"center\" class=\"{$tb_row}\">{$post}</td>
									<td colspan=\"5\" align=\"center\" class=\"{$tb_row}\">{$date}</td>
									<td colspan=\"1\" align=\"center\" class=\"{$tb_row}\"><a href=\"usercp.php?action=postpresetreplies&e={$a_preset['id']}\">Edit</a><br><a href=\"usercp.php?action=postpresetreplies&d={$a_preset['id']}\">Delete</a></td>
								</tr>";
					}
				}
				if(!$preset_list) {
					$preset_list ="<tr><td colspan=\"21\" align=\"center\" class=\"trow1\">No Preset Posts</td></tr>";
				}

				if(!empty($user_ppr))
				{
					if(count($user_ppr) < $mybb->settings['postpresetreplies_preset_limit']) {
						eval("\$newpreset = \"".$templates->get('postpresetreplies_ucp_new_preset')."\";");
					} else {$newpreset = "<div align=\"left\" class=\"smalltext\">Sorry, you can have maximum of {$mybb->settings['postpresetreplies_preset_limit']} Presets</div>";}
				} else {
					eval("\$newpreset = \"".$templates->get('postpresetreplies_ucp_new_preset')."\";");
				}
			} else {
				$newpreset = "<div align=\"left\" class=\"smalltext\">Sorry, You're group isn't allowed to Create New Preset Posts</div>";
			}
			eval("\$ucp_temp = \"".$templates->get('postpresetreplies_ucp')."\";");
		}
		output_page($ucp_temp);
	} elseif($mybb->input['action'] == "postpresetreplies_new")
	{
		$groups = explode(",",",".$mybb->settings['postpresetreplies_disabled_group']);
		if(!in_array($mybb->user['usergroup'],$groups)) {
			$error = 0;

			if(!empty($user_ppr))
			{
				if(count($user_ppr) >= $mybb->settings['postpresetreplies_preset_limit']) {
					$error = 1;
				}
			}

			if($error) {
				redirect("usercp.php?action=postpresetreplies&error=1&reason=You have ".$mybb->settings['postpresetreplies_preset_limit']." or more preseted posts already");
			}

			if(!isset($mybb->input['title']) or !isset($mybb->input['message'])) {
				redirect("usercp.php?action=postpresetreplies&error=1&reason=Not all fields are filled in");
			}

			if(strlen($mybb->input['message']) < $mybb->settings['minmessagelength']) {
				redirect("usercp.php?action=postpresetreplies&error=1&reason=Post not met the minimum character of ".$mybb->settings['minmessagelength']);
			}

			if(strlen($mybb->input['message']) > $mybb->settings['postpresetreplies_max_char']) {
				redirect("usercp.php?action=postpresetreplies&error=1&reason=Post exceeded the maximum character of ".$mybb->settings['postpresetreplies_max_char']);
			}

			$insert = array(
				"uid" => $db->escape_string($mybb->user['uid']),
				"title" => $db->escape_string($mybb->input['title']),
				"post" => $db->escape_string($mybb->input['message']),
				"set_date" => $db->escape_string(time())
			);
			$db->insert_query("postpresetreplies", $insert);
			postpresetreplies_update();
			redirect("usercp.php?action=postpresetreplies&error=0");
		} else {
			redirect("usercp.php?action=postpresetreplies&error=1&reason=Your group isn't allowed to have preset posts");
		}
	} elseif($mybb->input['action'] == "postpresetreplies_edit")
	{
		if(!isset($mybb->input['title']) or !isset($mybb->input['message'])) {
			if(!isset($mybb->input['id'])) {
			redirect("usercp.php?action=postpresetreplies");
			}
			redirect("usercp.php?action=postpresetreplies&e=".$mybb->input['id']);
		}

		if(strlen($mybb->input['message']) < $mybb->settings['minmessagelength']) {
			redirect("usercp.php?action=postpresetreplies&error=1&reason=Post not met the minimum character of ".$mybb->settings['minmessagelength']);
		}

		if(strlen($mybb->input['message']) > $mybb->settings['postpresetreplies_max_char']) {
			redirect("usercp.php?action=postpresetreplies&error=1&reason=Post exceeded the maximum character of ".$mybb->settings['postpresetreplies_max_char']);
		}

		$insert = array(
			"title" => $db->escape_string($mybb->input['title']),
			"post" => $db->escape_string($mybb->input['message']),
			"set_date" => $db->escape_string(time())
		);
		$db->update_query("postpresetreplies", $insert, "uid='".$mybb->user['uid']."' and id='".$db->escape_string($mybb->input['id'])."'");
		postpresetreplies_update();
		redirect("usercp.php?action=postpresetreplies&error=0");
	}
}

/**

*/
function postpresetreplies_update() {
	global $db,$mybb,$cache;

    $query = $db->simple_select('postpresetreplies',"*","uid='".$mybb->user['uid']."'");
    $replies = array();
    while($list = $db->fetch_array($query))
    {
        $replies[$mybb->user['uid']][$list['id']] = $list;
        unset($replies[$mybb->user['uid']][$list['id']][$list['id']]);
    }
    $cache->update('postpresetreplies', $replies);
}