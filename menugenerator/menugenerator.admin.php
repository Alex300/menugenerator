<?php
/* ====================
[BEGIN_COT_EXT]
Hooks=tools
[END_COT_EXT]
==================== */

/**
 * Menu Generator for Cotonti CMF
 *
 * @author esclkm, http://www.littledev.ru, Kalnov Alexey <kalnovalexey@yandex.ru>
 * @copyright (c) esclkm http://www.littledev.ru, 2011-2021 Lily Software https://lily-software.com (ex. Portal30 Studio)
 */

defined('COT_CODE') or die('Wrong URL.');

require_once(cot_langfile('menugenerator'));
require_once cot_incfile('forms');

$item_id = cot_import('item_id', 'P', 'ARR');
$item_title = cot_import('item_title', 'P', 'ARR');
$item_href = cot_import('item_href', 'P', 'ARR');
$item_extra = cot_import('item_extra', 'P', 'ARR');
$item_path = cot_import('item_path', 'P', 'ARR');
$item_users = cot_import('item_users', 'P', 'ARR');
$item_desc = cot_import('item_desc', 'P', 'ARR');

foreach (cot::$extrafields[$db_menugenerator] as $rowex) {
	if ($rowex['field_type'] != 'file' || $rowex['field_type'] != 'filesize') {
		$item_extrafieldsarr[$rowex['field_name']] = cot_import('item_'.$rowex['field_name'], 'P', 'ARR');

	} elseif($rowex['field_type'] == 'file') {
		// TODO FIXME!
		//$item_extrafieldsarr[$rowex['field_name']] = cot_import_filesarray('rstructure'.$rowex['field_name']);
	}
}

if (count($item_id)) {
	foreach ($item_id as $key => $val) {
		$menu['mg_title'] = cot_import($item_title[$key], 'D', 'TXT');
		$menu['mg_path'] = cot_import($item_path[$key], 'D', 'TXT');
		$menu['mg_href'] = cot_import($item_href[$key], 'D', 'TXT');
		$menu['mg_extra'] = cot_import($item_extra[$key], 'D', 'TXT');
		$menu['mg_users'] = cot_import($item_users[$key], 'D', 'TXT');
		$menu['mg_desc'] = cot_import($item_desc[$key], 'D', 'TXT');

		foreach (cot::$extrafields[$db_menugenerator] as $i => $rowex) {
		    $menu['mg_'.$rowex['field_name']] = cot_import_extrafields($item_extrafieldsarr[$rowex['field_name']][$i],
                                                                           $rowex, 'D', '');
		}

		if ($val == 'new' && !empty($item_title[$key])) {
			cot::$db->insert($db_menugenerator, $menu);

        } else {
			if(!empty($item_title[$key])) {
				cot::$db->update($db_menugenerator, $menu, "mg_id='".(int)$val."'");

            } else {
				cot::$db->delete($db_menugenerator, "mg_id='".(int)$val."'");
			}
		}
	}

}
//ЧИТАЕМ ТАБЛИЦУ
$local_max = 0;
$mg_set[] = 'GENERAL';
$mg_menuarray = array();
cot_read_sqltable();

// МЕНЮ ГЕНИРИРУЕТСЯ СРАЗУ ПРИ ЗАПУСКЕ СТРАНИЦЫ.

// делал микулик
//создаем меню.
// запускаем генератор
// Читаем всписки менюшек.
cot::$cache && cot::$cache->db->remove('mg_menus', 'system');

$sskin = cot_tplfile('menugenerator.admin', 'plug');
$tt = new XTemplate($sskin);
if (count($mg_menuarray)) {
	cot_build_table('');
}

