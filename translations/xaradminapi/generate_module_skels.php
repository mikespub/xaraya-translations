<?php

/**
 * File: $Id$
 *
 * Generate skeletons for a module
 *
 * @package modules
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @link http://www.xaraya.com
 *
 * @subpackage translations
 * @author Marco Canini
 * @author Marcel van der Boom <marcel@xaraya.com>
*/

/**
 * generate translations XML skels for a specified module
 * @param $args['modid'] module registry identifier
 * @param $args['locale'] locale name
 * @returns array
 * @return statistics on generation process
 */
function translations_adminapi_generate_module_skels($args)
{
    set_time_limit(0);

    // Get arguments
    extract($args);

    // Argument check
    assert('isset($modid) && isset($locale)');

    if (!$modinfo = xarModGetInfo($modid)) return;
    $modname = $modinfo['name'];
    $moddir = $modinfo['osdirectory'];

    // Security Check
    if(!xarSecurityCheck('AdminTranslations')) return;

    // {ML_dont_parse 'modules/translations/class/PHPParser.php'}
    include 'modules/translations/class/PHPParser.php';
    // {ML_dont_parse 'modules/translations/class/TPLParser.php'}
    include 'modules/translations/class/TPLParser.php';

    $time = explode(' ', microtime());
    $startTime = $time[1] + $time[0];

    // Load core translations
    $core_backend = xarModAPIFunc('translations','admin','create_backend_instance',array('interface' => 'ReferencesBackend', 'locale' => $locale));
    if (!isset($core_backend)) return;
    if (!$core_backend->bindDomain(XARMLS_DNTYPE_CORE, 'xaraya')) {
        $msg = xarML('Before you can generate skels for the #(1) module, you must first generate skels for the core.', $modname);
        $link = array(xarML('Click here to proceed.'), xarModURL('translations', 'admin', 'update_info', array('dntype'=>'core')));
        xarExceptionSet(XAR_USER_EXCEPTION, 'MissingCoreSkels', new DefaultUserException($msg, $link));
        return;
    }
    if (!$core_backend->loadContext('core:', 'core')) return;

    // Parse files
    $transEntriesCollection = array();
    $transKeyEntriesCollection = array();

    $subnames = xarModAPIFunc('translations','admin','get_module_phpfiles',array('moddir'=>$moddir));

    foreach ($subnames as $subname) {
        $module_contexts_list[] = 'modules:'.$modname.'::'.$subname;
        $filename = "modules/$moddir/xar$subname.php";

        if (file_exists($filename)) {
            $parser = new PHPParser();
            $parser->parse($filename);

            $transEntriesCollection[$subname] = $parser->getTransEntries();
            $transKeyEntriesCollection[$subname] = $parser->getTransKeyEntries();
        }

    }

    $dirnames = xarModAPIFunc('translations','admin','get_module_dirs',array('moddir'=>$moddir));
    foreach ($dirnames as $dirname) {
        ${$dirname . "names"} = array();
        if (!preg_match('!^templates!i', $dirname, $matches)) {
            $pattern = '/^([a-z\-_]+)\.php$/i';
            $xtype = 'php';
        }
        else { 
            $pattern = '/^([a-z\-_]+)\.xd$/i';
            $xtype = 'xd';
        }
        $subnames = xarModAPIFunc('translations','admin','get_module_files',
                         array('moddir'=>"modules/$moddir/xar$dirname",'pattern'=>$pattern));
        foreach ($subnames as $subname) {
            $module_contexts_list[] = 'modules:'.$modname.':'.$dirname.':'.$subname;
            if ($xtype == 'xd') $parser = new TPLParser();
            else $parser = new PHPParser();
            $parser->parse("modules/$moddir/xar$dirname/$subname.$xtype");
            ${$dirname . "names"}[] = $subname;
            $transEntriesCollection[$dirname.'::'.$subname] = $parser->getTransEntries();
            $transKeyEntriesCollection[$dirname.'::'.$subname] = $parser->getTransKeyEntries();
        }
    }

    $transEntriesCollection = translations_gather_common_entries($transEntriesCollection);
    $transKeyEntriesCollection = translations_gather_common_entries($transKeyEntriesCollection);

    $subnames[] = 'common';
    // Load previously made translations
    $backend = xarModAPIFunc('translations','admin','create_backend_instance',array('interface' => 'ReferencesBackend', 'locale' => $locale));
    if (!isset($backend)) return;

    if ($backend->bindDomain(XARMLS_DNTYPE_MODULE,$modname)) {
        if ($backend->hasContext('modules:','common')){
            if (!$backend->loadContext('modules:','common')) return;
        }
        foreach ($module_contexts_list as $module_context) {
            list ($dntype1, $dnname1, $ctxtype1, $ctxname1) = explode(':',$module_context);
            if ($backend->hasContext('modules:'.$ctxtype1,$ctxname1)){
                if (!$backend->loadContext('modules:'.$ctxtype1,$ctxname1)) return;
            }
        }
    }

    // Load KEYS
    $filename = "modules/$moddir/KEYS";
    $KEYS = array();
    if (file_exists($filename)) {
        $lines = file($filename);
        foreach ($lines as $line) {
            if ($line{0} == '#') continue;
            list($key, $value) = explode('=', $line);
            $key = trim($key);
            $value = trim($value);
            $KEYS[$key] = $value;
        }
    }

    // Create skels
    $subnames = array_keys($transEntriesCollection);
    $gen = xarModAPIFunc('translations','admin','create_generator_instance',array('interface' => 'ReferencesGenerator', 'locale' => $locale));
    if (!isset($gen)) return;
    if (!$gen->bindDomain(XARMLS_DNTYPE_MODULE, $modname)) return;

    foreach ($subnames as $subname) {
        if (preg_match('/(.*)::(.*)/', $subname, $matches)) {
           list ($ctxtype1, $ctxname1) = explode('::',$subname);
        } else {
            $ctxtype1 = '';
            $ctxname1 = $subname;
        }
        if (!$gen->create('modules:'.$ctxtype1,$ctxname1)) return;

        $statistics[$subname] = array('entries'=>0, 'keyEntries'=>0);

        // Avoid creating entries for the same locale
        if ($locale != 'en_US.utf-8') {
            foreach ($transEntriesCollection[$subname] as $string => $references) {

                // Check if string appears in core translations
                $entry = $core_backend->getEntry($string);
                if (isset($entry)) continue;

                $statistics[$subname]['entries']++;
                // Get previous translation, it's void if not yet translated
                $translation = $backend->translate($string);

                // Add entry
                $gen->addEntry($string, $references, $translation);
            }
        }

        foreach ($transKeyEntriesCollection[$subname] as $key => $references) {

            // Check if key appears in core translations
            $keyEntry = $core_backend->getEntryByKey($key);
            if (isset($keyEntry)) continue;

            $statistics[$subname]['keyEntries']++;
            // Get previous translation, it's void if not yet translated
            $translation = $backend->translateByKey($key);
            // Get the original translation made by developer if any
            if (!$translation && isset($KEYS[$key])) $translation = $KEYS[$key];
            // Add key entry
            $gen->addKeyEntry($key, $references, $translation);
        }

        $gen->close();
    }

    $time = explode(' ', microtime());
    $endTime = $time[1] + $time[0];
    return array('time' => $endTime - $startTime, 'statistics' => $statistics);
}

