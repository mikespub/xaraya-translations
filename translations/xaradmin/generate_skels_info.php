<?php

/**
 * File: $Id$
 *
 * Generate skels information
 *
 * @package modules
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage translations
 * @author Marco Canini
 * @author Marcel van der Boom <marcel@xaraya.com>
*/

function translations_admin_generate_skels_info()
{
    // Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    $druidbar = translations_create_generate_skels_druidbar(INFO);
    $opbar = translations_create_opbar(GEN_SKELS);
    $tplData = array_merge($druidbar, $opbar);
    $tran_type = xarSessionGetVar('translations_dnType');
    $tplData['dnType'] = translations__dnType2Name($tran_type);

    return $tplData;
}

?>