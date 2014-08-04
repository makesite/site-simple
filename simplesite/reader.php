<?php
##
## AppName
##
ini_set('display_errors', 1);
error_reporting(E_ALL | E_NOTICE | E_STRICT);

# Timezone
date_default_timezone_set("Europe/Moscow");

# Development environment?
if (isset($_SERVER['DEBUG_HOST']))
	define('DEBUG', TRUE);

# Config
include 'admin/config.php';
@include 'admin/config.dev.php';

# System
include 'admin/domtempl.php'; # DOMtempl
include 'admin/dispatch.php'; # Dispatch
include 'admin/form.php';     # Object/Array -> HTML form
include 'admin/db.orm.php';   # DB, ORM
include 'admin/common.php';   # Helpers
# Models
include 'admin/models/files.php'; //<-- should not be used directly, inherit
include 'admin/models/settings.php';
/* add other models here */

# Controllers
//include 'controllers/backup.php'; # Backup Manager
include 'admin/controllers/dbmodels.php'; # Models Manager

# Other controllers ARE RIGHT IN THIS FILE! YEAH.

# Third-party libs
//include 'vendor/phpmarkdownextra/markdown.php';

# Config bind
//ORM::SetMacro('lang', $languages);
File::WORK_DIR($user_workdir);	# Work Dir
BasicDummy::$realm = $app_name . " Realm"; # Auth realm
WAuth::enable('BasicDummy'); # Auth provider

##
## AppName -- Route/Dispatch:
##

try {
	if ($_SERVER['REQUEST_METHOD'] == 'DELETE')
	{
	    $ok = dispatch(_REST(), 'del_');
	    if (!$ok) _view_500();
	}
	else if ($_SERVER['REQUEST_METHOD'] == 'POST')
	{
		$ok = dispatch(_REST(), 'do_');
		if (!$ok) _view_500();
	}
	else
	{
		$found = dispatch(_GET(), 'view_');
	}
} catch (Exception $e) {
	_view_500($e);
};

function figure_language() {
	return 'ru';
}

class BasicDummy {
	static $realm = "AppName Realm";
	static function session_start() {

	}
	static function _init() {
		http_response_code(401);
		header('WWW-Authenticate: Basic realm="'.self::$realm.'"');
		echo 'Access aborted.';
		exit;
	}
	static function _quit() {
		http_response_code(401);
		exit;
	}
	static function _login() {
		if (!isset($_SERVER['PHP_AUTH_USER'])) {
			return -2;
		}
		global $master_login, $master_password;
		$users = array(
			$master_login => $master_password,
		);
		if (defined('DEBUG')) $users[''] = '';
		if (!isset($users[$_SERVER['PHP_AUTH_USER']])
		|| $users[$_SERVER['PHP_AUTH_USER']] !=
			$_SERVER['PHP_AUTH_PW']) {
				return false;
		}
		return true;
	}
	static function forcelogin() {
		$r = self::_login();
		if ($r === -2) self::_init(); // <-- those actually call exit()
		if ($r === false) self::_quit();
		return $r;
	}	
	static function hasPower($power) {
		return self::loggedIN();
	}
	static function forcepower($power) {
		return self::forcelogin();
	}
	static function loggedIN() {
		return self::_login() === true ? true : false;
	}
}

class DigestDummy {
	static $realm = "AppName Realm";
	static function session_start() {

	}
	static function _init() {
		http_response_code(401);
		header('WWW-Authenticate: Digest realm="'.self::$realm.
					'",qop="auth",nonce="'.uniqid().'",opaque="'.md5(self::$realm).'"');
		echo 'Access aborted.';
		exit;
	}
	static function _quit() {
		http_response_code(401);
		exit;
	}
	static function _login() {
		if (empty($_SERVER['PHP_AUTH_DIGEST'])) {
			return -2;
		}
		global $master_login, $master_password;
		$users = array(
			$master_login => $master_password,
		);
		if (!($data = http_digest_parse($_SERVER['PHP_AUTH_DIGEST']))
			|| !isset($users[$data['username']]) )
			return false;
		$password = $users[$data['username']];

		$A1 = md5($data['username'] . ':' . self::$realm . ':' . $password);
		$A2 = md5($_SERVER['REQUEST_METHOD'].':'.$data['uri']);
		$valid_response = md5($A1.':'.$data['nonce'].':'.$data['nc'].':'.$data['cnonce'].':'.$data['qop'].':'.$A2);
		if ($data['response'] != $valid_response) 
			return false;
		return true;
	}
	static function forcelogin() {
		$r = self::_login();
		if ($r === -2) self::_init(); // <-- those actually call exit()
		if ($r === false) self::_quit();
		return $r;
	}
	static function hasPower($power) {
		return self::loggedIN();
	}
	static function forcepower($power) {
		return self::forcelogin();
	}
	static function loggedIN() {
		return self::_login() === true ? true : false;
	}
}

