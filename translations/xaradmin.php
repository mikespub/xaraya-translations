<?php
// File: $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file: translations admin GUI
// ----------------------------------------------------------------------

define('CHOOSE', -1);
define('INFO', 0);
define('GEN', 1);
define('TRAN',2);
define('REL', 2);
define('DOWNLOAD', 3);

define('OVERVIEW', 0);
define('GEN_SKELS', 1);
define('TRANSLATE', 2);
define('GEN_TRANS', 3);
define('RELEASE', 4);

/* EVENT */function translations_adminevt_OnModLoad($args)
{
    if (xarMLSGetMode() != XARMLS_UNBOXED_MULTI_LANGUAGE_MODE) {
        $msg = xarML('To execute the translations module you must set the Multi Language System mode to UNBOXED.');
        xarExceptionSet(XAR_USER_EXCEPTION, 'WrongMLSMode', new DefaultUserException($msg));
        return;
    }
    xarTplSetPageTitle(xarML('Welcome to translators\' paradise!'));

}

/* FUNC */function translations_admin_update_working_locale()
{
    // Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    if (!xarVarFetch('locale', 'str:1:', $locale)) return;
    translations_working_locale($locale);
    translations_release_locale($locale);
    xarResponseRedirect(xarModURL('translations', 'admin','start'));
}

/* FUNC */function translations_admin_update_release_locale()
{
// Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    if (!xarVarFetch('locale', 'str:1:', $locale)) return;
    translations_release_locale($locale);
    xarResponseRedirect(xarModURL('translations', 'admin', 'generate_trans_info'));
}



/* FUNC */function translations_admin_core_overview()
{
// Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    xarSessionSetVar('translations_dnName', 'xaraya');

    $tplData = translations_create_opbar(OVERVIEW);
    $tplData['verNum'] = XARCORE_VERSION_NUM;
    $tplData['verId'] = XARCORE_VERSION_ID;
    $tplData['verSub'] = XARCORE_VERSION_SUB;
    return $tplData;
}

/* FUNC */function translations_admin_choose_a_module()
{
// Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    if (!($modlist = xarModAPIFunc('modules', 'admin', 'GetList'))) return;

    $tplData = translations_create_choose_a_module_druidbar(CHOOSE);
    $tplData['modlist'] = $modlist;
    return $tplData;
}

/* FUNC */function translations_admin_choose_a_theme()
{
// Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    if (!($themelist = xarModAPIFunc('themes','admin','GetThemeList',array()))) return;

    $tplData = translations_create_choose_a_theme_druidbar(CHOOSE);
    $tplData['themelist'] = $themelist;
    return $tplData;
}

/* FUNC */function translations_admin_module_overview()
{
// Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    $sessmodid = xarSessionGetVar('translations_modid');
    if (!xarVarFetch('modid', 'id', $modid, $sessmodid)) return;
    xarSessionSetVar('translations_modid', $modid);

    if (!($tplData = xarModGetInfo($modid))) return;

    xarSessionSetVar('translations_dnName', $tplData['name']);

    $druidbar = translations_create_choose_a_module_druidbar(OVERVIEW);
    $opbar = translations_create_opbar(OVERVIEW);
    $tplData = array_merge($tplData, $druidbar, $opbar);

    return $tplData;
}

/* FUNC */function translations_admin_theme_overview()
{
// Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    $sessthemeid = xarSessionGetVar('translations_themeid');
    if (!xarVarFetch('themeid', 'id', $themeid, $sessthemeid)) return;
    xarSessionSetVar('translations_themeid', $themeid);

    if (!($tplData = xarThemeGetInfo($themeid))) return;

    xarSessionSetVar('translations_dnName', $tplData['name']);

    $druidbar = translations_create_choose_a_theme_druidbar(OVERVIEW);
    $opbar = translations_create_opbar(OVERVIEW);
    $tplData = array_merge($tplData, $druidbar, $opbar);

    return $tplData;
}

/* FUNC */function translations_admin_generate_skels_info()
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