/* PRIVATE FUNCTIONS */
function translations_gather_common_entries($transEntriesCollection)
{
    $commonEntries = array();
    $subnames = array_keys($transEntriesCollection);
    foreach ($subnames as $subname) {
        foreach ($transEntriesCollection[$subname] as $string => $references) {

            $refs_inserted = false;
            foreach ($subnames as $other_subname) {
                if ($other_subname == $subname) continue;

                if (isset($transEntriesCollection[$other_subname][$string])) {
                    // Found a duplicated ML string
                    if (!isset($commonEntries[$string])) {
                        $commonEntries[$string] = array();
                    }

                    if (!$refs_inserted) {
                        // Insert once the references in $transEntriesCollection[$subname][$string]
                        foreach ($references as $reference) {
                            $ref_exists = false;
                            foreach ($commonEntries[$string] as $existant_refs) {
                                if ($reference['file'] == $existant_refs['file'] &&
                                    $reference['line'] == $existant_refs['line']) {
                                        $ref_exists = true;
                                }
                            }
                            if (!$ref_exists) {
                                $commonEntries[$string][] = $reference;
                            }
                        }
                        $refs_inserted = true;
                    }

                    // Insert the references in $transEntriesCollection[$other_subname][$string]
                    $other_references = $transEntriesCollection[$other_subname][$string];
                    foreach ($other_references as $reference) {
                        $ref_exists = false;
                        foreach ($commonEntries[$string] as $existant_refs) {
                            if ($reference['file'] == $existant_refs['file'] &&
                                $reference['line'] == $existant_refs['line']) {
                                    $ref_exists = true;
                            }
                        }
                        if (!$ref_exists) {
                            $commonEntries[$string][] = $reference;
                        }
                    }

                    unset($transEntriesCollection[$subname][$string]);
                    unset($transEntriesCollection[$other_subname][$string]);
                }
            }
        }
    }
    $transEntriesCollection['common'] = $commonEntries;
    return $transEntriesCollection;
}

?>