function cot_build_table($parent = '', $level = 0)
{
	global $tt, $mg_menuarray, $cot_extrafields, $db_menugenerator;

	$level++;
	$level_case = '';
	for ($j = 1; $j < $level; $j++) {
		$level_case .= '&nbsp; &nbsp; &nbsp; ';
	}
	$parent = ($parent == '') ? 'GENERAL' : $parent;
	foreach ($mg_menuarray[$parent] as $key => $row) {
		$qid = $row['mg_id'];

		$tt->assign(array(
			'MENU_ITEM_TITLE' => cot_inputbox('hidden', 'item_id['.$qid.']', $row['mg_id']).cot_inputbox('text', 'item_title['.$qid.']', $row['mg_title']),
			'MENU_ITEM_HREF' => cot_inputbox('text', 'item_href['.$qid.']', $row['mg_href'], 'size="32"'),
			'MENU_ITEM_EXTRA' => cot_inputbox('text', 'item_extra['.$qid.']', $row['mg_extra'], 'size="16"'),
			'MENU_ITEM_DESC' => cot_inputbox('text', 'item_desc['.$qid.']', $row['mg_desc'], 'size="18"'),
			'MENU_ITEM_PATH' => cot_inputbox('text', 'item_path['.$qid.']', $row['mg_path'], 'size="8"'),
			'MENU_ITEM_USERS' => cot_inputbox('text', 'item_users['.$qid.']', $row['mg_users'], 'size="12"'),
			'MENU_ITEM_ID' => $qid,
			'MENU_ITEM_LEVELC' => $level_case,
			'MENU_ITEM_LEVEL' => $level,
		));
		
		// Extra fields
		foreach(cot::$extrafields[$db_menugenerator] as $i => $rowex) {
			$uname = strtoupper($rowex['field_name']);
			$t->assign('MENU_ITEM_'.$uname, cot_build_extrafields('item_'.$rowex['field_name'].'['.$qid.']', $rowex, $row[$rowex['field_name']]));
			$t->assign('MENU_ITEM_'.$uname.'_TITLE', isset(cot::$L['menugenerator_'.$rowex['field_name'].'_title']) ?  cot::$L['menugenerator_'.$rowex['field_name'].'_title'] : $rowex['field_description']);
		}
		
		$tt->parse('MAIN.GENERAL.ITEMS');

		if (isset($mg_menuarray[$row['mg_path']])) {
			cot_build_table($row['mg_path'], $level);
		}
	}
}

$tt->assign(array(
	'MENU_ITEM_TITLE' => cot_inputbox('hidden', 'item_id[]', 'new').cot_inputbox('text', 'item_title[]', ''),
	'MENU_ITEM_HREF' =>  cot_inputbox('text', 'item_href[]', '#', 'size="32"'),
	'MENU_ITEM_EXTRA' => cot_inputbox('text', 'item_extra[]', '', 'size="16"'),
	'MENU_ITEM_DESC' => cot_inputbox('text', 'item_desc[]', '', 'size="18"'),
	'MENU_ITEM_PATH' => cot_inputbox('text', 'item_path[]', '', 'size="8"'),
	'MENU_ITEM_USERS' => cot_inputbox('text', 'item_users[]', '', 'size="12"'),
	'MENU_ITEM_ID' => 'new',
	'MENU_ITEM_LEVELC' => '',
	'MENU_ITEM_LEVEL' => 0,
));
// Extra fields
foreach(cot::$extrafields[$db_menugenerator] as $i => $rowex) {
	$uname = strtoupper($rowex['field_name']);
	$t->assign('MENU_ITEM_'.$uname, cot_build_extrafields('item_'.$rowex['field_name'].'[]', $rowex, ''));
	$t->assign('MENU_ITEM_'.$uname.'_TITLE', isset(cot::$L['menugenerator_'.$rowex['field_name'].'_title']) ?  cot::$L['menugenerator_'.$rowex['field_name'].'_title'] : $rowex['field_description']);
}
		
$tt->parse('MAIN.GENERAL.ITEMS');
$tt->assign('MENU_FORMACTION', cot_url('admin', 'm=other&p=menugenerator'));
$textmenugenerator = '';
if (count($mg_set)){
	foreach ($mg_set as $key => $val) {
		if ($textmenugenerator != '') $textmenugenerator.=", ";
		$textmenugenerator .= '{PHP.MENUGENERATOR.'.mb_strtoupper($val).'}';
	}
}
$tt->assign('MENU_MENU_SET', $textmenugenerator);
$tt->parse('MAIN.GENERAL');
$tt->parse('MAIN.HELP');

$tt->parse('MAIN');
$pluginBody = $tt->text('MAIN');