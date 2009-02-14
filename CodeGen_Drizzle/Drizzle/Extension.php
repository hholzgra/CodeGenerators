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
 * @version    CVS: $Id: Extension.php,v 1.21 2007/05/06 21:06:22 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen_Drizzle
 */

/**
 * includes
 */
// {{{ includes

require_once "System.php";

require_once "CodeGen/Extension.php";

require_once "CodeGen/Maintainer.php";
require_once "CodeGen/License.php";
require_once "CodeGen/Tools/Platform.php";
require_once "CodeGen/Tools/Indent.php";

require_once "CodeGen/Drizzle/Element.php";
require_once "CodeGen/Drizzle/Element/Test.php";

require_once "CodeGen/Drizzle/Element/StatusVariable.php";
require_once "CodeGen/Drizzle/Element/SystemVar.php";

require_once "CodeGen/Drizzle/Element/Fulltext.php";
require_once "CodeGen/Drizzle/Element/Storage.php";
require_once "CodeGen/Drizzle/Element/Daemon.php";
require_once "CodeGen/Drizzle/Element/InformationSchema.php";

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
class CodeGen_Drizzle_Extension 
    extends CodeGen_Extension
{
    /** 
     * Custom test cases
     *
     * @var array
     */
    protected $testcases = array();

    /**
     * Is the full source needed to compile this?
     *
     * @var bool
     */
    protected $needSource = false;

    /**
     * The prefix for archive files created by "make dist"
     *
     * @var string
     */
    protected $archivePrefix = "Drizzle";

    // {{{ member adding functions

    /**
     * Do we need the full source to compile? 
     *
     * @param  bool 
     */
    function setNeedSource($flag)
    {
        $this->needSource = (bool)$flag;
    }
    
    // }}} 


    /**
    * Current CodeGen_Drizzle version number
    * 
    * @return string
    */
    function version() 
    {
        return "@package_version@";
    }

    /**
    * CodeGen_Drizzle Copyright message
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

        $this->setLanguage("c++");

        $this->addConfigFragment("DRIZZLE_USE_PLUGIN_API()", "bottom");
    }
    
    // }}} 
    

    // {{{ license and authoers
    /**
     * Create the license part of the source file header comment
     *
     * @return string  code fragment
     */
    function getLicenseComment() 
    {    
        $code = "/*\n";
        $code.= "   +----------------------------------------------------------------------+\n";
        
        if (is_object($this->license)) {
            $code.= $this->license->getComment();
        } else {
            $code.= sprintf("   | unkown license: %-52s |\n", $this->license);
        }
        
        $code.= "   +----------------------------------------------------------------------+\n";
        
        foreach ($this->authors as $author) {
            $code.= $author->comment();
        }
        
        $code.= "   +----------------------------------------------------------------------+\n";
        $code.= "*/\n\n";
        
        $code.= "/* $ Id: $ */ \n\n";
        
        return $code;
    }
    
    // }}} 


    /**
     * Write authors to the AUTHORS file
     *
     * @access protected
     */
    function writeAuthors() 
    {
        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/AUTHORS");
        if (count($this->authors)) {
            $this->addPackageFile("doc", "AUTHORS");
            echo "{$this->name}\n";
            $names = array();
            foreach ($this->authors as $author) {
                $names[] = $author->getName();
            }
            echo join(", ", $names) . "\n";
        }
        
        return $file->write();
    }


    /**
    * Write EXPERIMENTAL file for non-stable extensions
    *
    * @access protected
    */
    function writeExperimental() 
    {
        if (($this->release) && isset($this->release->state) && $this->release->state !== 'stable') {
            $this->addPackageFile("doc", "EXPERIMENTAL");


            $file =  new CodeGen_Tools_Outbuf($this->dirpath."/EXPERIMENTAL");
?>
this extension is experimental,
its functions may change their names 
or move to extension all together 
so do not rely to much on them 
you have been warned!
<?php

            return $file->write();
        }
    }


    /** 
    * Generate NEWS file (custom or default)
    *
    * @access protected
    */
    function writeNews() 
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/NEWS");

?>
This is a source project generated by <?php echo get_class($this) . " " . $this->version(); ?>

...
<?php

        return $file->write();
    }


    /** 
    * Generate ChangeLog file (custom or default)
    *
    * @access protected
    */
    function writeChangelog() 
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/ChangeLog");
?>
This is a source project generated by <?php echo get_class($this) . " " . $this->version(); ?>

