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
 * @copyright  2005-2008 Hartmut Holzgraefe
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

        echo "
#ifdef HAVE_CONFIG_H
#include \"config.h\"
#endif

#include <mysql_priv.h>
#include <stdlib.h>
#include <string.h>
#include <ctype.h>
#include <my_global.h>
#include <mysql_version.h>

// TODO configure should take care of this
#ifdef DBUG_ON
#define SAFEMALLOC
#define PEDANTIC_SAFEMALLOC
#define SAFE_MUTEX
#endif

#define MYSQL_SERVER

#include <mysql/plugin.h>
        
#include \"myplugin_{$this->name}.h\"

";

        foreach ($this->headers as $header) {
            echo $header->hCode(false);
        }
        

        foreach ($this->headers as $header) {
            echo $header->hCode(true);
        }
        
        if (isset($this->code["code"]["top"])) {
            foreach ($this->code["code"]["top"] as $code) {
                echo $this->codegen->block($code, 0);
            }
        }
        foreach ($this->plugins as $plugin) {
            echo $plugin->getPluginRegistration($this);
            echo $plugin->getPluginCode()."\n";
        }

        $declarations = array();
        foreach ($this->plugins as $plugin) {
            $declarations[] = $plugin->getPluginDeclaration($this);
        }
        echo "\nmysql_declare_plugin({$this->name})\n";
        echo join(",", $declarations);
        echo "mysql_declare_plugin_end;\n\n";


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
This is a MySQL plugin library generetad using CodeGen_Mysql_Plugin <?php echo self::version(); ?>

To build the library configure it with:

<?php

         if ($this->needSource) {
             echo "    ./configure --with-mysql-src=... [--libdir=...]\n";
         } else {
             echo "    ./configure [--with-mysql=...] [--libdir=...]\n";
         }

?>
    make
    sudo make install

<?php
        if ($this->needSource) {
?>
You need to specify where to find the MySQL source for the server
you are running, this plugin library depends on server internals
not found in the public header files.
<?php
        } else {
?>
You can either specify the MySQL installation prefix or the absolute 
path to the mysql_config binary with --with-mysql=... or you can
just rely on the mysql_config being found in your PATH environment
variable.
<?php
        }
?>

With --libdir=... you can specify your MySQL servers plugin_dir path.
As this is a runtime configuration setting in the server the configure
script is not reliably able to detect where it is so you should set
it manually.

If there is no 'configure' file yet you may need to generate it using

  autoreconf -i


To register the actual plugins within this library 
execute the following SQL statements:

<?php

        foreach ($this->plugins as $plugin) {
            echo "    ".$plugin->installStatement($this)."\n";
        }

?>
To unregister the plugins execute

    FLUSH TABLES;   /* to make sure plugins are no longer referenced */
<?php

        foreach ($this->plugins as $plugin) {
            echo "    ".$plugin->uninstallStatement($this)."\n";
        }
?>

To test the plugin before installing it you need to make sure that the
following binaries and scripts are available via your $PATH:

 * mysql
 * mysql_install_db
 * mysqladmin 
 * mysqltest
 * mysqld

If you are using a binary tarball distribution or installed from source
yourself without changing the install prefix you can arrange for that 
using:

    PATH=/usr/local/mysql/bin:/usr/local/mysql/libexec:$PATH
    export PATH

You can then test the plugin using a simple

    make test

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
        echo "FLUSH TABLES;\n";
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

    function writeConfig()
    {
        parent::writeConfig();

        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/plug.in");
        
        echo "MYSQL_PLUGIN({$this->name}, [{$this->name}], [{$this->summary}], [max])\n";
        echo "MYSQL_PLUGIN_STATIC({$this->name}, [lib{$this->name}.la])\n";
        echo "MYSQL_PLUGIN_DYNAMIC({$this->name}, [{$this->name}.la])\n";

        return $file->write();
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
