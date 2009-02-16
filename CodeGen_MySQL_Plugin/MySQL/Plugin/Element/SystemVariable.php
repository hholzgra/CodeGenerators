<?php
/**
 * A class that generates MySQL Plugin soure and documenation files
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
 * @version    CVS: $Id: SystemVariable.php,v 1.3 2007/04/26 13:56:03 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen_MySQL_Plugin
 */

/**
 * includes
 */
// {{{ includes

require_once "CodeGen/MySQL/Plugin/Element.php";

// }}} 

/**
 * A class that generates Plugin extension soure and documenation files
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL_Plugin
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_MySQL_Plugin
 */

class CodeGen_MySQL_Plugin_Element_SystemVariable
  extends CodeGen_Element
{
  protected $type;
  protected $name;
  protected $varname;
  protected $default; 
  protected $min;
  protected $max;
  protected $blocksize;
  protected $scope;
  protected $comment;
  protected $flags;
  protected $check;
  protected $uppdate;
  protected $readonly;

  function __construct($scope, $type, $name)
  {
    $this->name      = $name;
    $this->varname   = $name;
    $this->type      = strtoupper($type);
    $this->scope     = $scope;
    $this->default   = 0;
    $this->min       = 0;
    $this->max       = "INT_MAX";
    $this->blocksize = 0;
    $this->flags     = "PLUGIN_VAR_MEMALLOC";
    $this->readonly  = false;
  }

  function setMin($val) 
  {
    switch ($this->type) {
    case "BOOL": 
    case "INT": 
    case "LONG":
    case "LONGLONG": 
    case "UINT": 
    case "ULONG":
    case "ULONGLONG": 
      $this->min = $val;
      return true;
    default:
      return PEAR::raiseError("'{$this->type}' does not support a min value");
    }
  }

  function setMax($val) 
  {
    switch ($this->type) {
    case "BOOL": 
    case "INT": 
    case "LONG":
    case "LONGLONG": 
    case "UINT": 
    case "ULONG":
    case "ULONGLONG": 
      $this->max = $val;
      return true;
    default:
      return PEAR::raiseError("'{$this->type}' does not support a max value");
    }
  }

  function setDefault($val)
  {
    switch ($this->type) {
    case "BOOL": 
    case "INT": 
    case "LONG":
    case "LONGLONG": 
    case "UINT": 
    case "ULONG":
    case "ULONGLONG": 
      $this->default = $val;
    break;
    case "STR":
      $this->default = "\"$val\"";
      break;
    }
  }

  static function isValidType($type)
  {
    switch (strtoupper($type)) {
    case "BOOL": 
    case "INT": 
    case "LONG":
    case "LONGLONG": 
    case "UINT": 
    case "ULONG":
    case "ULONGLONG": 
    case "STR": 
      //    case "ENUM": 
      //    case "SET": 
      return true;
    default:
      return PEAR::raiseError("'$type' is not a valid system variable type");
    }
  }

  function cType()
  {
    switch ($this->type) {
    case "BOOL":      return "bool";
    case "INT":       return "int";
    case "LONG":      return "long";
    case "LONGLONG":  return "long long";
    case "UINT":      return "unsigned int";
    case "ULONG":     return "unsigned long";
    case "ULONGLONG": return "unsigned long long";
    case "STR":       return "char *";
      //    case "ENUM":      return "unsigned long";
      //    case "SET":       return "unsigned long long"; 
    }
  }

  static function isValidScope($scope)
  {
    switch (strtolower($scope)) {
    case "session": 
    case "global": 
      return true;
    default:
      return PEAR::raiseError("'$scope' is not a valid system variable scope");
    }
  }

  function getRegistration() 
  {
    return "  MYSQL_SYSVAR({$this->name}),\n";
  }

  static function startRegistrations($prefix="")
  {
    return "static struct st_mysql_sys_var* {$prefix}system_variables[]= {\n";
  }
  
  static function endRegistrations($prefix) 
  {
    return "  NULL\n};\n";
  }

  function getDefinition($prefix = "") 
  {
    ob_start();

    switch ($this->scope) {
    case "global":
      echo "static ".$this->cType()." {$this->name};\n";
      $scope = "SYS";
      $name  = $this->name.", ".$this->varname;
      break;
    case "session":
      $scope = "THD";
      $name  = $this->name;
      break;
    default:
      die($this->scope);
    }

    echo "static MYSQL_{$scope}VAR_{$this->type}({$name},\n";
    echo "  {$this->flags}, \"{$this->name}\",\n"; 
    echo "  NULL, NULL, \n";
    echo "  {$this->default}";

    switch ($this->type) {
    case "INT":
    case "LONG":
    case "LONGLONG":
    case "UINT":
    case "ULONG":
    case "ULONGLONG":
      echo ", {$this->min}, {$this->max}, {$this->blocksize}";
      break;
    }

    echo ");\n\n";
    
    return ob_get_clean();
  }
}