...
<?php

        $file->write();
    }


    /**
     * Create all project files
     *
     * @param  string Directory to create (default is ./$this->name)
     */
    function createExtension($dirpath = false, $force = false) 
    {
        // check whether the specification information is valid
        $err = $this->isValid();
        if (PEAR::isError($err)) {
            return $err;
        }

        // default: create dir in current working directory, 
        // dirname is the extensions base name
        if (empty($dirpath) || $dirpath == ".") {
            $dirpath = "./" . $this->name;
        } 
        
        // purge and create extension directory
        if (file_exists($dirpath)) {
            if ($force) {
                if (!is_writeable($dirpath)) {
                    return PEAR::raiseError("can't write to target dir '$dirpath'");
                }
            } else {
                return PEAR::raiseError("'$dirpath' already exists, can't create that directory (use '--force' to override)"); 
            }
        } else if (!@System::mkdir("-p $dirpath")) {
            return PEAR::raiseError("can't create '$dirpath'");
        }
        
        // make path absolute to be independant of working directory changes
        $this->dirpath = realpath($dirpath);
        
        echo "Creating '{$this->name}' extension in '$dirpath'\n";
        
        // generate complete source code
        $this->generateSource();
        
        // generate README file
        $this->writeReadme();

        // write test files
        $this->writeTests();

        // generate INSTALL file
        $this->writeInstall();

        // generate NEWS file
        $this->writeNews();
        
        // generate ChangeLog file
        $this->writeChangelog();

        // generate AUTHORS file
        $this->writeAuthors();

        // generate Documentation
        if (method_exists($this, "generateDocumentation")) {
            $this->generateDocumentation();
        }

        // copy additional source files
        if (isset($this->packageFiles['copy'])) {
            foreach ($this->packageFiles['copy'] as $basename => $filepath) {
                copy($filepath, $this->dirpath."/".$basename);
            }
        }

        // generate autoconf/automake files 
        // this needs to be called last as others may register
        // extra package files
        $this->writeConfig();

        return true;
    }
    
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

";
        // prepended header files
        foreach ($this->headers as $header) {
            echo $header->hCode(true);
        }

/* TODO
needed for --with-drizzle-src builds? 
#include <drizzle_priv.h>
*/

        echo "
#include <stdlib.h>
#include <string.h>
#include <ctype.h>
#include <my_global.h>
#include <drizzle_version.h>

// TODO configure should take care of this
#ifdef DBUG_ON
#define SAFEMALLOC
#define PEDANTIC_SAFEMALLOC
#define SAFE_MUTEX
#endif

#define DRIZZLE_SERVER

#include <drizzle/plugin.h>
        
#include \"myplugin_{$this->name}.h\"

