<?php

class AttachmentsController {

public function GET_list() {
	$auth = http_auth();
	if (!$auth) return _view_403();

	$l = html_page();

	$attachments = ORM::Collection('Attachment');

	$l->assign('attachments', $attachments);
	$l->vars['site']['show_attachments'] = true;

	$l->out();
	return true;
}

public function upload($A) {
	$auth = http_auth();
	if (!$auth) return _view_403();

	if ($A['title']) $_FILES['file']['title'] = $A['title']; // use title as fake imagefilename, for db
	else { //replace file extension
		$name = $_FILES['file']['name'];
		$ext = File::file_extension($name);
		$name = substr($name, 0, strlen($name) - strlen($ext) - 1);
		$_FILES['file']['title'] = $name;
	}

	$attachment = new Attachment();
	$ok = $attachment->upload($_FILES['file'], 'attachments');
	if (is_object($ok)) $attachment = $ok;

	if (!$ok) throw new Exception($attachment->LAST_ERROR);

	if (_FORMAT('json') || isset($_REQUEST['json']))
		return ajax_success( $attachment );

	go_to('attachments/');
	return true;
}

}

function view_attaches() {
	$auth = http_auth();
	if (!$auth) return _view_403();

	$l = html_page();

	$attachments = ORM::Collection('Attachment');

	$l->assign('attachments', $attachments);
	$l->vars['site']['show_attachments'] = true;

	$l->out();
	return true;
}

function do__createattachment($A) {
	$auth = http_auth();
	if (!$auth) return _view_403();

	if ($A['title']) $_FILES['file']['title'] = $A['title']; // use title as fake imagefilename, for db
	else { //replace file extension
		$name = $_FILES['file']['name'];
		$ext = File::file_extension($name);
		$name = substr($name, 0, strlen($name) - strlen($ext) - 1);
		$_FILES['file']['title'] = $name;
	}

	$attachment = new Attachment();
	$ok = $attachment->upload($_FILES['file'], 'attachments');
	if (is_object($ok)) $attachment = $ok;

	if (!$ok) throw new Exception($attachment->LAST_ERROR);

	if (_FORMAT('json') || isset($_REQUEST['json']))
		return ajax_success( $attachment );

	go_to('attachments/');
	return true;
}


?>