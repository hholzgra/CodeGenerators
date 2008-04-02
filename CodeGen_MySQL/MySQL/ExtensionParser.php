<?php
/**
 * A class that generates MySQL soure and documenation files
 *
 * PHP versions 5
 *
 * LICENSE: This source file is subject to version 3.0 of the PHP license
 * that is available through the world-wide-web at the following URI:
 * http://www.php.net/license/3_0.txt.  If you did not receive a copy of
 * the PHP License and are unable to obtain it through the web, please
 * send a note to license@php.net so we can mail you a copy immediately.
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: ExtensionParser.php,v 1.5 2007/04/19 17:41:55 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen_MySQL
 */


/**
 * includes
 */
require_once "CodeGen/ExtensionParser.php";
require_once "CodeGen/Maintainer.php";
require_once "CodeGen/Tools/Indent.php";

/**
 * A class that generates MySQL soure and documenation files
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_MySQL
 */
abstract class CodeGen_MySQL_ExtensionParser 
    extends CodeGen_ExtensionParser
{
    function tagstart_test($attr) 
    {
        static $testCount = 0;
        $test = $this->extension->testFactory();

        if (isset($attr["name"])) {
            $err = $test->setName($attr["name"]);
            if (PEAR::isError($err)) {
                return $err;
            }
        } else {
            if (!$test->getName()) {
                $test->setName(sprintf("%03d", ++$testCount));
            }
        }

        $this->pushHelper($test);
    }

    function tagend_test_description($attr, $data) 
    {
        $this->helper->setDescription(CodeGen_Tools_Indent::linetrim($data));
    }

    function tagstart_test_code($attr)
    {
        if (isset($attr["src"])) {
            if (!file_exists($attr["src"])) {
                return PEAR::raiseError("Soruce file '$attr[src]' not found");                    
            }
            if (!is_readable($attr["src"])) {
                return PEAR::raiseError("Cannot read source file '$attr[src]'");                    
            }
        }
    }

    function tagend_test_code($attr, $data) {
        if (isset($attr["src"])) {
            $this->helper->setCode(CodeGen_Tools_Indent::linetrim(file_get_contents($attr["src"])));
        } else {
            $this->helper->setCode(CodeGen_Tools_Indent::linetrim($data));
        }
    }

    function tagend_test_result($attr, $data) 
    {
        $err = $this->helper->setResult(CodeGen_Tools_Indent::linetrim($data));
            
        return $err;
    }

    function tagend_test($attr, $data) 
    {
        $test =  $this->popHelper();
        $err = $this->extension->addTest($test);
        return $err;
    }

    function tagend_tests($attr, $data) 
    {
        return true;
    }


    function tagend_deps_src($attr, $data) 
    {
        $this->extension->setNeedSource(true);
    }
}


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
?>
