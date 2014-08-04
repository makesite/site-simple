<?php

$master_login    = 'admin';
$master_password = 'FILLME';

$user_workdir = 'files';

$languages = array(
	'Russian' => '',
	'English' => 'en',
	'Chinese' => 'ch',
);

$default_language = 'ru';

$language_method = 'url'; // url | host

$app_name = "KeyGroup";
$user_backupdir = 'backups';

$deep_pages = false;

/* mysql config is in db.conf.php! */

/* APP DIR */
if (!defined('APP_DIR')) define('APP_DIR', __DIR__);

if (!defined('CORE_DIR')) define('CORE_DIR', constant('APP_DIR'));


?>