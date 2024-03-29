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
 * @version    CVS: $Id: StatusVariable.php,v 1.3 2007/04/26 13:56:03 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen_Drizzle
 */

/**
 * includes
 */
// {{{ includes

require_once "CodeGen/Drizzle/Element.php";

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

class CodeGen_Drizzle_Element_StatusVariable
  extends CodeGen_Element
{
  protected $type;
  protected $name;
  protected $value; 
  protected $init;
  protected $code = "";
  protected $statusVariables = array();

  public function __construct($type, $name, $value = false, $init = 0)
  {
    $this->type  = strtoupper($type);
    $this->name  = $name;
    $this->value = $value;
    $this->init  = $init;
  }

  function getName()
  {
    return $this->name;
  }

  function getType()
  {
    return $this->type;
  }

  static function isValidType($type)
  {
    switch (strtoupper($type)) {
    case "UNDEF": 
    case "BOOL": 
    case "INT": 
    case "LONG":
    case "LONGLONG": 
    case "CHAR": 
    case "CHAR_PTR":
    case "FUNC":
    case "ARRAY": 
      return true;
    default:
      return PEAR::raiseError("'$type' is not a valid status variable type");
    }
  }

  function getRegistration($prefix = "") 
  {
    switch ($this->type) {
      // TODO no float type? 
    case "BOOL": 
    case "INT": 
    case "LONG":
    case "LONGLONG": 
    case "CHAR": 
    case "CHAR_PTR":
    case "FUNC":
      $value = "&".$prefix.$this->value;
      break;
    case "ARRAY": 
      $value = "&array_".$this->value."_status_variables";
      break;
    case "UNDEF": 
    default:
      $value = "NULL";
      break;
    }

    return "  { \"{$this->name}\", (char *)$value, SHOW_{$this->type}},\n";
  }

  static function startRegistrations($prefix="")
  {
    return "static SHOW_VAR {$prefix}status_variables[] = {\n";
  }
  
  static function endRegistrations($prefix) 
  {
    return "  { NULL, NULL, SHOW_UNDEF }\n};\n";
  }

  function getDefinition($prefix = "") 
  {
    switch ($this->type) {
    case "UNDEF": 
      break;

    case "BOOL": 
      $code = "bool $prefix{$this->value}";
      break;

    case "INT": 
      $code = "int32_t $prefix{$this->value}";
      break;

    case "LONG":
      $code = "long $prefix{$this->value}";
      break;

    case "LONGLONG": 
      $code = "long long $prefix{$this->value}";
      break;

    case "UINT": 
      $code = "uint32_t $prefix{$this->value}";
      break;

    case "ULONG":
      $code = "unsigned long $prefix{$this->value}";
      break;

    case "ULONGLONG": 
      $code = "unsigned long long $prefix{$this->value}";
      break;

    case "CHAR": 
      $code = "char $prefix{$this->value}";
      break;

    case "CHAR_PTR":
      $code = "char* $prefix{$this->value}";
      break;

    case "FUNC":
      // callback function prototype (TODO: can we use the drizzle_show_var_func somehow here?)
      return "int $prefix{$this->value} (void *thd, struct st_drizzle_show_var *out, char *buf);\n";
      break;

    case "ARRAY": 
      ob_start();
      foreach ($this->statusVariables as $variable) {
        echo $variable->getDefinition($this->name."_");
      }
      echo "\n\n";
      
      echo CodeGen_Drizzle_Element_StatusVariable::startRegistrations("array_".$this->value."_");
      foreach ($this->statusVariables as $variable) {
        echo $variable->getRegistration($this->name."_");
      }
      echo CodeGen_Drizzle_Element_StatusVariable::endRegistrations("array_".$this->value."_");   
      return ob_get_clean();
      break;

    default:
      return "";
      break;
    }
    
    if ($this->init !== false) {
      $code .= " = ".$this->init;
    }
    
    $code.= ";\n";
    return $code;
  }

  function addStatusVariable($var)
  {
    if ($this->type !== "ARRAY" && $this->type !== "FUNC") {
      return PEAR::raiseError("only variables of type ARRAY or FUNC can have sub-variables");
    }

    if (isset($this->statusVariables[$var->getName()])) {
      return PEAR::raiseError("status variable '".$var->getName()."' already defined (x)");
    }
    
    $this->statusVariables[$var->getName()] = $var;
    
    return true;
  }

}
