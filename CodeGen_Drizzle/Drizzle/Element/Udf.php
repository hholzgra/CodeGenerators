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
 * @version    CVS: $Id: Daemon.php,v 1.1 2007/04/17 08:48:31 hholzgra Exp $
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
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_Drizzle
 */

class CodeGen_Drizzle_Element_Udf
  extends CodeGen_Drizzle_Element
{
    /**
     * Function type: normal or aggregate
     *
     * @var    string  
     */
    protected $type = "normal";

    /**
     * Function type setter
     *
     * @param  string  "normal" or "aggregate"
     * @return bool    success status
     */        
    function setType($type) 
    {
        switch ($type) {
        case "regular":
            $this->type = "normal";
            return true;
        case "normal":
        case "aggregate":
            $this->type = $type;
            return true;

        default:
            return PEAR::raiseError("'$type' is not a valid function type");
        }
    }


    /**
     * max. Length of return value
     *
     * @var    mixed
     */
    protected $length = 10;

    /**
     * Length setter
     *
     * @param  mixed  max. lengt as int or flase if not applicable
     * @return bool    success status
     */
    function setLength($length) 
    {
        if (!is_numeric($length) || $length != (int)$length || $length <= 0) {
            return PEAR::raiseError("length attribute needs an integer value greater than zero");
        }

        $this->length = $length;

        return true;
    }


    /**
     * decimal digits for REAL return values
     *
     * @var    mixed
     */
    protected $decimals = false;

    /**
     * Decimals setter
     *
     * @param  mixed  max. lengt as int or flase if not applicable
     * @return bool    success status
     */
    function setDecimals($decimals) 
    {
        if (!is_numeric($decimals) || $decimals != (int)$decimals || $decimals < 0) {
            return PEAR::raiseError("decimals attribute needs a non-negative integer value");
        }

        $this->decimals = $decimals;

        return true;
    }


    /**
     * Function may return NULL values?
     *
     * @var    bool
     */
    protected $null = 0;

    /**
     * NULL setter
     *
     * @param  bool  truth value
     * @return bool   success status
     */
    function setNull($null) 
    {
        $this->null = $null;
    }


    /**
     * Function returntype
     *
     * @var     string
     */
    protected $returns = "string";

        
    /**
     * Return type setter
     *
     * one of "string", "int", "real", or "datetime"
     *
     * @param  string  return type
     * @return bool    success status
     */
    function setReturns($returns) 
    {
        $returns = strtolower($returns);

        switch ($returns) {
        case "str":
        case "int":
        case "real":
	  break;
	case "str":
	  $returns = "str";
	  break;
        default:
            return PEAR::raiseError("'$returns' is not a valid return type");
        }

	$this->returns = $returns;
    }



    /**
     * Function parameters 
     *
     * @var     array
     */
    protected $params = array();

    /**
     * Number of mandatory parameters 
     *
     * @var   int
     */
    protected $mandatoryParams = 0;

    /**
     * Number of optional parameters 
     *
     * @var   int
     */
    protected $optionalParams  = 0;

    /**
     * Total number of parameters 
     *
     * @var   int
     */
    protected $totalParams     = 0;

    /**
     * Add a function parameter to the parameter list
     *
     * @param  string  parameter name
     * @param  string  parameter type
     * @param  string  optional?
     * @param  string  default value
     * @return bool    success status
     */
    function addParam($name, $type, $optional = null, $default = null) 
    {
        if (isset($this->params[$name])) {
            return PEAR::raiseError("duplicate parameter name '$name'");
        }

        if (!self::isName($name)) {
            return PEAR::raiseError("'$name' is not a valid parameter name");
        }
            
        if ($name == "data") {
            return PEAR::raiseError("'data' is a reserved name");
        }
            
        // keywords are not allowed as parameter names
        if (self::isKeyword($name)) {
            return PEAR::raiseError("'$name' is a reserved word which is not valid for data element names");
        }

        $type = strtolower($type);
        switch ($type) {
        case "int":
        case "real":
        case "string":
        case "datetime":
            break;
        default:
            return PEAR::raiseError("'$type' is not a valid parameter type");
        }

        if ($optional !== null) {
            switch (strtolower($optional)) {
            case "yes":
            case "true":
            case "1":
                $optional = true;
                break;
                    
            case "no":
            case "false":
            case "0":
                $optional = false;
                break;
                    
            default:
                return PEAR::raiseError("'$optional' is not a valid value for the 'optional' attribute");
            }
        } 
            
        if ($optional) {
            $this->optionalParams++;
        } else {
            if ($this->optionalParams) {
                return PEAR::raiseError("only optional parameters are allowed after the first optonal");
            }
            if ($default !== null) {
                return PEAR::raiseError("only optional parameters may have default values");
            }
            $this->mandatoryParams++;
        }

        $this->totalParams++;

        $this->params[$name] = array("type"=>$type, "optional"=>$optional, "default"=>$default);

        return true;
    }

    /**
     * Private data elements
     *
     * @var    array
     */
    protected $dataElements = array();

    /**
     * Add an element to the functions private data structure
     *
     * @param  string  element name
     * @param  string  element type
     * @param  stirng  default value
     * @return bool    success status
     */
    function addDataElement($name, $type, $default=false) 
    {
        if (isset($this->dataElements[$name])) {
            return PEAR::raiseError("duplicate data element name '$name'");
        }

        if (!self::isName($name)) {
            return PEAR::raiseError("'$name' is not a valid data element name");
        }
            
        // keywords are not allowed as data element names
        if (self::isKeyword($name)) {
            return PEAR::raiseError("'$name' is a reserved word which is not valid for data element names");
        }

        $this->dataElements[$name] = array("type"=>$type, "default"=>$default);

        return true;
    }
    
        
        


    /**
     * Code snippet
     *
     * @var     string
     */
    protected $code = "";

    /**
     * Function code setter
     * 
     * @param  string  C code snippet
     * @return bool    success status
     */
    function setCode($text)
    {
        $this->code = $text;
        return true;
    }


    /**
     * Code snippet for init function
     *
     * @var    string
     */
    protected $initCode;

    /**
     * Function init code setter
     * 
     * @param  string  C code snippet
     * @return bool    success status
     */
    function setInitCode($code) 
    {
        $this->initCode = $code;
        return true;
    }


    /**
     * Code snippet for deinit function
     *
     * @var    string
     */
    protected $deinitCode;

    /**
     * Function deinit code setter
     * 
     * @param  string  C code snippet
     * @return bool    success status
     */
    function setDeinitCode($code) 
    {
        $this->deinitCode = $code;
        return true;
    }

    function getPluginIncludes()
    {
       return "
#include <drizzled/sql_udf.h>
#include <drizzled/item/func.h>

#include <string>

using namespace std;

";
    }


    function regObject()
    {
      return "{$this->name}_Udf";
    }

    function getPluginCode()
    {
      $this->initPrefix   = "  udf_func **f = (udf_func**) data;\n  *f= &{$this->name}_udf;\n";
      
      $this->deinitPrefix = "  udf_func *udff = (udf_func *) data;\n  (void)udff;\n";

      $code = "";

      switch ($this->returns) {
      case "int":
	$code.="
class Item_func_{$this->name} :public Item_int_func
{
  String value;
public:
  Item_func_{$this->name}() :Item_int_func() { unsigned_flag= 1; }
  const char *func_name() const { return \"{$this->name}\"; }
  void fix_length_and_dec() { max_length= {$this->length}; }
  int64_t val_int();
};

int64_t Item_func_{$this->name}::val_int()
{
  int64_t val = args[0]->val_int();

  return val * 2;
}

Create_function<Item_func_{$this->name}> ".($this->regObject())."(string(\"{$this->name}\"));
";
	break;
      case "str":
	$code.="
class Item_func_{$this->name}: public Item_str_func
{
  String buffer;
public:
  Item_func_compress():Item_str_func(){}
  void fix_length_and_dec(){max_length= {$this->length};}
  const char *func_name() const{return \"{$this->name}\";}
  String *val_str(String *) ;
};
";
	break;
      }

      $code.= parent::getPluginCode();

      return $code;
    }
}
