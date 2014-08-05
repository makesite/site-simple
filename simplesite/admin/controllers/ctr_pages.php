<?php

class Page extends DeepPage { }

function gen_breadcrumbs($page, &$last) {
	//if ($page->parent_id == 0) return array();	
	$path = array();

	$skip = 15;
	while ($page) {
		$path[] = array(
			'title' => $page->title,
			'href' => $page->adminHref,
		);
		$page = $page->parent;
		$skip--;
		if ($skip <= 0) break;
	}

	$path = array_reverse($path);

	$apop = array_pop($path);
	$last = $apop['title'];

	return $path;
}

function view_pages() {
	$auth = http_auth();
	if (!$auth) return _view_403();

	$view = html_page();

	$filter = array('parent_id' => 0);

	$pages = ORM::Collection('Page', $filter, false);
	$max_elems = $pages->count();

	$pages->order_by('priority');
	$pages->load();

	foreach ($pages as $page) {
		$page->subpages->order_by('priority');
		$page->subpages->load();
	}

	$view->vars['site']['show_pages'] = true;
	$view->assign('pages', $pages);
	
	$view->assign('site.form', array(
		'parent_id' => 0,
	));

	//$l->assign('pagination', draw_pages('pages/', $page, $per_page, $max_elems, 1) );

	$view->out();
	return true;
}

function view_page($id = null) {
	$auth = http_auth();
	if (!$auth) return _view_403();
	if (!$id) return false;

	$l = html_page();

	$page = ORM::Model('Page', $id);
	if (!$page) return false;

	$page->pictures->order_by('priority');
	$page->pictures->load();

	$page->html = bootstrap_formalize($page, 'pages/update', 'POST', array(), 
		array('main' =>
			'<footer class="fixed-float well">' .
			'<button formaction="pages/update" type="submit">'."Save".'</button>' .
			'<button formaction="pages/delete" type="submit">'."Delete".'</button>' . 
			'</footer>',
		)
	);

	$l->vars['site']['show_edit_form'] = true;
	$l->vars['site']['show_edit_form_pictures'] = true;
	$l->assign('site.form', array(
		'order_pictures' => 'order_pagepictures',
		'create_picture' => 'create_pagepicture',
		'star_button' => true,
		'heart_button' => false,

		'picture_owner' => 'page',

		'parent_id' => $page->id(),
	));

	$l->assign('item', $page);

	$sub_pages = true;
	
	if ($sub_pages == true)
	{
	/* Sub-pages */
	$page->subpages->order_by('priority');
	$page->subpages->load();
	/* And sub-sub pages */
	foreach ($page->subpages as $subpage) {
		$subpage->subpages->order_by('priority');
		$subpage->subpages->load();
	}
	$l->vars['site']['show_pages'] = true;
	$l->assign('pages', $page->subpages);

	/* Breadcrumbs */
	$last_bread = false;
	$l->assign('breadcrumbs', gen_breadcrumbs($page, $last_bread));
	$l->vars['site']['last_breadcrumb'] = $last_bread;
	$l->vars['site']['show_breadcrumbs'] = true;
	}

	/* Done */
	$l->out();
	return true;
}

function do__createpage($A) {
	$auth = http_auth();
	if (!$auth) return _view_403();	

	$page = new Page();
	fillObject($page, $A);

	$filter = null;	
	if (isset($A['parent_id'])) {
		$page->parent = new stdClass();
		$page->parent->id = $A['parent_id'];
		$filter = array('parent_id' => $A['parent_id']); 
	}

	$page->priority = ORM::Collection('Page', $filter, false)->count();
	$page->published = 1;
	$page->onmenu = 1;
	$page->save();

	go_to('page/'.$page->id());
	return true;
}

function do_publishpage($A) {
	if (!http_auth()) return _view_403();
	$parent = new CRUD_controller('Page');
	return $parent->quickupdate_generic($A, 'published', 1, 'published', 'pages/unpublish');	
}
function do_unpublishpage($A) {
	if (!http_auth()) return _view_403();
	$parent = new CRUD_controller('Page');
	return $parent->quickupdate_generic($A, 'published', 0, 'unpublished', 'pages/publish');	
}

function do_promotepage($A) {
	if (!http_auth()) return _view_403();
	$parent = new CRUD_controller('Page');
	return $parent->quickupdate_generic($A, 'onmenu', 1, 'promoted', 'pages/demote');	
}
function do_demotepage($A) {
	if (!http_auth()) return _view_403();
	$parent = new CRUD_controller('Page');
	return $parent->quickupdate_generic($A, 'onmenu', 0, 'demoted', 'pages/promote');	
}



?>