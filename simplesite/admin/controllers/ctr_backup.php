<?php

class BackupController {

	public function upload() {
		return do_uploadbackup();
	}
	public function create() {
		return do_createbackup();
	}
	public function import($A) {
		$name = array_shift($A);
		return do_importbackup($name);
	}
	public function delete($A) {
		return do__deletebackup($A);
	}
	public function GET_index() {
		return view_backup();
	}

}

function do_uploadbackup() {
	$auth = http_auth();
	if (!$auth) return _view_403();

	$dir = rtrim(load_config('user_backupdir'), '/');

	move_uploaded_file($_FILES['backup']['tmp_name'], $dir.'/'.$_FILES['backup']['name']);

	go_to('backup');
	return true;
}

function do_createbackup() {
	$auth = http_auth();
	if (!$auth) return _view_403();

	$name = load_config('app_name');
	$dir = rtrim(load_config('user_backupdir'), '/');
	$all = ORM::Classes();
	$models = array();
	
	foreach ($all as $one) {
		$x = ORM::Sync($one, true);
		if (!$x[$one]) $models[] = $one;
	}

	$file = ORM::ExportTGZ($models, $name.'-data-$date');

	if ($file) {
		$name = basename($file);
		rename($file, $dir .'/'. $name);
	}

	go_to('backup');
	return true;
}

function do_importbackup($backup) {
	$auth = http_auth();
	if (!$auth) return _view_403();

	$dir = rtrim(load_config('user_backupdir'), '/');

	if (!preg_match('#\.tar\.gz$#', $backup)) {

		ORM::ImportDIR($dir.'/'.$backup);

	} else

	ORM::ImportTGZ($dir.'/'.$backup);

	go_to('backup');
	return true;
}

function do__deletebackup($A) {
	if (!http_auth()) return _view_403();
	if (!isset($A['backup'])) return false;

	$dir = rtrim(load_config('user_backupdir'), '/');

	$name = basename($A['backup']);
	unlink($dir . '/' . $name);

	go_to('backup');
	return true;
}

function view_backup() {
	$auth = http_auth();
	if (!$auth) return _view_403();

	$l = html_page();

	$dir = rtrim(load_config('user_backupdir'), '/') . '/';

	$backups = array();
	$files = glob($dir.'*.tar.gz');
	foreach ($files as $i=>$file) {
		$backups[] = array(
			'name' => preg_replace('#^'.$dir.'#', '', $file),
			'href' => $file,
		);
	}
	$dirs = glob($dir.'*', GLOB_ONLYDIR);
	foreach ($dirs as $file) {
		$backups[] = array(
			'name' => preg_replace('#^'.$dir.'#', '', $file),
			'href' => $file,
		);
	}
	$l->assign('backups', $backups);
	$l->vars['site']['show_backup'] = true;

	$l->out();
	return true;
}

?>
