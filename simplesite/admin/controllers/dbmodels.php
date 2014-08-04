<?php

//ORM::SetMacro('lang', load_config('languages'));

function do_dbsync($class) {
	ORM::Sync($class);
	go_to('dbmodels');
}

function do_dbwipe($class) {
	ORM::FixClear($class);
	go_to('dbmodels');
}

function do_dbdrop($class) {
	ORM::Destroy($class);
	go_to('dbmodels');
}

function do_dbfix($class) {
	$xml = file_get_contents('backups/'.$class.'.xml');
	$c = ORM::Collection($class, null, false);
	$c->loadFromXml($xml);
	$c->save();
	go_to('dbmodels');
}

function view_dbmodels() {
	$l = html_page();

	$models = ORM::Classes();

	$fixdir = load_config('user_backupdir');

	$links = array();
	foreach ($models as $name) {
		
		$synced = false;
		try {
			$sql = ORM::Sync($name, true);
			$r = $sql[$name];
			$synced = !sizeof($r);
		}
		catch (Exception $e) {
			$synced = false;
		}

		$has_fixture = file_exists($fixdir . '/' . $name . '.xml');
		if (!$has_fixture)
			_debug_log('Fixture file "' . $fixdir . '/' . $name . '.xml' . '" not found');		

		$classes = array(
			'form' => 'pull-left',
			'button' => 'btn btn-sm btn-danger',
		);
		$classes3 = array(
			'form' => 'pull-left',
			'button' => 'btn btn-sm btn-warning',
		);
		$classes2 = array(
			'form' => 'pull-left',
			'button' => 'btn btn-sm btn-default',
		);
		$classes4 = array(
			'form' => 'pull-left',
			'button' => 'btn btn-sm btn-primary',
		);
		$hiddens = array('class'=>$name);
		$links[] = array(
			'title' => $name,
			'class' => (!$synced ? 'danger' : 'success'),
			'html' => ''
				.(!$synced ? mini_form('dbsync', 'Sync <span class="glyphicon glyphicon-refresh"></span>', $hiddens, $classes2) : '' )
				.($has_fixture ? mini_form('dbfix', 'Fixture <span class="glyphicon glyphicon-plus"></span>', $hiddens, $classes4) : '' )				
				.($synced ? mini_form('dbwipe', 'Wipe <span class="glyphicon glyphicon-trash"></span>', $hiddens, $classes3) : '' )
				.($synced ? mini_form('dbdrop', 'Drop <span class="glyphicon glyphicon-remove"></span>', $hiddens, $classes) : '' )
			,
			'adminHref' => '',
		);
	}

	$l->vars['site']['show_links'] = true;
	$l->assign('links', $links);

	$l->assign('site.form', array(
		'link_header' => "Models",
		'link_ids' => array('/links/title'),
		'link_fields' => array(
			'title' => array(
				'type' => 'text',
				'placeholder' => 'Рассылка',
				'required' => false,
				'value' => '/links/title',
			),
		),
	));

	$l->out();
	return true;
}

?>