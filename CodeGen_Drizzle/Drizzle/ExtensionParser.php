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
 * @version    CVS: $Id: ExtensionParser.php,v 1.11 2007/05/06 08:20:19 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen_Drizzle
 */


/**
 * includes
 */
require_once "CodeGen/ExtensionParser.php";
require_once "CodeGen/Maintainer.php";
require_once "CodeGen/Tools/Indent.php";

/**
 * A class that generates Drizzle Plugin soure and documenation files
 *
 * @category   Tools and Utilities
 * @package    CodeGen_Drizzle
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2009 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_Drizzle
 */
class CodeGen_Drizzle_ExtensionParser 
    extends CodeGen_ExtensionParser
{
    function __construct($extension)
    {
        parent::__construct($extension);
        
        $this->addTagAlias("plugin", "extension");
    }

    function tagend_deps_src($attr, $data) 
    {
        $this->extension->setNeedSource(true);
    }

    //   ____                      _        _                  
    //  / ___| ___ _ __   ___ _ __(_) ___  | |_ __ _  __ _ ___ 
    // | |  _ / _ \ '_ \ / _ \ '__| |/ __| | __/ _` |/ _` / __|
    // | |_| |  __/ | | |  __/ |  | | (__  | || (_| | (_| \__ \
    //  \____|\___|_| |_|\___|_|  |_|\___|  \__\__,_|\__, |___/
    //                                               |___/     
    
    function start_generic_plugin($classname, $attr)
    {
        $err = $this->checkAttributes($attr, array(), array("name"));
        if (PEAR::isError($err)) {
            return $err;
        }

        $classname = "CodeGen_Drizzle_Element_".$classname;

        $this->pushHelper(new $classname);
        $this->helper->setName($attr["name"]);
    }

    function end_generic_plugin($attr, $data)
    {
        $err = $this->extension->addPlugin($this->helper);
        if (PEAR::isError($err)) {
            return $err;
        }

        $this->popHelper();
        return $err;        
    }

    function end_generic_summary($attr, $data)
    {
        return $this->helper->setSummary($data);
    }


    //  ____  _             _         _                  
    // |  _ \| |_   _  __ _(_)_ __   | |_ __ _  __ _ ___ 
    // | |_) | | | | |/ _` | | '_ \  | __/ _` |/ _` / __|
    // |  __/| | |_| | (_| | | | | | | || (_| | (_| \__  \
    // |_|   |_|\__,_|\__, |_|_| |_|  \__\__,_|\__, |___/
    //                |___/                    |___/     

    function tagstart_plugin($attr) 
    {
        return $this->tagstart_extension($attr);
    }
    
    function tagend_extension_init($attr, $data)
    {
        return $this->extension->addInitCode($data);
    }
 
    function tagend_extension_deinit($attr, $data)
    {
        return $this->extension->addDeinitCode($data);
    }

    function tagend_extension_code($attr, $data) {
        return $this->tagend_extension_code($attr, $data);
    }

    function tagend_extension_acinclude($attr, $data) {
        return $this->tagend_extension_acinclude($attr, $data);
    }

    function tagend_extension_configure($attr, $data) {
        return $this->tagend_deps_configm4($attr, $data);
    }

    function tagstart_extension_statusvar($attr)
    {
        $err = $this->checkAttributes($attr, array("type", "name", "value", "init"));
        if (PEAR::isError($err)) {
            return $err;
        }
        
        if (!isset($attr["type"])) {
            return PEAR::raiseError("type attribut for fulltext plugin missing");
        }
        if (!isset($attr["name"])) {
            return PEAR::raiseError("name attribut for fulltext plugin missing");
        }
        if (!isset($attr["value"])) {
            $attr["value"] = $attr["name"];
        }
        if (!isset($attr["init"])) {
            $attr["init"] = false;
        }

        $err = CodeGen_Drizzle_Element_StatusVariable::isName($attr["name"]);
        if ($err !== true) {
            return PEAR::raiseError("'$name' is not a valid status variable name");
        }

        $err = CodeGen_Drizzle_Element_StatusVariable::isValidType($attr["type"]);
        if ($err !== true) {
            return $err;
        }

        if (!isset($attr["value"])) {
            $attr["value"] = $attr["name"];
        }

        $var = new CodeGen_Drizzle_Element_StatusVariable($attr['type'], $attr['name'], $attr['value'], $attr['init']);

        return $this->pushHelper($var);
    }

    function tagend_extension_statusvar($attr)
    {
        $var = $this->helper;
        $this->popHelper();
        return $this->extension->addStatusVariable($var);
    }

    function tagstart_extension_systemvar($attr)
    {
        $err = $this->checkAttributes($attr, array("scope", "type", "name", "comment", "min", "max", "default"));
        if (PEAR::isError($err)) {
            return $err;
        }
        
        if (!isset($attr["scope"])) {
            return PEAR::raiseError("scope attribut for system variable missing");
        }
        if (!isset($attr["type"])) {
            return PEAR::raiseError("type attribut for system variable missing");
        }
        if (!isset($attr["name"])) {
            return PEAR::raiseError("name attribut for system variable missing");
        }
        if (!isset($attr["default"])) {
            $attr["0"] = false;
        }

        $err = CodeGen_Drizzle_Element_SystemVariable::isName($attr["name"]);
        if ($err !== true) {
            return PEAR::raiseError("'$name' is not a valid system variable name");
        }

        $err = CodeGen_Drizzle_Element_SystemVariable::isValidType($attr["type"]);
        if ($err !== true) {
            return $err;
        }

        $err = CodeGen_Drizzle_Element_SystemVariable::isValidScope($attr["scope"]);
        if ($err !== true) {
            return $err;
        }

        $var = new CodeGen_Drizzle_Element_SystemVariable($attr['scope'],
                                                               $attr['type'], 
                                                               $attr['name']);

        if (isset($attr["default"]))  $var->setDefault($attr["default"]);
        if (isset($attr["min"]))      $var->setMin($attr["min"]);
        if (isset($attr["max"]))      $var->setMax($attr["max"]);
        if (isset($attr["readonly"])) $var->setReadonly($this->toBool($attr["readonly"]));

        return $this->pushHelper($var);
    }

    function tagend_extension_systemvar($attr)
    {
        $var = $this->helper;
        $this->popHelper();
        return $this->extension->addSystemVariable($var);
    }

    // ErrMsg
    
    // default plugin tags

    function tagstart_extension_errmsg($attr)
    {
        return $this->start_generic_plugin("Errmsg", $attr);
    }

    function tagend_extension_errmsg($attr, $data)
    {
        return $this->end_generic_plugin($attr, $data);
    }

    function tagend_errmsg_summary($attr, $data)
    {
        return $this->end_generic_summary($attr, $data);
    }

    // UDF function
    
    // default plugin tags

    function tagstart_extension_udf($attr)
    {
        return $this->start_generic_plugin("Udf", $attr);
    }

    function tagend_extension_udf($attr, $data)
    {
        return $this->end_generic_plugin($attr, $data);
    }

    function tagend_udf_summary($attr, $data)
    {
        return $this->end_generic_summary($attr, $data);
    }

    function tagstart_udf_function($attr)
    {
        if (isset($attr["name"])) {
            $err = $this->helper->setName($attr["name"]);
            if (PEAR::isError($err)) {
                return $err;
            }
        } else {
            return PEAR::raiseError("name attribut for function missing");
        }
        
        if (isset($attr['type'])) {
            $err = $this->helper->setType($attr['type']);
            if (PEAR::isError($err)) {
                return $err;
            }
        }
        
        if (isset($attr["returns"])) {
            $err = $this->helper->setReturns($attr["returns"]);
            if (PEAR::isError($err)) {
                return $err;
            }
        }
        
        if (isset($attr['null'])) {
            $null = $this->helper->toBool($attr["null"], "null");
            if (PEAR::isError($null)) {
                return $null;
            }
            $err = $this->setNull($null);
            if (PEAR::isError($err)) {
                return $err;
            }
        }

        if (isset($attr['length'])) {
            $err = $this->helper->setLength($attr['length']);
            if (PEAR::isError($err)) {
                return $err;
            }
        }
        
        if (isset($attr['decimals'])) {
            $err = $this->helper->setDecimals($attr['decimals']);
            if (PEAR::isError($err)) {
                return $err;
            }
        }
        
        if (isset($attr["if"])) {
            $this->helper->setIfCondition($attr["if"]);
        }
        
        return true;
    }

    function tagend_udf_function_summary($attr, $data) 
    {
        return $this->helper->setSummary(trim($data));
    }
    
    function tagend_udf_function_description($attr, $data) 
    {
        return $this->helper->setDescription(CodeGen_Tools_Indent::linetrim($data));
    }
    
    function tagend_udf_function_proto($attr, $data)
    {
        return $this->helper->setProto(trim($data));
    }
    
    
    function tagend_udf_function_code($attr, $data)
    {
        $data = CodeGen_Tools_Indent::linetrim($data);
        
        return $this->helper->setCode($data);
    }
    

    
    
    function tagstart_udf_function_param($attr) 
    {
        if (!isset($attr['name'])) {
            return PEAR::raiseError("name attribut for parameter missing");
        }
        
        if (!isset($attr['type'])) {
            return PEAR::raiseError("type attribut for parameter missing");
        }
        
        return $this->helper->addParam($attr['name'], $attr['type'], @$attr['optional'], @$attr['default']);
    }

    function tagstart_udf_function_data($attr) 
    {
    }
    
    function tagstart_udf_function_data_element($attr) 
    {
        if (!isset($attr['name'])) {
            return PEAR::raiseError("name attribut for data element missing");                
        }
        
        if (!isset($attr['type'])) {
            return PEAR::raiseError("type attribut for data element missing");                
        }

        return $this->helper->addDataElement($attr['name'], $attr['type'], @$attr['default']);
    }
        
    function tagend_udf_function_init($attr, $data) 
    {
        return $this->helper->setInitCode($data);
    }

    function tagend_udf_function_deinit($attr, $data) 
    {
        return $this->helper->setDeinitCode($data);
    }
    
    function tagend_udf_function($attr, $data) 
    {
        //TODO check integrity here

        return true;
    }


    //  ___        __          ____       _                          
    // |_ _|_ __  / _| ___    / ___|  ___| |__   ___ _ __ ___   __ _ 
    //  | || '_ \| |_ / _ \   \___ \ / __| '_ \ / _ \ '_ ` _ \ / _` |
    //  | || | | |  _| (_) |   ___) | (__| | | |  __/ | | | | | (_| |
    // |___|_| |_|_|  \___(_) |____/ \___|_| |_|\___|_| |_| |_|\__,_|
 
    // default plugin tags

    function tagstart_extension_infoschema($attr)
    {
        return $this->start_generic_plugin("InformationSchema", $attr);
    }

    function tagend_extension_infoschema($attr, $data)
    {
        return $this->end_generic_plugin($attr, $data);
    }

    function tagend_infoschema_summary($attr, $data)
    {
        return $this->end_generic_summary($attr, $data);
    }

    // plugin specific tags
    function tagstart_infoschema_field($attr)
    {
        $err = $this->checkAttributes($attr, array("name", "type", "length", "null", "default"));
        if (PEAR::isError($err)) {
            return $err;
        }
        
        if (!isset($attr["name"])) {
            return PEAR::raiseError("name attribut for information schema field missing");
        }

        if (!isset($attr["type"])) {
            return PEAR::raiseError("type attribut for information schema field missing");
        }

        if (!isset($attr["length"])) {
            $attr["length"] = false;
        }

        if (isset($attr["null"])) {
            $null = $this->toBool($attr["null"]);
            if (PEAR::isError($null)) {
                return $null;
            }
        } else {
            $null = false;
        }

        if (!isset($attr["default"])) {
            $attr["default"] = false;
        }

        return $this->helper->addField($attr["name"], $attr["type"], $attr["length"], $null, $attr["default"]);
    }

    function tagend_infoschema_code($attr, $data)
    {
        $this->helper->setCode($data);
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
