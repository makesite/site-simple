<?php
class settingsController {

public function update($A) {
	$setting_id = isset($A['setting']) ? $A['setting'] : 0;
	$hints = isset($A['hints']) ? $A['hints'] : '';
	$value = isset($A['value']) ? $A['value'] : 0;
	return do_changesetting($setting_id, $hints, $value); 
}

}

function view_settings() {
	$auth = http_auth();
	if (!$auth) return _view_403();

	$l = html_page();

	$l->assign('settings', Setting::Get(null, null, true) );
	$l->vars['site']['show_settings'] = true;

	$l->out();
	return true;
}

function do_changesetting($setting_id = 0, $hints = '', $value = '') {
	$auth = http_auth();
	if (!$auth) return _view_403();	

	if (!$setting_id) return false;

	//* HACKS */
	if ($setting_id == 'earth_texture') {
		$value = preg_replace('/^\.\.\//', '', $value);	
	}

	$setting = ORM::Collection('Setting', array('name'=>$setting_id))->one();
	//if (!$setting) {$setting = new Setting();$setting->name=$setting_id;}//return false;
	if (!$setting) return false;

	$setting->value = trim($value);
	if (is_string($hints))
		$setting->hints = $hints;
	$setting->save(array('value', 'hints'));

	if (_FORMAT('json'))
		return ajax_success( $setting );

	go_to(get_return_to());
	return true;
}

?>