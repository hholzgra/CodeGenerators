<?php
/**
 * Class for testfile generation as needed for 'make test'
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
 * @package    CodeGen_MySQL_Plugin
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: Test.php,v 1.1 2007/04/19 17:46:24 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen_Plugin
 */

/**
 * includes
 */
require_once "CodeGen/MySQL/Element/Test.php";

/**
 * Class for testfile generation as needed for 'make test'
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL_Plugin
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_Plugin
 */
class CodeGen_MySQL_Plugin_Element_Test
    extends CodeGen_MySQL_Element_Test
{
    /**
     * generate testcase file
     *
     * @access public
     * @param  object  the complete extension context
     */
    function writeTest($extension) 
    {
        $extName = $extension->getName();

        $testName = "tests/t/{$this->name}.test";
        $extension->addPackageFile("test", $testName);

        $file = new CodeGen_Tools_Outbuf($extension->dirpath."/".$testName);        
        echo "# Package: $extName   Test: {$this->name}\n#\n";
        echo preg_replace("/^/m", "# ", $this->description)."\n\n";
        echo "-- disable_query_log\n";
        echo "-- disable_metadata\n\n";
        echo "-- source install_plugins.inc\n\n";
        echo $this->code;
        echo "\n-- source uninstall_plugins.inc\n";        
        $file->write();

        $resultName = "tests/r/{$this->name}.result";
        $extension->addPackageFile("test", $resultName);

        $file = new CodeGen_Tools_Outbuf($extension->dirpath."/".$resultName);      
        echo $this->result;
        $file->write();
    }
}
?>
