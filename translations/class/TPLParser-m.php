<?php
// $Id$
// ----------------------------------------------------------------------
// Xaraya eXtensible Management System
// Copyright (C) 2002 by the Xaraya Development Team.
// http://www.xaraya.org
// ----------------------------------------------------------------------
// Original Author of file: Marco Canini
// Purpose of file:
// ----------------------------------------------------------------------

//

class TPLParser
{
    var $transEntries = array();
    var $transKeyEntries = array();

    var $_fd;
    var $_offs;
    var $_pos;
    var $_len;
    var $_buf;
    var $_line;
    var $_token;
    var $_string;
    var $tokenarray;

    function TPLParser()
    {
        $this->tokenarray = array("<xar:mlstring>", "<xar:mlkey>", "xarML('", "xarMLByKey('", 'xarML("', 'xarMLByKey("');
    }

    function getTransEntries()
    {
        return $this->transEntries;
    }

    function getTransKeyEntries()
    {
        return $this->transKeyEntries;
    }

    function _get_token($t_array, $right) {
        foreach ($t_array as $n => $t) {
            $found = false;
//                     printf("Getting line %d\n"."for token %d " . $t, $this->_line, $n);
            while (!$found) {
                if (($this->_pos = strpos($this->_buf, $t, $this->_offs)) !== false) {  // ����� ������
                    if ($right)
                        $this->_string .= substr($this->_buf, $this->_offs, $this->_pos - $this->_offs);
                    $this->_offs = $this->_pos + strlen($t);
                    $this->_token = $n;
                    $found = true;
//                        printf("Found token %s[%d] at pos %d\n"."<br />", $t, $n, $this->_pos);
                    if(!$right) $this->parseclose();
                }
                else {
                    $found = false;
                    if ($right)
                        $this->_string .= substr($this->_buf, $this->_offs);
                    $this->_offs = $this->_len;
                    $this->_offs = 0;
                    break;
                }
            }
        }
        return $found;
    }

    function parseclose()
    {
        $this->_string ='';
        $line = $this->_line;
        switch ($this->_token) {
            case 0:
                if ($this->_get_token(array("</xar:mlstring>"), true)) {
                    $this->_string = trim($this->_string);
                    if (!isset($this->transEntries[$this->_string])) {
                        $this->transEntries[$this->_string] = array();
                    }
                    $this->transEntries[$this->_string][] = array('line' => $line, 'file' => $this->filename);
                }
                break;
            case 1:
                if ($this->_get_token(array("</xar:mlkey>"), true)) {
                    $this->_string = trim($this->_string);
                    if (!isset($this->transKeyEntries[$this->_string])) {
                        $this->transKeyEntries[$this->_string] = array();
                    }
                    $this->transKeyEntries[$this->_string][] = array('line' => $line, 'file' => $this->filename);
                }
                break;
            case 2:
                if ($this->_get_token(array("')"), true)) {
                    if ($string = $this->parseString($this->_string)) {
                        if (!isset($this->transEntries[$string])) {
                            $this->transEntries[$string] = array();
                        }
                        $this->transEntries[$string][] = array('line' => $line, 'file' => $this->filename);
                    }
                }
                break;
            case 3:
                if ($this->_get_token(array("')"), true)) {
                    if ($string = $this->parseString($this->_string)) {
                        if (!isset($this->transKeyEntries[$string])) {
                            $this->transKeyEntries[$string] = array();
                        }
                        $this->transKeyEntries[$string][] = array('line' => $line, 'file' => $this->filename);
                    }
                }
                break;
            case 4:
                if ($this->_get_token(array('")'), true)) {
                    if ($string = $this->parseString($this->_string)) {
                        if (!isset($this->transKeyEntries[$string])) {
                            $this->transKeyEntries[$string] = array();
                        }
                        $this->transKeyEntries[$string][] = array('line' => $line, 'file' => $this->filename);
                    }
                }
                break;
            case 4:
                if ($this->_get_token(array('")'), true)) {
                    if ($string = $this->parseString($this->_string)) {
                        if (!isset($this->transKeyEntries[$string])) {
                            $this->transKeyEntries[$string] = array();
                        }
                        $this->transKeyEntries[$string][] = array('line' => $line, 'file' => $this->filename);
                    }
                }
                break;
            default :
                // internal error
            break;
        }
        if (defined('TPLPARSERDEBUG'))
            printf("Result: %s %s<br />\n", $string, $this->_string);
    }

    function parse($filename)
    {
        if (!file_exists($filename)) return;
        $this->filename = $filename;
        $this->_fd = fopen($filename, 'r') or die("Cannot open file");

        if (!$filesize = filesize($filename)) return;

        $this->_offs = 0;
        $this->_len = 0;

        while (!feof($this->_fd)) {

            $this->_buf = fgets($this->_fd, 1024);
            $this->_len = strlen($this->_buf);
            $this->_line++;
            $this->_offs = 0;
            if ($this->_get_token($this->tokenarray, false)) {


//                if ($filename == "modules/base/xartemplates/blocks/menuAdmin.xd" || $line == 18){
//                echo $this->_token;exit;
//                }
            }
        }

        fclose($this->_fd);
    }

    function parseString($buf)
    {
        return $buf;
        $pos = 0;
        $len = strlen($buf);
        while ($pos < $len) {
            $char = $buf{$pos++};
            if ($char == "'" || $char == "'") {
                $quote = $char;
                break;
            } elseif ($char != ' ') {
                return;
            }
        }
        if ($pos == $len) return;
        $string = '';
        while ($pos < $len) {
            $char = $buf{$pos};
            if ($char == "\\") {
                if ($buf{$pos+1} == $quote) {
                    $string .= $quote;
                    $pos++;
                } else {
                    $string .= $char;
                }
            } else {
                if ($char == $quote) {
                    return $string;
                }
                $string .= $char;
            }
            $pos++;
        }
        return;
    }

}

/*
$p = new TPLParser();
$p->parse('/home/marco/src/xaraya/html/modules/translations/xartemplates/admin-translate_subtype.xd');

var_dump($p);
*/
?>