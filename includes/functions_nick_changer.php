<?php
/*=====================================*\
|| ################################### ||
|| # Nick Changer        version 0.1 # ||
|| ################################### ||
\*=====================================*/

require_once(DIR . '/includes/functions.php');
require_once(DIR . '/includes/functions_user.php');
require_once(DIR . '/includes/functions_misc.php');

define('DAYS_TO_SECONDS', 86400);

function fetch_usernames_for_user($userid)
{
  global $vbulletin;

  $db =& $vbulletin->db;

  $usernames = $db->query_read("SELECT distinct(username) FROM " . TABLE_PREFIX . "nick_changer_usernames WHERE userid = $userid ORDER BY dateline DESC");
  $ret = array();
  while ($username = $db->fetch_array($usernames)) {
    $ret[] = $username['username'];
  }
  $db->free_result($usernames);

  return $ret;
}

function nick_changer_can_change_username($userinfo)
{
  global $vbulletin;


  if ($vbulletin->options["nick_changer_on_off"] == 0) return false;

  $timespan = TIMENOW - $vbulletin->options["nick_changer_days"] * DAYS_TO_SECONDS;

  if ($userinfo['nick_changer_last_change'] > $timespan) return false;

  return true;
}

function nick_changer_change_username($userinfo, $username)
{
  global $vbulletin;

  if (!nick_changer_can_change_username($userinfo)) return false;

  if ($userinfo['username'] != $username) {
    $old_username = $userinfo['username'];

    $dataman =& datamanager_init('User', $vbulletin, ERRTYPE_ARRAY);
  	$dataman->set_existing($userinfo);

	  // If this is the first post, we close the thread as "Moderated"
	  $dataman->set('nick_changer_last_change', TIMENOW);
	  $dataman->set('username', $username);
  	$dataman->pre_save();
	  
    if (!empty($dataman->errors))
    {
      return $dataman->errors;
    } else {
  	  $dataman->save();
  	  
  	  $vbulletin->db->query_write("INSERT INTO `" . TABLE_PREFIX . "nick_changer_usernames` (userid, username, dateline) 
  	  VALUES (
  	  " . $userinfo['userid'] . ",
  	  '" . $vbulletin->db->escape_string($old_username) . "',
  	  " . TIMENOW . "
  	  );
  	  ");
  	  
  	  nick_changer_create_usernote($old_username);
	  }
  } else {
    return false;
  }

  return true;
}

function nick_changer_create_usernote($old_username)
{
  global $vbulletin, $vbphrase;

  eval('$message = "' . fetch_template('nick_changer_usernote') . '";');

  $vbulletin->db->query_write("
	  INSERT INTO " . TABLE_PREFIX . "usernote (message, dateline, userid, posterid, title, allowsmilies)
	  VALUES ('" . $vbulletin->db->escape_string($message) . "', " . 
	  TIMENOW . ", " . $vbulletin->userinfo['userid'] . ", " . $vbulletin->options['nick_changer_usernote_posterid'] . ", '" . $vbulletin->db->escape_string($vbulletin->options['nick_changer_usernote_title']) . "', 0)
  ");
}

?>