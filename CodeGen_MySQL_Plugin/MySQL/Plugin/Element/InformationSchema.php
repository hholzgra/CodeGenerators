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
 * @version    CVS: $Id: InformationSchema.php,v 1.5 2007/04/27 22:12:39 hholzgra Exp $
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

class CodeGen_MySQL_Plugin_Element_InformationSchema
  extends CodeGen_MySQL_Plugin_Element
{
  protected $fields = array();

  protected $code = "";

  function __construct()
  {
    $this->initPrefix = "  ST_SCHEMA_TABLE *schema = (ST_SCHEMA_TABLE *)data;\n";
    $this->deinitPrefix = "  ST_SCHEMA_TABLE *schema = (ST_SCHEMA_TABLE *)data;\n";

    // this plugin type requires header files not installed by "make install"
    $this->requiresSource = true;
  }


  function setName($name)
  {
    $err = parent::setName($name);
    if (PEAR::isError($err)) {
      return $err;
    }

    $this->initPrefix.= "  schema->fields_info = {$name}_field_info;\n";
    $this->initPrefix.= "  schema->fill_table = {$name}_fill_table;\n";

    return true;
  }

  function setCode($code)
  {
    $this->code = $code;
  }

    /**
     * Plugin type specifier is needed for plugin registration
     *
     * @param  void
     * @return string
     */
    function getPluginType() 
    {
      return "MYSQL_INFORMATION_SCHEMA_PLUGIN";
    }

    function getPluginCode()
    {
      $code = "
bool schema_table_store_record(THD *thd, TABLE *table);

static struct st_mysql_information_schema {$this->name}_descriptor =
{ 
  MYSQL_INFORMATION_SCHEMA_INTERFACE_VERSION
};

";

      $code.= "
ST_FIELD_INFO {$this->name}_field_info[] =
{
";

      foreach ($this->fields as $field) {
        $code.= '  {';
        $code.= '"'.$field['name'].'", ';
        $code.= $field['length'].', ';
        $code.= "MYSQL_TYPE_".strtoupper($field['type']).', ';
        $code.= $field['default'].', ';
        $code.= ($field['null'] ? '1' : '0').', ';
        $code.= "NULL},\n";
      }

      $code.= "  {0, 0, MYSQL_TYPE_STRING,0, 0, 0}\n";
      $code.= "};\n\n";

      $n = 0;
      foreach ($this->fields as $field) {
        $code.= sprintf("#define FIELD_%-20s %d\n", $field["name"], $n++);
      }

      $code.= "int {$this->name}_fill_table(THD *thd, TABLE_LIST *tables, COND *cond)\n";
      $code.= "{\n";
      $code.= $this->code;
      $code.= "\n};\n\n";

      foreach ($this->fields as $field) {
        $code.= "#undef FIELD_$field[name]\n";
      }   

      $code.= parent::getPluginCode();


      return $code;
    }

    function addField($name, $type, $length = 0, $null = false, $default = 0)
    {
        if (!self::isName($name)) {
            return PEAR::raiseError("'$name' is not a valid information schema field name");
        }
       
        if (isset($this->fields[$name])) {
            return PEAR::raiseError("duplicate field name '$name'");
        }

        switch ($type) {
            case "DECIMAL": 
              $type = "NEWDECIMAL";
              break;
            case "NEWDECIMAL":
            case "LONG":
            case "STRING":
              break;
            /* TODO support all types 
            case "TINY":
            case "SHORT":  
            case "FLOAT":  
            case "DOUBLE":
            case "NULL":   
            case "TIMESTAMP":
            case "LONGLONG":
            case "INT24":
            case "DATE":   
            case "TIME":
            case "DATETIME": 
            case "YEAR":
            case "NEWDATE": 
            case "VARCHAR":
            case "BIT":
            case "ENUM":
            case "SET":
            case "TINY_BLOB":
            case "MEDIUM_BLOB":
            case "LONG_BLOB":
            case "BLOB":
            case "VAR_STRING":
            case "GEOMETRY":
            */
            default:
                return PEAR::raiseError("'$type' is not a valid information schema field type");
        }

        if (!$length) {
            switch ($type) {
                case "LONG":
                    $length = "MY_INT64_NUM_DECIMAL_DIGITS";
                    break;
                case "STRING":
                    $length = "NAME_CHAR_LEN";
                    break;
                case "NEWDECIMAL":
                    $length = "32";  
                    break;
                default:
                    break;
            }
        }

        if (empty($default)) {
            $default = 0;
        }

        $this->fields[$name] = array("name"    => $name, 
                                     "type"    => $type,
                                     "length"  => $length,
                                     "null"    => $null,
                                     "default" => $default);
    }

    function needsSource()
    {
        return true;
    }
}