<?php
/**
 * Update configuration for translations module
 *
 * @package modules
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage translations
 * @author Marcel van der Boom <marcel@xaraya.com>
*/

/**
 * Update configuration
 *
 * @param string
 * @return void?
 * @todo move in timezone var when we support them
 * @todo decide whether a site admin can set allowed locales for users
 * @todo add decent validation
 */
function translations_admin_updateconfig()
{
    if (!xarSecConfirmAuthKey()) return;

    // Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    if (!xarVarFetch('tab', 'str:1:100', $data['tab'], 'general', XARVAR_NOT_REQUIRED)) return;
    switch ($data['tab']) {
        case 'locales':
            if (!xarVarFetch('defaultlocale','str:1:',$defaultLocale)) return;
            if (!xarVarFetch('active','isset',$active)) return;
            if (!xarVarFetch('mlsmode','str:1:',$MLSMode,'SINGLE',XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('translationsbackend','str:1:',$translationsBackend)) return;

            $localesList = array();
            foreach($active as $activelocale) $localesList[] = $activelocale;
            if (!in_array($defaultLocale,$localesList)) $localesList[] = $defaultLocale;
            sort($localesList);

            if (($MLSMode == 'UNBOXED') && (xarMLSGetCharsetFromLocale($defaultLocale) != 'utf-8')) {
                $msg = xarML('You should select utf-8 locale as default before selecting UNBOXED mode');
                xarErrorSet(XAR_USER_EXCEPTION, 'BAD_DATA', new DefaultUserException($msg));
                break;
            }

            // Locales
            xarConfigSetVar('Site.MLS.MLSMode', $MLSMode);
            xarConfigSetVar('Site.MLS.DefaultLocale', $defaultLocale);
            xarConfigSetVar('Site.MLS.AllowedLocales', $localesList);
            xarConfigSetVar('Site.MLS.TranslationsBackend', $translationsBackend);
            break;
        case 'display':
            if (!xarVarFetch('showcontext','checkbox',$showContext,false,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('maxreferences','int',$maxReferences,5,XARVAR_NOT_REQUIRED)) return;
            if (!xarVarFetch('maxcodelines','int',$maxCodeLines,5,XARVAR_NOT_REQUIRED)) return;
            
            xarModSetVar('translations', 'showcontext',$showContext);
            xarModSetVar('translations', 'maxreferences',$maxReferences);
            xarModSetVar('translations', 'maxcodelines',$maxCodeLines);

            break;
        case 'release':
            if (!xarVarFetch('releasebackend','str:1:',$releaseBackend)) return;
            
            // xarModSetVar('translations', 'release_backend_type', $releaseBackend);

            break;
    }

    //FIXME: what is this?
    if (!isset($cacheTemplates)) {
        $cacheTemplates = true;

        // Call updateconfig hooks
        xarModCallHooks('module','updateconfig','translations', array('module' => 'translations'));
    }

    xarResponseRedirect(xarModURL('translations', 'admin', 'modifyconfig',array('tab' => $data['tab'])));

    return true;
}

?>