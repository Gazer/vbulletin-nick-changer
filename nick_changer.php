<?php
/*=====================================*\
|| ################################### ||
|| # Nick Changer        version 0.1 # ||
|| ################################### ||
\*=====================================*/

// ####################### SET PHP ENVIRONMENT ###########################
error_reporting(E_ALL & ~E_NOTICE);

// #################### DEFINE IMPORTANT CONSTANTS #######################
define('THIS_SCRIPT', 'nick_changer');

// ################### PRE-CACHE TEMPLATES AND DATA ######################
// get special phrase groups
$phrasegroups = array('user', 'timezone', 'posting', 'cprofilefield', 'cppermission');

// get special data templates from the datastore
$specialtemplates = array();

// pre-cache templates used by all actions
$globaltemplates = array(
        'USERCP_SHELL',
        'usercp_nav_folderbit'
);

// pre-cache templates used by specific actions
$actiontemplates = array();

// ######################### REQUIRE BACK-END ############################
require_once('./global.php');
require_once(DIR . '/includes/functions_user.php');
require_once(DIR . '/includes/functions_nick_changer.php');

// #######################################################################
// ######################## START MAIN SCRIPT ############################
// #######################################################################
$shelltemplatename = 'USERCP_SHELL';
$templatename = 'nick_changer';

// start the navbar
$navbits = array('usercp.php' . $vbulletin->session->vars['sessionurl_q'] => $vbphrase['user_control_panel']);

($hook = vBulletinHook::fetch_hook('profile_start')) ? eval($hook) : false;

$vbulletin->input->clean_array_gpc('r', array(
	'using_ajax'  => TYPE_UINT,
  'username'    => TYPE_STR
));

$using_ajax = $vbulletin->GPC['using_ajax'];
$username = $vbulletin->GPC['username'];

if (!isset($_REQUEST['do'])) {
  $_REQUEST['do'] = 'chance_username';
}

if ($_REQUEST['do'] == 'checkavailability') {
  $result = $db->query_first("SELECT count(1) as total FROM " . TABLE_PREFIX . "user WHERE LOWER(username) = '" . $db->escape_string(strtolower($username)). "' AND userid != " . $vbulletin->userinfo['userid']. ";");

  $exists = ($result['total'] > 0) ? true : false;

	if ($using_ajax)
	{
	  if ($exists) {
	    $template = 'nick_changer_check_error';
	  } else {
	    $template = 'nick_changer_check_ok';
    }
    eval('$echo = "' . fetch_template($template) . '";');
    echo $echo;
		exit;
	}	else {
	  # Redirect back to the post
		$vbulletin->url = "nick_changer.php";
		eval(print_standard_redirect('redirect_nick_changer_already_registered'));
	}
}

if ($_REQUEST['do'] == 'chance_username') {
  // draw cp nav bar
  construct_usercp_nav('nick_changer');

  $navbits = construct_navbits($navbits);
  eval('$navbar = "' . fetch_template('navbar') . '";');
  
  ($hook = vBulletinHook::fetch_hook('profile_complete')) ? eval($hook) : false;
  
  eval('$template_hook["usercp_navbar_bottom"] .= " ' . fetch_template('nick_changer_usercp') . '";');

  if (nick_changer_can_change_username($vbulletin->userinfo)) {
    eval('$HTML = "' . fetch_template('nick_changer_form') . '";');
  } else {
    if ($vbulletin->options['nick_changer_on_off'] == 1) {
      $days = ceil(($vbulletin->options['nick_changer_days']*86400 - (TIMENOW - $vbulletin->userinfo['nick_changer_last_change']))/86400);
      $days = construct_phrase($vbphrase['nick_changer_no_permission_days'], $days);
    } else {
      $days = "";
    }
    eval('$HTML = "' . fetch_template('nick_changer_no_permission') . '";');
  }
  eval('print_output("' . fetch_template($shelltemplatename) . '");');
}

if ($_REQUEST['do'] == 'dochance_username') {
  $vbulletin->input->clean_array_gpc('p', array('username' => TYPE_STR, "username_confirmation" => TYPE_UINT));

  if (!$vbulletin->GPC['username_confirmation']) {
		$vbulletin->url = "nick_changer.php";
		eval(standard_error(fetch_error('nick_changer_not_confirmed', $vbphrase['nick_changer_username_confirmation'])));
    exit(0);
  }

  if (nick_changer_can_change_username($vbulletin->userinfo) || empty($vbulletin->GPC['username'])) {
    $errors = nick_changer_change_username($vbulletin->userinfo, $vbulletin->GPC['username']);

    if ($errors !== true) {
      if (is_array($errors)) {
        $errorlist = '<ul>';
        foreach ($errors AS $index => $error)
        {
                $errorlist .= "<li>$error</li>";
        }
        $errorlist .= "</ul>";
        eval(standard_error(fetch_error('nick_changer_errors', $vbulletin->GPC['username'], $errorlist)));
      } else if ($errors === false) {
        eval(standard_error(fetch_error('nick_changer_not_changed', $vbulletin->GPC['username'])));
      }
    } else {
      eval(standard_error(fetch_error('nick_changer_change_done', $vbulletin->GPC['username'])));
    }
  } else {
    // TODO : Dar un mensaje mas amigable al usuario
    print_no_permission();
  }
}

?>