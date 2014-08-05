<?php

function do__updatepagepicture($A) {
	return _do_update_generic($A, 'PagePicture', 'id', array('title'), null);
}

function do__createpagepicture($A) {
	$auth = http_auth();
	if (!$auth) return _view_403();	

	if (!isset($A['page_id']))
		throw new Exception("Undefined Page.");

	$Page = ORM::Model('Page', $A['page_id'], 1);
	if (!$Page) throw new Exception("Unrecognized Page.");

	if ($A['title']) $_FILES['image']['title'] = $A['title']; // use title as fake imagefilename, for db
	else { //replace file extension
		$name = $_FILES['image']['name'];
		$ext = File::file_extension($name);
		$name = substr($name, 0, strlen($name) - strlen($ext) - 1); 
		$_FILES['image']['title'] = $name;
	}

	$picture = new PagePicture();
	//$picture->is_raunchy = 1;//
	$ok = $picture->upload($_FILES['image'], 'page/'.$A['page_id']);
	if (is_object($ok)) $picture = $ok;

	if (!$ok) throw new Exception($picture->LAST_ERROR);

	$picture->Page = $Page;
	//$picture->is_raunchy = 1;

	$size = $picture->imageSize();
	if (!$size) throw new Exception("Uploaded file is not an image.");

	//$picture->width = $size[0];
	//$picture->height = $size[1];
	$picture->priority = $Page->pictures->count();

	$ok = $picture->save(array('page_id', 'priority'));
//	$ok = $picture->save();

	if (_FORMAT('json') || isset($_REQUEST['json'])) 
		return ajax_success( $picture );	

	go_to('page/' . $page->id() );
	return true;
}

function do__orderpagepictures($A) {
	if (!http_auth()) return _view_403();
	if (!isset($A['page_id']))
		throw new Exception("Undefined Page.");
	return _do_order_generic('pagepicture', $A, 'page/' . $A['page_id'], array('page_id'=>$A['page_id']), 'order', 'priority', 2);
}


?>