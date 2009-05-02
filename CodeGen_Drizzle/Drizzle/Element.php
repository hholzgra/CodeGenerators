<?php
/**
 * A class that generates Drizzle Plugin soure and documenation files
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
 * @package    CodeGen_Drizzle
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2009 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: Element.php,v 1.11 2007/05/05 12:05:37 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen_Drizzle
 */

/**
 * includes
 */
// {{{ includes

require_once "CodeGen/Element.php";
require_once "CodeGen/Tools/Indent.php";

// }}} 

/**
 * A class that generates Plugin extension soure and documenation files
 *
 * @category   Tools and Utilities
 * @package    CodeGen_Drizzle
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2009 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_Drizzle
 */

abstract class CodeGen_Drizzle_Element
  extends CodeGen_Element
{
    /** 
     * Do we require Drizzle source or can we do with public headers only?
     *
     * @var bool
     */
    protected $requiresSource = false;

    /**
     * Constructor
     */
    function __construct()
    {
      $this->setSummary("no summary given");
    }

    /**
     * requiresSource getter
     *
     * @return bool
     */
    function getRequiresSource()
    {
      return $this->requiresSource;
    }

    /**
    * Name setter
    *
    * @param  string  function name
    * @return bool    success status
    */
    function setName($name) 
    {
        if (!self::isName($name)) {
            return PEAR::raiseError("'$name' is not a valid plugin name");
        }
    
        // keywords are not allowed as function names
        if (self::isKeyword($name)) {
            return PEAR::raiseError("'$name' is a reserved word which is not valid for plugin names");
        }
    
        return parent::setName($name);
    }

    function isValid()
    {
        return true;
    }

    function getPluginCode()
    {
        return "";
    }

    function regObject()
    {
        return false;
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