function http_auth() {
	if (!WAuth::forcelogin()) {
		return false;
	}
	return true;
}

function lang_menu() {
	global $languages;
	$lang = figure_language();
	$langs = array();
	foreach ($languages as $title => $code) {
		if (!$code) $code = 'ru';
		$link = $_SERVER['SITE_URL'] . '/'.$code.'/'.$_SERVER['NODE_URI'];
		$langs[] = array(
			'class' => $code . ($lang == $code ? ' active' : ''),
			'title' => $title,
			'href' => $link,
		);
	}
	return $langs;
}

function html_menu($page) {

	$tmp = preg_split('#/#', $_SERVER['NODE_URI'], -1, PREG_SPLIT_NO_EMPTY);
	$url = array_shift($tmp);

	if ($url)
	foreach($page->vars['menu'] as &$item) {
		if (substr($item['href'], 0, strlen($url)) == $url) {
			$item['class'] .= ' active';
		}
		$item['class'] = trim($item['class']);
	}

	return $page->vars['menu'];
}

function html_help($page, $topic) {
	if ($topic === null)
	{
		$tmp = preg_split('#/#', $_SERVER['NODE_URI'], -1, PREG_SPLIT_NO_EMPTY);
		$topic = array_shift($tmp);
	}

	if (!$topic) $topic = 'index';
	$file = 'docs/'.$topic.'.md';
	if (file_exists($file)) {
		$md = file_get_contents($file);
		$html = markdown($md);

		if (preg_match("#\<h1\>(.*?)\</h1\>#", $html, $title)) {
			$topic = $title[1];
			$html = str_replace($title[0], '', $html);
		}

		$page->assign('article', array(
			'class' => 'collapse well help',
			'title' => $topic,
			'body' => $html,
		));
	}
}

function html_page() {
	$page = new DOMtempl('design/index.html');

	$page->assign('meta', array(
		'title' => load_config('app_name') . ' admin',
		'base' => $_SERVER['SITE_URL'],
		'MAX_FILE_SIZE' => max_file_size(),
	));

	if (defined('DEBUG')) {
		global $_debug_logs;
		$page->assign('debug', array(
			'log' => & $_debug_logs, 
		));
	} else $page->assign('debug', false);

	global $languages;
	$page->assign("top-script", 'var lang = '.json_encode($languages).';');

	$lang = figure_language();

	$page->assign('langmenu', lang_menu() );
	$page->assign('menu', html_menu($page) );

	$page->assign('article', false);
	html_help($page, null);

	$page->assign("top-script", 
		'var lang = '.json_encode(load_config('languages')).';'."\n");

	$page->assign('site', array(
		'show_pages' => false,
		'show_projects' => false,
		'show_services' => false,
		'show_partners' => false,
		'show_branches' => false,

		'show_attachments' => false,
		'show_settings' => false,
		'show_backup' => false,
		'show_edit_form' => false,
		'show_edit_form_pictures' => false,
		'show_links' => false,

		'show_breadcrumbs' => false,

		'last_breadcrumb' => false,
		'search_term' => false,
		'page' => 0,
		'include_googlemaps' => false,
	));
/*
	foreach ($page->vars['config'] as $cfg => $val) {
		$page->vars['config'][$cfg] = Setting::Get($cfg, $val);	
	}
*/
	$page->assign('pagination', false);

	return $page;
}

