<?php

/**
 * File: $Id$
 *
 * Short description of purpose of file
 *
 * @package modules
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 * 
 * @subpackage translations
 * @author Marcel van der Boom <marcel@xaraya.com>
*/


function translations_admin_modifyconfig()
{

    $locales = xarMLSListSiteLocales();
    $i = 0; $j = 0;
    foreach($locales as $locale) {
        $data['locales'][] = $locale; $i++;
    }
    $data['localeslist'] = '';
    foreach($locales as $locale) {
        $data['localeslist'] .= $locale; 
        $j++;
        if ($j < $i) $data['localeslist'] .= ',';
    }
    
    $data['translationsBackend'] = xarConfigGetVar('Site.MLS.TranslationsBackend');
    
    $data['authid'] = xarSecGenAuthKey();
    return $data;

}