/* FUNC */function translations_admin_generate_skels()
{

    $dnType = xarSessionGetVar('translations_dnType');
    $locale = translations_working_locale();
    $args = array('locale'=>$locale);
    switch ($dnType) {
        case XARMLS_DNTYPE_CORE:
        $res = xarModAPIFunc('translations','admin','generate_core_skels',$args);
        break;
        case XARMLS_DNTYPE_MODULE:
        $args['modid'] = xarSessionGetVar('translations_modid');
        $res = xarModAPIFunc('translations','admin','generate_module_skels',$args);
        break;
    }
    if (!isset($res)) return;

    xarSessionSetVar('translations_result', $res);
    xarResponseRedirect(xarModURL('translations', 'admin', 'generate_skels_result'));
}

/* FUNC */function translations_admin_generate_skels_result()
{
// Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    $tplData = xarSessionGetVar('translations_result');
    if ($tplData == NULL) {
        xarResponseRedirect(xarModURL('translations', 'admin', 'generate_skels_info'));
    }
    xarSessionDelVar('translations_result');

    $druidbar = translations_create_generate_skels_druidbar(GEN);
    $opbar = translations_create_opbar(GEN_SKELS);
    $tran_type = xarSessionGetVar('translations_dnType');
    $tplData['dnType'] = translations__dnType2Name($tran_type);
    $tplData = array_merge($tplData, $druidbar, $opbar);

    return $tplData;
}

/* FUNC */function translations_admin_generate_trans_info()
{
// Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    $tplData['locales'] = xarConfigGetVar('Site.MLS.AllowedLocales');
    $tplData['release_locale'] = translations_release_locale();
    $tplData['archiver_path'] = xarModAPIFunc('translations','admin','archiver_path');

    $druidbar = translations_create_generate_trans_druidbar(INFO);
    $opbar = translations_create_opbar(GEN_TRANS);
    $tplData = array_merge($tplData, $druidbar, $opbar);

    return $tplData;
}

/* FUNC */function translations_admin_generate_trans()
{
    $dnType = xarSessionGetVar('translations_dnType');
    $locale = translations_release_locale();
    $args = array('locale'=>$locale);
    switch ($dnType) {
        case XARMLS_DNTYPE_CORE:
        $res = xarModAPIFunc('translations','admin','generate_core_trans',$args);
        break;
        case XARMLS_DNTYPE_MODULE:
        $args['modid'] = xarSessionGetVar('translations_modid');
        $res = xarModAPIFunc('translations','admin','generate_module_trans',$args);
        break;
    }
    if (!isset($res)) return;

    xarSessionSetVar('translations_result', $res);
    xarResponseRedirect(xarModURL('translations', 'admin', 'generate_trans_result'));
}

/* FUNC */function translations_admin_generate_trans_result()
{
// Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    $tplData = xarSessionGetVar('translations_result');
    if ($tplData == NULL) {
        xarResponseRedirect(xarModURL('translations', 'admin', 'generate_trans_info'));
    }
    xarSessionDelVar('translations_result');

    $druidbar = translations_create_generate_trans_druidbar(GEN);
    $opbar = translations_create_opbar(GEN_TRANS);
    $tplData = array_merge($tplData, $druidbar, $opbar);

    return $tplData;
}

/* FUNC */function translations_admin_release()
{
    $dnType = xarSessionGetVar('translations_dnType');
    $locale = translations_release_locale();
    $args = array('locale'=>$locale);
    switch ($dnType) {
        case XARMLS_DNTYPE_CORE:
        $res = xarModAPIFunc('translations','admin','release_core_trans',$args);
        break;
        case XARMLS_DNTYPE_MODULE:
        $args['modid'] = xarSessionGetVar('translations_modid');
        $res = xarModAPIFunc('translations','admin','release_module_trans',$args);
        break;
    }
    if (!isset($res)) return;

    xarSessionSetVar('translations_filename', $res);
    xarResponseRedirect(xarModURL('translations', 'admin', 'release_result'));
}

