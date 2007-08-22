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
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: Extension.php,v 1.21 2007/05/06 21:06:22 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen_MySQL_Plugin
 */

/**
 * includes
 */
// {{{ includes

require_once "CodeGen/MySQL/Extension.php";

require_once "CodeGen/MySQL/Plugin/Element.php";

require_once "CodeGen/MySQL/Plugin/Element/StatusVariable.php";

require_once "CodeGen/MySQL/Plugin/Element/Fulltext.php";
require_once "CodeGen/MySQL/Plugin/Element/Storage.php";
require_once "CodeGen/MySQL/Plugin/Element/Daemon.php";
require_once "CodeGen/MySQL/Plugin/Element/InformationSchema.php";

// }}} 

/**
 * A class that generates Plugin extension soure and documenation files
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL_Plugin
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_MySQL_Plugin
 */
class CodeGen_MySQL_Plugin_Extension 
    extends CodeGen_MySQL_Extension
{
    /**
    * Current CodeGen_MySQL_Plugin version number
    * 
    * @return string
    */
    function version() 
    {
        return "@package_version@";
    }

    /**
    * CodeGen_MySQL_Plugin Copyright message
    *
    * @return string
    */
    function copyright()
    {
        return "Copyright (c) 2006, 2007 Hartmut Holzgraefe";
    }

    // {{{ member variables    

    /**
     * Plugins defined by this extension
     *
     * @type array
     */
    protected $plugins = array();

    // }}} 

    
    // {{{ constructor
    
    /**
     * The constructor
     *
     */
    function __construct() 
    {
        parent::__construct();

        $this->setLang = "c++";

        $this->addConfigFragment("MYSQL_USE_PLUGIN_API()", "bottom");
    }
    
    // }}} 
    
    // {{{ output generation
        
    // {{{   docbook documentation

    // {{{ header file

    /**
     * Write the complete C header file
     *
     * @access protected
     */
    function writeHeaderFile() 
    {
        $filename = "myplugin_{$this->name}.h";
        
        $this->addPackageFile('header', $filename); 

        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/".$filename);
        
        $upname = strtoupper($this->name);
        
        echo $this->getLicenseComment();
        echo "#ifndef MYPLUGIN_{$upname}_H\n";
        echo "#define MYPLUGIN_{$upname}_H\n\n";   

        if (isset($this->code["header"]["top"])) {
            foreach ($this->code["header"]["top"] as $code) {
                echo $this->codegen->block($code, 0);
            }
        }

        foreach ($this->plugins as $plugin) {
            echo $plugin->getPluginHeader()."\n";
        }

        if (isset($this->code["header"]["bottom"])) {
            foreach ($this->code["header"]["bottom"] as $code) {
                echo $this->codegen->block($code, 0);
            }
        }
        echo "#endif /* MYPLUGIN_{$upname}_H */\n\n";

        return $file->write();
    }

    // }}} 



  // {{{ code file

    /**
     * Write the complete C code file
     *
     * @access protected
     */
    function writeCodeFile() {
        $filename = "{$this->name}.".$this->language;  
        $upname   = strtoupper($this->name);
        $lowname  = strtolower($this->name);

        $this->addPackageFile('c', $filename); 

        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/".$filename);
        
        echo $this->getLicenseComment();

        foreach ($this->headers as $header) {
            echo $header->hCode(false);
        }
        
        echo "
#ifdef HAVE_CONFIG_H
#include \"config.h\"
#endif

#include <stdlib.h>
#include <string.h>
#include <ctype.h>
#include <my_global.h>

// TODO configure should take care of this
#ifdef DBUG_ON
#define SAFEMALLOC
#define PEDANTIC_SAFEMALLOC
#define SAFE_MUTEX
#endif
#include <mysql_version.h>
#include <mysql/plugin.h>
";
        
        if ($this->needSource) {
            echo "#include <mysql_priv.h>\n";
        }

        echo "#include \"myplugin_{$this->name}.h\"\n";

        foreach ($this->headers as $header) {
            echo $header->hCode(true);
        }
        
        if (isset($this->code["code"]["top"])) {
            foreach ($this->code["code"]["top"] as $code) {
                echo $this->codegen->block($code, 0);
            }
        }
        foreach ($this->plugins as $plugin) {
            echo $plugin->getPluginCode()."\n";
            echo $plugin->getPluginRegistration($this);
        }

        if (isset($this->code["code"]["bottom"])) {
            foreach ($this->code["code"]["bottom"] as $code) {
                echo $this->codegen->block($code, 0);
            }
        }
        echo $this->cCodeEditorSettings();

        return $file->write();
    }

    // }}} 


    /** 
    * Generate README file (custom or default)
    *
    * @param  protected
    */
    function writeReadme() 
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/README");

?>
This is a MySQL plugin generetad using CodeGen_Mysql_Plugin <?php echo self::version(); ?>

...
<?php

      return $file->write();
    }


    /** 
    * Generate INSTALL file (custom or default)
    *
    * @access protected
    */
    function writeInstall() 
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/INSTALL");

?>
This is a MySQL plugin generetad using CodeGen_Mysql_Plugin <?php echo self::version(); ?>

...
<?php

        $file->write();
    }


    /**
     * Add a plugin to the extension
     * 
     * @param object the plugin to add
     */
    function addPlugin(CodeGen_MySQL_Plugin_Element $plugin)
    {
        $this->plugins[$plugin->getName()] = $plugin;

        if ($plugin->getRequiresSource()) {
            $this->needSource = true;
        }
    }


    function writeTests()
    {
        parent::writeTests();

        $this->addPackageFile("test", "tests/install_plugins.inc");
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/tests/install_plugins.inc");      
        echo "-- disable_warnings\n";
        foreach ($this->plugins as $plugin) {
            echo $plugin->installStatement($this)."\n";
        }
        echo "-- enable_warnings\n";
        $file->write();

        $this->addPackageFile("test", "tests/uninstall_plugins.inc");
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/tests/uninstall_plugins.inc");        
        foreach ($this->plugins as $plugin) {
            echo $plugin->uninstallStatement($this)."\n";
        }
        $file->write();
    }

    function testFactory()
    {
        return new CodeGen_MySQL_Plugin_Element_Test(); 
    }

    function isValid() 
    {
        foreach ($this->plugins as $plugin) {
            $err = $plugin->isValid();
            if (PEAR::isError($err)) {
                return $err;
            }
        }

        return true;
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
