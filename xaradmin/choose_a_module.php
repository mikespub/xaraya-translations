<?php
/**
 * Choose a module page generation
 *
 * @package modules
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage translations
 * @author Marco Canini
 * @author Marcel van der Boom <marcel@xaraya.com>
*/

function translations_admin_choose_a_module()
{
    // Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    if (!($installed = xarModAPIFunc('modules', 'admin', 'getlist', array('filter' => array('State' => XARMOD_STATE_INSTALLED))))) return;
    if (!($uninstalled = xarModAPIFunc('modules', 'admin', 'getlist', array('filter' => array('State' => XARMOD_STATE_UNINITIALISED))))) return;
    $modlist1 = array();
    foreach($uninstalled as $term) $modlist1[$term['name']] = $term;
    $modlist2 = array();
    foreach($installed as $term) $modlist2[$term['name']] = $term;
    $modlist = array_merge($modlist1,$modlist2);
    ksort($modlist);
    
    $tplData = translations_create_druidbar(CHOOSE, XARMLS_DNTYPE_MODULE, '', 0);
    $tplData['modlist'] = $modlist;
    $tplData['dnType'] = XARMLS_DNTYPE_MODULE;
    return $tplData;
}

?>