/* FUNC */function translations_admin_release_result()
{
// Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    $filename = xarSessionGetVar('translations_filename');
    if ($filename == NULL) {
        xarResponseRedirect(xarModURL('translations', 'admin', 'release_info'));
    }
    xarSessionDelVar('translations_filename');

    $tplData['url'] = xarServerGetBaseURL().xarCoreGetVarDirPath().'/cache/'.$filename;

    $druidbar = translations_create_generate_trans_druidbar(REL);
    $opbar = translations_create_opbar(GEN_TRANS);
    $tplData = array_merge($tplData, $druidbar, $opbar);

    return $tplData;
}

/* FUNC */function translations_admin_translate()
{
// Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    $opbar = translations_create_opbar(TRANSLATE);
    $trabar = translations_create_trabar('', '');
    $druidbar = translations_create_translate_druidbar(TRAN);
    $tplData = array_merge($opbar, $trabar, $druidbar);

    return $tplData;
}


/* FUNC */function translations_admin_translate_context()
{
// Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    if (!xarVarFetch('name', 'isset', $name)) return;
    $context = $GLOBALS['MLS']->getContextByName($name);

    $dnType = xarSessionGetVar('translations_dnType');
    $dnName = xarSessionGetVar('translations_dnName');

    $locale = translations_working_locale();

    $args['interface'] = 'ReferencesBackend';
    $args['locale'] = $locale;
    $backend = xarModAPIFunc('translations','admin','create_backend_instance',$args);
    if (!isset($backend)) return;
    if (!$backend->bindDomain($dnType, $dnName)) {
        xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN');
        return;
    }
    $subtype = $context->getType();
    $subnames = $backend->getContextNames($context->getType());
    $args = array();
    $entrydata = array();
    $i=0;
    $subnams = $subnames;
    $args['subtype'] = $context->getName();
    foreach($subnams as $subname) {
        $args['subname'] = $subname;
        $entry = xarModAPIFunc('translations','admin','getcontextentries',$args);
        if ($entry['numEntries']+$entry['numKeyEntries'] == 0) {
            array_splice($subnames,$i,1);
        }
        else {
            $entrydata[] = $entry;
        }
        $i++;
    }
    $tplData['subnames'] = $subnames;
    $tplData['entrydata'] = $entrydata;
    $tplData['subtype'] = $context->getName();

    $opbar = translations_create_opbar(TRANSLATE);
    $trabar = translations_create_trabar($name, '',$backend);

    $tplData = array_merge($tplData, $opbar, $trabar);
    $tplData['dnType'] = translations__dnType2Name($dnType);

    return xarTplModule('translations','admin', 'translate_template',$tplData);
}

/* FUNC */function translations_admin_test()
{
    $args = array('testcomponent' => 'Pippo::', 'testinstance' => '.*');
    $res = xarModAPIFunc('permissions', 'admin', 'query_access_level', $args);
    ob_start();
    var_dump($res);
    $res = ob_get_contents();
    ob_end_clean();
    return $res;
}

// PRIVATE STUFF


function translations_create_generate_skels_druidbar($currentStep) {
    $stepLabels[INFO] = xarML('Overview');
    $stepLabels[GEN] = xarML('Generation');
    $stepURLs[INFO] = xarModURL('translations', 'admin', 'generate_skels_info');
    $stepURLs[GEN] = NULL;

    return array('stepLabels'=>$stepLabels, 'stepURLs'=>$stepURLs, 'currentStep'=>$currentStep);
}

function translations_create_translate_druidbar($currentStep) {
    $stepLabels[INFO] = xarML('Overview');
    $stepLabels[GEN] = xarML('Generation');
    $stepLabels[TRAN] = xarML('Translate');
    $stepURLs[INFO] = xarModURL('translations', 'admin', 'generate_skels_info');
    $stepURLs[GEN] = xarModURL('translations','admin','generate_skels');

    return array('stepLabels'=>$stepLabels, 'stepURLs'=>$stepURLs, 'currentStep'=>$currentStep);
}

