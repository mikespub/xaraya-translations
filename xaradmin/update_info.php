<?php
/**
 * Translations Module
 *
 * @package modules
 * @subpackage translations module
 * @category Third Party Xaraya Module
 * @version 2.0.0
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://xaraya.com/index.php/release/77.html
 * @author Marco Canini
 * @author Marcel van der Boom <marcel@xaraya.com>
 */


function translations_admin_update_info()
{
    // Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    if (!xarVarFetch('dntype', 'regexp:/^(core|module|theme)$/', $type)) return;

    switch ($type) {
        case 'core':
        $url = xarModURL('translations', 'admin', 'core_overview');
        break;
        case 'module':
        $url = xarModURL('translations', 'admin', 'choose_a_module');
        break;
        case 'theme':
        $url = xarModURL('translations', 'admin', 'choose_a_theme');
        break;
    }
    xarController::redirect($url);
}

?>