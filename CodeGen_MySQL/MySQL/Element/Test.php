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
 * @package    CodeGen_MySQL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: Test.php,v 1.4 2007/04/19 17:41:55 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * includes
 */
require_once "CodeGen/Element.php";

/**
 * Class for testfile generation as needed for 'make test'
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_MySQL_Element_Test
    extends CodeGen_Element
{
    /** 
     * Constructor
     * 
     * @access public
     * @param  string  testfile basename
     */
    function __construct() 
    {
    }

    /**
     * testfile basename
     *
     */
    protected $name = "";
    
    /**
     * Setter for testcase name
     *
     * @access public
     * @return string  value of
     */ 
    function setName($name) 
    {
        if (! preg_match('|^[\w-]+$|i', $name)) {
            return PEAR::raiseError("'$name' is not a valid test case basename");
        }

        $this->name = $name;
        if (empty($this->title)) {
            $this->title = $name;   
        }
    }

    /**
     * Getter for testcase name
     *
     * @access public
     * @return string  value of
     */ 
    function getName() 
    {
        return $this->name;
    }

    /**
     * Testcase description
     *
     * @var   string
     */
    protected $description = "";

    /**
     * Getter for testcase description
     *
     * @access public
     * @return string  value of
     */
    function getDescription() 
    {
        return $this->description;
    }
    
    /**
     * Setter for testcase description
     *
     * @access public
     * @param  string  new value for
     */
    function setDescription($text) 
    {
        $this->description = $text;
    }
    
    /**
     * actual test code
     *
     * @var   string
     */
    protected $code;

    /**
     * Getter for test code
     *
     * @access public
     * @return string  value of
     */
    function getCode() 
    {
        return $this->code;
    }
    
    /**
     * Setter for test code
     *
     * @access public
     * @param  string  new value for
     */
    function setCode($code) 
    {
        $this->code = $code;
    }
    
    /**
     * expected result for test code
     *
     * @var   string
     */
    protected $result = "OK";

    /**
     * Getter for expected result
     *
     * @access public
     * @return string  value of
     */
    function getResult() 
    {
        return $this->result;
    }
    
    /**
     * Setter for expected result
     *
     * @access public
     * @param  string  new value for
     */
    function setResult($data) 
    {
        $this->result = $data;
    }

    /** 
     * all required properties set?
     *
     * @access public
     * @return bool
     */
    function complete() 
    {
        if (empty($this->code))   return PEAR::raiseError("no code specified for test case");
        if (empty($this->result)) return PEAR::raiseError("no result specified for test case");
        return true;
    }
    
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
        echo preg_replace("/^/", "# ", $this->description);
        echo $this->code;
        $file->write();

        $resultName = "tests/r/{$this->name}.result";
        $extension->addPackageFile("test", $resultName);

        $file = new CodeGen_Tools_Outbuf($extension->dirpath."/".$resultName);      
        echo $this->result;
        $file->write();
    }
}
?>