function translations_create_generate_trans_druidbar($currentStep) {
    $stepLabels[INFO] = xarML('Overview');
    $stepLabels[GEN] = xarML('Generation');
    $stepLabels[REL] = xarML('Release');
    //$stepLabels[DOWNLOAD] = xarML('Download');
    $stepURLs[INFO] = xarModURL('translations', 'admin', 'generate_trans_info');
    $stepURLs[GEN] = xarModURL('translations', 'admin', 'generate_trans');
    //$stepURLs[REL] = xarModURL('translations', 'admin', 'generate_release');
    $stepURLs[DOWNLOAD] = NULL;

    return array('stepLabels'=>$stepLabels, 'stepURLs'=>$stepURLs, 'currentStep'=>$currentStep);
}

function translations_create_choose_a_module_druidbar($currentStep) {
    // This + 1 is actually an "hack"
    $stepLabels[CHOOSE + 1] = xarML('Choose a module');
    $stepLabels[OVERVIEW + 1] = xarML('Overview');
    $stepURLs[CHOOSE + 1] = xarModURL('translations', 'admin', 'choose_a_module');
    $stepURLs[OVERVIEW + 1] = NULL;

    return array('stepLabels'=>$stepLabels, 'stepURLs'=>$stepURLs, 'currentStep'=>$currentStep + 1);
}

function translations_create_choose_a_theme_druidbar($currentStep) {
    // This + 1 is actually an "hack"
    $stepLabels[CHOOSE + 1] = xarML('Choose a theme');
    $stepLabels[OVERVIEW + 1] = xarML('Overview');
    $stepURLs[CHOOSE + 1] = xarModURL('translations', 'admin', 'choose_a_theme');
    $stepURLs[OVERVIEW + 1] = NULL;

    return array('stepLabels'=>$stepLabels, 'stepURLs'=>$stepURLs, 'currentStep'=>$currentStep + 1);
}


function translations_create_opbar($currentOp)
{
    $dnType = xarSessionGetVar('translations_dnType');
    $dnName = xarSessionGetVar('translations_dnName');

    // Overview | Generate skels | Translate | Generate translations | Release translations package
    $opLabels[OVERVIEW] = xarML('Overview');
    $opLabels[GEN_SKELS] = xarML('Generate skels');
    $opLabels[TRANSLATE] = xarML('Translate');
    //$opLabels[GEN_TRANS] = xarML('Generate translations');
    //$opLabels[RELEASE] = xarML('Release translations package');

    switch ($dnType) {
        case XARMLS_DNTYPE_CORE:
        $opURLs[OVERVIEW] = xarModURL('translations', 'admin', 'core_overview');
        break;
        case XARMLS_DNTYPE_MODULE:
        $opURLs[OVERVIEW] = xarModURL('translations', 'admin', 'module_overview');
        break;
        case XARMLS_DNTYPE_THEME:
        $opURLs[OVERVIEW] = xarModURL('translations', 'admin', 'theme_overview');
        break;
    }
    $opURLs[GEN_SKELS] = xarModURL('translations', 'admin', 'generate_skels_info');
    $opURLs[TRANSLATE] = xarModURL('translations', 'admin', 'translate');
    //$opURLs[GEN_TRANS] = xarModURL('translations', 'admin', 'generate_trans_info');
    //$opURLs[RELEASE] = xarModURL('translations', 'admin', 'release_info');

    $enabledOps = array(true, true, false, false/*, false*/); // Enables See module details & Generate translations skels

    $locale = translations_working_locale();
    $args['interface'] = 'ReferencesBackend';
    $args['locale'] = $locale;
    $backend = xarModAPIFunc('translations','admin','create_backend_instance',$args);
    if (!isset($backend)) return;

    if ($backend->bindDomain($dnType, $dnName)) {
        $enabledOps[TRANSLATE] = true; // Enables Translate
        $enabledOps[GEN_TRANS] = true; // Enables Generate translations
        /*$args['interface'] = 'TranslationsBackend';
        $args['locale'] = $locale;
        $backend = xarModAPIFunc('translations','admin','create_backend_instance',$args);
        if (!isset($backend)) return;
        if ($backend->bindDomain($dnType, $dnName)) {
            $enabledOps[RELEASE] = true; // Enables Release translations package
        }*/
    }
    return array('opLabels'=>$opLabels, 'opURLs'=>$opURLs, 'enabledOps'=>$enabledOps, 'currentOp'=>$currentOp);
}

