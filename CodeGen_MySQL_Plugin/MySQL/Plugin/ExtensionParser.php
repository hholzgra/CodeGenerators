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
 * @version    CVS: $Id: ExtensionParser.php,v 1.11 2007/05/06 08:20:19 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen_MySQL_Plugin
 */


/**
 * includes
 */
require_once "CodeGen/MySQL/ExtensionParser.php";

/**
 * A class that generates MySQL Plugin soure and documenation files
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL_Plugin
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_MySQL_Plugin
 */
class CodeGen_MySQL_Plugin_ExtensionParser 
    extends CodeGen_MySQL_ExtensionParser
{
    function __construct($extension)
    {
        parent::__construct($extension);
        
        $this->addTagAlias("plugin", "extension");
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

        $classname = "CodeGen_Mysql_Plugin_Element_".$classname;

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

    function end_generic_init($attr, $data)
    {
        return $this->helper->setInitCode($data);
    }
 
    function end_generic_deinit($attr, $data)
    {
        return $this->helper->setDeinitCode($data);
    }

    function start_generic_statusvar($attr)
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

        $err = CodeGen_MySQL_Plugin_Element_StatusVariable::isName($attr["name"]);
        if ($err !== true) {
            return PEAR::raiseError("'$name' is not a valid status variable name");
        }

        $err = CodeGen_MySQL_Plugin_Element_StatusVariable::isValidType($attr["type"]);
        if ($err !== true) {
            return $err;
        }

        if (!isset($attr["value"])) {
            $attr["value"] = $attr["name"];
        }

        $var = new CodeGen_MySQL_Plugin_Element_StatusVariable($attr['type'], $attr['name'], $attr['value'], $attr['init']);

        return $this->pushHelper($var);
    }

    function end_generic_statusvar($attr)
    {
        $var = $this->helper;
        $this->popHelper();
        return $this->helper->addStatusVariable($var);
    }

    function start_generic_systemvar($attr)
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

        $err = CodeGen_MySQL_Plugin_Element_SystemVariable::isName($attr["name"]);
        if ($err !== true) {
            return PEAR::raiseError("'$name' is not a valid system variable name");
        }

        $err = CodeGen_MySQL_Plugin_Element_SystemVariable::isValidType($attr["type"]);
        if ($err !== true) {
            return $err;
        }

        $err = CodeGen_MySQL_Plugin_Element_SystemVariable::isValidScope($attr["scope"]);
        if ($err !== true) {
            return $err;
        }

        $var = new CodeGen_MySQL_Plugin_Element_SystemVariable($attr['scope'],
                                                               $attr['type'], 
                                                               $attr['name']);

        if (isset($attr["default"]))  $var->setDefault($attr["default"]);
        if (isset($attr["min"]))      $var->setMin($attr["min"]);
        if (isset($attr["max"]))      $var->setMax($attr["max"]);
        if (isset($attr["readonly"])) $var->setReadonly($this->toBool($attr["readonly"]));

        return $this->pushHelper($var);
    }

    function end_generic_systemvar($attr)
    {
        $var = $this->helper;
        $this->popHelper();
        return $this->helper->addSystemVariable($var);
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
    
    function tagend_extension_code($attr, $data) {
        return $this->tagend_extension_code($attr, $data);
    }

    function tagend_extension_acinclude($attr, $data) {
        return $this->tagend_extension_acinclude($attr, $data);
    }

    function tagend_extension_configure($attr, $data) {
        return $this->tagend_deps_configm4($attr, $data);
    }

    function tagstart_statusvar_statusvar($attr) {
        return $this->start_generic_statusvar($attr);
    }

    function tagend_statusvar_statusvar($attr, $data) {
        return $this->end_generic_statusvar($attr, $data);
    }

    //     ____                                   
    //    |  _ \  __ _  ___ _ __ ___   ___  _ __  
    //    | | | |/ _` |/ _ \ '_ ` _ \ / _ \| '_ \ 
    //    | |_| | (_| |  __/ | | | | | (_) | | | |
    //    |____/ \__,_|\___|_| |_| |_|\___/|_| |_|

    
    // default plugin tags

    function tagstart_extension_daemon($attr)
    {
        return $this->start_generic_plugin("Daemon", $attr);
    }

    function tagend_extension_daemon($attr, $data)
    {
        return $this->end_generic_plugin($attr, $data);
    }

    function tagend_daemon_init($attr, $data)
    {
        return $this->end_generic_init($attr, $data);
    }

    function tagend_daemon_deinit($attr, $data)
    {
        return $this->end_generic_deinit($attr, $data);
    }

    function tagstart_daemon_statusvar($attr)
    {
        return $this->start_generic_statusvar($attr);
    }

    function tagend_daemon_statusvar($attr, $data)
    {
        return $this->end_generic_statusvar($attr, $data);
    }

    function tagstart_daemon_systemvar($attr)
    {
        return $this->start_generic_systemvar($attr);
    }

    function tagend_daemon_systemvar($attr, $data)
    {
        return $this->end_generic_systemvar($attr, $data);
    }

    function tagend_daemon_summary($attr, $data)
    {
        return $this->end_generic_summary($attr, $data);
    }


    //     _____      _ _ _            _   
    //    |  ___|   _| | | |_ _____  _| |_ 
    //    | |_ | | | | | | __/ _ \ \/ / __|
    //    |  _|| |_| | | | ||  __/>  <| |_ 
    //    |_|   \__,_|_|_|\__\___/_/\_\\__|


    // default plugin tags

    function tagstart_extension_fulltext($attr)
    {
        return $this->start_generic_plugin("Fulltext", $attr);
    }

    function tagend_extension_fulltext($attr, $data)
    {
        return $this->end_generic_plugin($attr, $data);
    }

    function tagend_fulltext_init($attr, $data)
    {
        return $this->end_generic_init($attr, $data);
    }

    function tagend_fulltext_deinit($attr, $data)
    {
        return $this->end_generic_deinit($attr, $data);
    }

    function tagstart_fulltext_statusvar($attr)
    {
        return $this->start_generic_statusvar($attr);
    }

    function tagend_fulltext_statusvar($attr, $data)
    {
        return $this->end_generic_statusvar($attr, $data);
    }

    function tagstart_fulltext_systemvar($attr)
    {
        return $this->start_generic_systemvar($attr);
    }

    function tagend_fulltext_systemvar($attr, $data)
    {
        return $this->end_generic_systemvar($attr, $data);
    }

    function tagend_fulltext_summary($attr, $data)
    {
        return $this->end_generic_summary($attr, $data);
    }

    // plugin specific tags

    function tagend_fulltext_parser($attr, $data)
    {
        return true;
    }

    function tagend_fulltext_parser_code($attr, $data)
    {
        return $this->helper->setParserCode($data);
    }

    function tagend_fulltext_parser_init($attr, $data)
    {
        return $this->helper->setParserInit($data);
    }

    function tagend_fulltext_parser_deinit($attr, $data)
    {
        return $this->helper->setParserDeinit($data);
    }



    //    ____  _                             
    //   / ___|| |_ ___  _ __ __ _  __ _  ___ 
    //   \___ \| __/ _ \| '__/ _` |/ _` |/ _ \
    //    ___) | || (_) | | | (_| | (_| |  __/
    //   |____/ \__\___/|_|  \__,_|\__, |\___|
    //                             |___/      
        
    // default plugin tags

    function tagstart_extension_storage($attr)
    {
        return $this->start_generic_plugin("Storage", $attr);
    }

    function tagend_extension_storage($attr, $data)
    {
        return $this->end_generic_plugin($attr, $data);
    }

    function tagend_storage_init($attr, $data)
    {
        return $this->end_generic_init($attr, $data);
    }

    function tagend_storage_deinit($attr, $data)
    {
        return $this->end_generic_deinit($attr, $data);
    }

    function tagstart_storage_statusvar($attr)
    {
        return $this->start_generic_statusvar($attr);
    }

    function tagend_storage_statusvar($attr, $data)
    {
        return $this->end_generic_statusvar($attr, $data);
    }

    function tagstart_storage_systemvar($attr)
    {
        return $this->start_generic_systemvar($attr);
    }

    function tagend_storage_systemvar($attr, $data)
    {
        return $this->end_generic_systemvar($attr, $data);
    }

    function tagend_storage_summary($attr, $data)
    {
        return $this->end_generic_summary($attr, $data);
    }

    // plugin specific tags
    function tagstart_storage_function($attr)
    {
        $err = $this->checkAttributes($attr, array(), array("name"));
        if (PEAR::isError($err)) {
            return $err;
        }
    }

    function tagend_storage_function($attr, $data)
    {
        $this->helper->setFunction($attr["name"], $data);
    }

    function tagstart_storage_fileextension($attr, $data)
    {
        $err = $this->checkAttributes($attr, array(), array("name"));
        if (PEAR::isError($err)) {
            return $err;
        }

        $this->helper->addFileExtension($attr["name"]);
    }

    function tagend_storage_class($attr, $data)
    {
        $this->helper->setClassExtra($data);
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

    function tagend_infoschema_init($attr, $data)
    {
        return $this->end_generic_init($attr, $data);
    }

    function tagend_infoschema_deinit($attr, $data)
    {
        return $this->end_generic_deinit($attr, $data);
    }

    function tagstart_infoschema_statusvar($attr)
    {
        return $this->start_generic_statusvar($attr);
    }

    function tagend_infoschema_statusvar($attr, $data)
    {
        return $this->end_generic_statusvar($attr, $data);
    }

    function tagstart_infoschema_systemvar($attr)
    {
        return $this->start_generic_systemvar($attr);
    }

    function tagend_infoschema_systemvar($attr, $data)
    {
        return $this->end_generic_systemvar($attr, $data);
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