";

        // non-prepended header files
        foreach ($this->headers as $header) {
            echo $header->hCode(false);
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
        echo "\ndrizzle_declare_plugin({$this->name})\n";
        echo join(",", $declarations);
        echo "drizzle_declare_plugin_end;\n\n";


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
This is a Drizzle plugin generetad using CodeGen_Drizzle <?php echo self::version(); ?>

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
This is a Drizzle plugin library generetad using CodeGen_Drizzle <?php echo self::version(); ?>

To build the library configure it with:

<?php

         if ($this->needSource) {
             echo "    ./configure --with-drizzle-src=... [--libdir=...]\n";
         } else {
             echo "    ./configure [--with-drizzle=...] [--libdir=...]\n";
         }

?>
    make
    sudo make install

<?php
        if ($this->needSource) {
?>
You need to specify where to find the Drizzle source for the server
you are running, this plugin library depends on server internals
not found in the public header files.
<?php
        } else {
?>
You can either specify the Drizzle installation prefix or the absolute 
path to the drizzle_config binary with --with-drizzle=... or you can
just rely on the drizzle_config being found in your PATH environment
variable.
<?php
        }
?>

With --libdir=... you can specify your Drizzle servers plugin_dir path.
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

 * drizzle
 * drizzle_install_db
 * drizzleadmin 
 * drizzletest
 * drizzled

If you are using a binary tarball distribution or installed from source
yourself without changing the install prefix you can arrange for that 
using:

    PATH=/usr/local/drizzle/bin:/usr/local/drizzle/libexec:$PATH
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
    function addPlugin(CodeGen_Drizzle_Element $plugin)
    {
        $this->plugins[$plugin->getName()] = $plugin;

        if ($plugin->getRequiresSource()) {
            $this->needSource = true;
        }
    }


    /**
     * Create the extensions code soure and project files
     *
     * @access  protected
     */
    function generateSource() 
    {
        // generate source and header files
        $this->writeHeaderFile();
        $this->writeCodeFile();

        // generate .cvsignore file entries
        $this->writeDotCvsignore();

        // generate EXPERIMENTAL file for unstable release states
        $this->writeExperimental();
        
        // generate LICENSE file if license given
        if ($this->license) {
            $this->license->writeToFile($this->dirpath."/COPYING");
            $this->files['doc'][] = "COPYING";
        }
    }


    /**
     * add a custom test case
     *
     * @access public
     * @param  object  a Test object
     */
    function addTest(CodeGen_Drizzle_Element_Test $test) 
    {
        $name = $test->getName();
       
        if (isset($this->testcases[$name])) {
            return PEAR::raiseError("testcase '{$name}' added twice");
        }

        $this->testcases[$name] = $test;
        return true;
    }

    /**
     * Create test files
     *
     * @void
     */
    function writeTests()
    {
        @mkdir($this->dirpath."/tests");
        @mkdir($this->dirpath."/tests/t");
        @mkdir($this->dirpath."/tests/r");

        copy("@DATADIR@/CodeGen_Drizzle/test.sh.in", $this->dirpath."/tests/test.sh.in");

        chmod($this->dirpath."/tests/test.sh.in", 0755);

        $this->addPackageFile("test", "tests/test.sh.in");

        // custom test cases (may overwrite custom function test cases)
        foreach ($this->testcases as $test) {
            $test->writeTest($this);
        }
 
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
        return new CodeGen_Drizzle_Element_Test(); 
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
        // copy .m4 include files
        mkdir($this->dirpath."/m4");
        foreach (glob("@DATADIR@/CodeGen_Drizzle/*.m4") as $file) {
            copy($file, $this->dirpath."/m4/".basename($file));
        }

        // Makefile.am
        $makefile = new CodeGen_Tools_Outbuf($this->dirpath."/Makefile.am");

        // set m4 include path
        echo "ACLOCAL_AMFLAGS = -I m4\n";

        echo "lib_LTLIBRARIES = {$this->name}.la\n\n";

        echo "{$this->name}_la_SOURCES = {$this->name}.".$this->language;
        // TODO check: the code file itself should be in the 'code' section
        // but is in 'c' instead ???
        if (isset($this->packageFiles['code'])) {
            foreach ($this->packageFiles['code'] as $file) {
                echo " ".basename($file);
            }
            if (isset($this->packageFiles['header'])) {
                echo " ".join(" ", $this->packageFiles['header']);
            }
        }
        echo "\n\n";

        if (isset($this->packageFiles['header'])) {
            $headers = join(" ", $this->packageFiles['header']);
            echo "noinst_HEADERS = $headers\n";
        }
        echo "\n\n";

        echo "{$this->name}_la_CFLAGS = @DRIZZLE_CFLAGS@\n";
        echo "{$this->name}_la_CXXFLAGS = @DRIZZLE_CXXFLAGS@ \n"; 
        echo "{$this->name}_la_LDFLAGS = -module -avoid-version\n";
        echo "\n";

        echo "test: all\n";
        echo "\tcd tests; . test.sh\n";        
        echo "\n";

        echo "\n";
        echo "pdf: manual.pdf\n\n";
        echo "manual.pdf: manual.xml\n\tdocbook2pdf manual.xml\n\n";
        echo "html: manual.html\n\n";
        echo "manual.html: manual.xml\n\tdocbook2html -u manual.xml\n\n";

        if (count($this->packageFiles["test"])) {
            echo "EXTRA_DIST=".join(" ", $this->packageFiles["test"]);
        }

        $makefile->write();
    
        
        // acinclude.m4
        $acinclude = new CodeGen_Tools_Outbuf($this->dirpath."/acinclude.m4");
        foreach ($this->acfragments["top"] as $fragment) {
            echo "$fragment\n";
        }        
        foreach ($this->acfragments["bottom"] as $fragment) {
            echo "$fragment\n";
        }        
        $acinclude->write();


        // configure.in
        $configure = new CodeGen_Tools_Outbuf($this->dirpath."/configure.in");

        echo "AC_INIT({$this->archivePrefix}-{$this->name}, ". $this->release->getVersion() .")\n";
        echo "AM_INIT_AUTOMAKE([no-define])\n";
        echo "AC_CONFIG_MACRO_DIR([m4])\n";
        echo "\n";

        foreach ($this->configfragments['top'] as $fragment) {
            echo "$fragment\n";
        }
        
        echo "AC_PROG_LIBTOOL\n";

        echo "AC_PROG_CC\n";
        if ($this->language === "cpp") {
            echo "AC_PROG_CXX\n";
            echo "AC_LANG([C++])\n";
        }

        if ($this->needSource) {
            echo "WITH_DRIZZLE_SRC()\n";
        } else {
            echo "WITH_DRIZZLE()\n";
        }      

        foreach ($this->configfragments['bottom'] as $fragment) {
            echo "$fragment\n";
        }

        echo "DRIZZLE_SUBST()\n\n";

        foreach ($this->headers as $header) {
            // TODO add header checks here
        }

        echo "AM_CONFIG_HEADER(config.h)\n\n";

        echo "AC_OUTPUT(Makefile tests/test.sh)\n";

        $configure->write();


        // CMake
        // TODO: C/C++ lang selection?
        // TODO: how to handle extra translators like flex, bison, ...?
        // TODO: how to deal with debug builds?
        // TODO: how to deal with "needs server source" requirement?
        $cmake = new CodeGen_Tools_Outbuf($this->dirpath."/CMakeLists.txt");

        $projectName = "{$this->archivePrefix}-{$this->name}";

        echo "CMAKE_MINIMUM_REQUIRED(VERSION 2.4.7 FATAL_ERROR)\n\n";

        echo "PROJECT($projectName)\n\n";

        echo "# Path for Drizzle include directory\n";
        echo 'INCLUDE_DIRECTORIES("C:/Program Files/Drizzle/Drizzle Server 5.1/include/")'."\n\n"; // TODO how to make this configurable?
        
        echo 'ADD_DEFINITIONS("-DHAVE_DLOPEN -DDBUG_OFF")'."\n\n"; // TODO need to figure out how to make DBUG stuff work on win
        
        echo "ADD_LIBRARY($projectName MODULE ";

        echo "{$this->name}.".$this->language;
        if (isset($this->packageFiles['code'])) {
            foreach ($this->packageFiles['code'] as $file) {
                echo " ".basename($file);
            }
            if (isset($this->packageFiles['header'])) {
                echo " ".join(" ", $this->packageFiles['header']);
            }
        }

        echo ")\n\n";

        echo "TARGET_LINK_LIBRARIES($projectName wsock32)\n"; // TODO add extra <lib> dependencies

        $cmake->write();

        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/plug.in");

        echo "DRIZZLE_PLUGIN({$this->name}, [{$this->name}], [{$this->summary}], [max])\n";
        echo "DRIZZLE_PLUGIN_STATIC({$this->name}, [lib{$this->name}.la])\n";
        echo "DRIZZLE_PLUGIN_DYNAMIC({$this->name}, [{$this->name}.la])\n";

        return $file->write();
    }

    function runExtra($what)
    {
        $run = array();

        switch ($what) { // all fall though!
        case "test":
            $run["test"] = true;

        case "make":
            $run["make"] = true;

        case "configure":
            $run["configure"] = true;

        case "autotools":
            $run["autotools"] = true;
            
            break;

        default:
            return parent::runExtra($what);            
        }
        
        
        $olddir = getcwd();
        chdir($this->dirpath);

        $return = 0;

        if (isset($run["autotools"])) {
            echo "running 'autreconf'\n";
            system("autoreconf -i", $return);
        
            if ($return != 0) {
                chdir($olddir);
                return PEAR::raiseError("autoreconf failed");
            }
        }

        if (isset($run["configure"])) {
            // TODO how to pass configure options
            echo "running 'configure'\n";
            system("./configure", $return);
        
            if ($return != 0) {
                chdir($olddir);
                return PEAR::raiseError("configure failed");
            }
        }


        if (isset($run["make"])) {
            echo "running 'make'\n";
            system("make -k", $return);
        
            if ($return != 0) {
                chdir($olddir);
                return PEAR::raiseError("make failed");
            }
        }


        if (isset($run["test"])) {
            echo "running 'make test'\n";
            system("make test", $return);
        
            if ($return != 0) {
                chdir($olddir);
                return PEAR::raiseError("testing failed");
            }
        }

        chdir($olddir);        
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