function ajax_failure($data) {
	if (is_string($data)) {
		$data = array(
			'error_message' => $data
		);
	}
	if (defined('DEBUG')) {
		global $_debug_logs;
		$data['debug'] = $_debug_logs;
	}
	//header("Connection: close", '500 Internal Server Error');
	//header("Content-type: application/json");
	echo json_encode(array(
		'result' => 'failure',
		'data' => $data
	));
	return true;
}
function ajax_success($data) {
	//header("Content-type: application/json");
	echo json_encode(array(
		'result' => 'success',
		'data' => $data
	));
	return true;
}

##
## AppName actions
##

function view_index() {
	$auth = http_auth();
	if (!$auth) return _view_403();

	$l = html_page();

	$l->out();
	return true;
}

function _view_403() {
	header("Connection: close", '403 Forbidden');

	if (_FORMAT('json') || isset($_REQUEST['json'])) {
		return ajax_failure( 'Forbidden' );
	}

	$l = html_page();

	$l->assign('article', array(
		'class' => '',
		'title' => 'Error 403',
		'body' => 'Access to <em>'.$_SERVER['NODE_URI'] .'</em> is denied.',
	));

	$l->out();
	return true;
}

function _view_404() {
	header("Connection: close", '404 Not Found');

	if (_FORMAT('json') || isset($_REQUEST['json'])) {
		return ajax_failure( 'Not Found' );
	}

	$l = html_page();

	$l->assign('article', array(
		'class' => '',	
		'title' => 'Error 404',
		'body' => 'Requested resource <em>'.$_SERVER['NODE_URI'] .'</em> was not found.',
	));

	if (defined('DEBUG')) {
		global $_debug_logs;
		$l->vars['article']['body'] .= er($_debug_logs,1);
	} 

	$l->out();
	return true;
}

function _view_500($ex = null) {
	header("Connection: close", '500 Internal Server Error');

	if (_FORMAT('json') || isset($_REQUEST['json'])) {
		return ajax_failure( ($ex ? $ex->getMessage() : 'Internal Server Error') );
	}

	$l = html_page();

	$l->assign('article', array(
		'class' => '',
		'title' => 'Error 500',
		'body' => 'Internal Server Error.',
	));

	if (defined('DEBUG')) {
		global $_debug_logs;
		$l->vars['article']['body'] .= er($_debug_logs,1);
	}

	if ($ex) $l->vars['article']['body'] .= "<pre>".draw_exception($ex, defined('DEBUG'))."</pre>"; 

	$l->out();
	return true;
}

function _raw_404() {
	header("Connection: close", '404 Not found');
	exit;
}

function view_debug($id=55) {
	if (!defined('DEBUG')) return false;

	$res = dispatchEx('controllers_$(VERB)$(FORMAT)/$(ARG)*?/ctr_#$(ARG)#.php>>$(ARG){0,1}->$(VERB)#_$(ARG.0)%%$(ARG)*', _REST());

	global $_debug_logs;
	er($_debug_logs);
	
	echo "Result: ";
	var_dump($res);

	return true;
}

##
## AppName views
##

function activate($elems, $one, $property = 'id', $class = 'active') {
	foreach ($elems as $_elem) {
		if (!isset($_elem->class)) $_elem->class = false;
		if ((is_object($one) && $one->$property == $_elem->$property)
		|| (!is_object($one) && $one == $_elem->$property))
			$_elem->class = 
				($_elem->class ? $_elem->class . ' ' : '') . $class;
	}
}

function bootstrap_formalize($object, $action, $method, $errors, $extra_html = array()) {
	$method = '@'.$method;

	if ($action === null) $action = 'edit'.strtolower(get_class($object));

	$fields = formObject($object, $errors);

	$opts = array();

	$form = new WForm($fields, $action, $method, $extra_html);

	$form->form_id = 'edit_form';
	$form->form_class = 'form-horizontal';
	$form->label_class="control-label";
	$form->input_class = 'form-control';
	$form->row_header = '<li class="form-group">';

	foreach ($opts as $key=>$val) $form->$key = $val;
	return $form->render();
}

?>