function translations_create_trabar($subtype, $subname, $backend=NULL)
{
    $dnType = xarSessionGetVar('translations_dnType');
    $dnName = xarSessionGetVar('translations_dnName');

    $currentTra = -1;
    switch ($dnType) {
        case XARMLS_DNTYPE_CORE:
        $traLabels[0] = 'core';

        $traURLs[0] = xarModURL('translations', 'admin', 'translate_subtype', array('subtype'=>'file', 'subname'=>'core'));

        $enabledTras = array(true);

        if ($subtype == 'file') {
            $currentTra = 0;
        }
        break;

        case XARMLS_DNTYPE_MODULE:
        $subnames = xarModAPIFunc('translations','admin','get_module_phpfiles',
                                  array('moddir'=>$dnName));

        $args = array();
        $args['subtype'] = "file";
        $subnams = $subnames;

        $j = 0;
        foreach ($subnams as $subnameinlist) {
            $args['subname'] = $subnameinlist;
            $entry = xarModAPIFunc('translations','admin','getcontextentries',$args);
            if ($entry['numEntries']+$entry['numKeyEntries'] > 0) {
                array_splice($subnames,$j,1);
                $traLabels[$j] = $subnameinlist;
                $traURLs[$j] = xarModURL('translations', 'admin', 'translate_subtype', array('subtype'=>'file', 'subname'=>$subnameinlist));
                $enabledTras[$j] = true;
                $j++;
            }
        }

        if ($backend == NULL) {
            $locale = translations_working_locale();
            $args['interface'] = 'ReferencesBackend';
            $args['locale'] = $locale;
            $backend = xarModAPIFunc('translations','admin','create_backend_instance',$args);
            if (!isset($backend)) return;
            if (!$backend->bindDomain($dnType, $dnName)) {
                xarExceptionSet(XAR_SYSTEM_EXCEPTION, 'UNKNOWN');
                return;
            }
        }
        $contexts = $GLOBALS['MLS']->getContexts();
        foreach ($contexts as $context) {
            if ($context->getName() != "file" && count($backend->getContextNames($context->getType())) >0) {
                $traLabels[$j] = $context->getLabel();
                $enabledTras[$j] = true;
                $traURLs[$j] = xarModURL('translations', 'admin', 'translate_context',array('name'=>$context->getName()));
                $j++;
            }
        }

        if ($subtype !="") {
            if($subtype =="file") {
                $currentTra = array_search($subname, $traLabels);
            }
            else {
                $currentContext = $GLOBALS['MLS']->getContextByName($subtype);
                $currentTra = array_search($currentContext->getLabel(), $traLabels);
            }
        }
        else {
            $currentTra = 99;
        }

        break;
        case XARMLS_DNTYPE_THEME:
        // TODO
        break;
    }

    return array('traLabels'=>$traLabels, 'traURLs'=>$traURLs, 'enabledTras'=>$enabledTras, 'currentTra'=>$currentTra);
}

function translations_working_locale($locale = NULL)
{
    if (!$locale) {
        $locale = xarSessionGetVar('translations_working_locale');
        if (!$locale) {
            $locale = xarMLSGetCurrentLocale();
            xarSessionSetVar('translations_working_locale', $locale);
        }
        return $locale;
    } else {
        xarSessionSetVar('translations_working_locale', $locale);
    }
}

function translations_release_locale($locale = NULL)
{
    if (!$locale) {
        $locale = xarSessionGetVar('translations_release_locale');
        if (!$locale) {
            $locale = translations_working_locale();
            xarSessionSetVar('translations_release_locale', $locale);
        }
        return $locale;
    } else {
        xarSessionSetVar('translations_release_locale', $locale);
    }
}

function translations__dntype2name ($tran_type)
{
    switch($tran_type) {
    case XARMLS_DNTYPE_CORE:
        return xarML('core');
        break;
    case XARMLS_DNTYPE_MODULE:
        return xarML('module');
        break;
    case XARMLS_DNTYPE_THEME:
        return xarML('theme');
        break;
    default:
        return xarML('unknown');
    }
}

?>