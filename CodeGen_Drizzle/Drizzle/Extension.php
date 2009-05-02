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

require_once "CodeGen/Drizzle/Element/StatusVariable.php";
require_once "CodeGen/Drizzle/Element/SystemVariable.php";

require_once "CodeGen/Drizzle/Element/ErrMsg.php";

require_once "CodeGen/Drizzle/Element/Udf.php";


/* A class that generates Plugin extension soure and documenation files
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
    * Plugin initialization code
    *
    * @var string
    */
    protected $initCode = "";

   /**
    * Plugin shutdown code
    *
    * @var string
    */
    protected $deinitCode = "";


    /**
     * Status variables for this plugin
     *
     * @var array
     */
    protected $statusVariables = array();

    /**
     * System variables for this plugin
     *
     * @var array
     */
    protected $systemVariables = array();
    
    /**
    * Init Code setter
    *
    * @param  string  code snippet
    * @return bool    success status
    */
    function setInitCode($code) 
    {
        $this->initCode = $this->indentCode($code);
        return true;
    }

    /**
    * Deinit Code setter
    *
    * @param  string  code snippet
    * @return bool    success status
    */
    function setDeinitCode($code) 
    {
        $this->deinitCode = $this->indentCode($code);
        return true;
    }


    /**
    * Init Code adder
    *
    * @param  string  code snippet
    * @return bool    success status
    */
    function addInitCode($code) 
    {
        $this->initCode.= $this->indentCode($code);
        return true;
    }

    /**
    * Deinit Code adder
    *
    * @param  string  code snippet
    * @return bool    success status
    */
    function addDeinitCode($code) 
    {
        $this->deinitCode.= $this->indentCode($code);
        return true;
    }


    function addStatusVariable($var)
    {
        if (isset($this->statusVariables[$var->getName()])) {
            return PEAR::raiseError("status variable '".$var->getName()."' already defined");
        }

        $this->statusVariables[$var->getName()] = $var;

        return true;
    }


    function addSystemVariable($var)
    {
        if (isset($this->systemVariables[$var->getName()])) {
            return PEAR::raiseError("system variable '".$var->getName()."' already defined");
        }

        $this->systemVariables[$var->getName()] = $var;

        return true;
    }

    // {{{ member adding functions

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

        if (isset($this->code["header"]["bottom"])) {
            foreach ($this->code["header"]["bottom"] as $code) {
                echo $this->codegen->block($code, 0);
            }
        }
        echo "#endif /* MYPLUGIN_{$upname}_H */\n\n";

        return $file->write();
    }

    // }}} 




    function getPluginDeclaration()
    {
        ob_start();

        $name    = $this->name;
        $desc    = $this->summary;

        $authors = array();
        foreach ($this->getAuthors() as $author) {
            $author_name = $author->getName();
            $author_email = $author->getEmail();
            if (!empty($author_email)) {
                $author_email = " <".$author_email.">";
            } 
            $authors[] = $author_name.$author_email;
        }
        $authors = join(", ", $authors);

        $license = $this->getLicense();
        if ($license) {
            switch ($license->getShortName()) {
            case 'GPL':
            case 'GPL2':
            case 'GPL3':
                $license = "PLUGIN_LICENSE_GPL";
                break;
            case 'BSD':
                $license = "PLUGIN_LICENSE_BSD"; 
                break;
            default:
                $license = "PLUGIN_LICENSE_PROPRIETARY";
                break;
            }
        } else {
            $license = "PLUGIN_LICENSE_PROPRIETARY";
        }

        $version = $this->getRelease()->getVersion();

        echo "
static int {$this->name}_plugin_init(PluginRegistry &registry __attribute((unused)))
{
{$this->initCode}
";
 
        foreach ($this->plugins as $plugin) {
            $regObj = $plugin->regObject();

            if ($regObj) {
                echo "  registry.add(&$regObj);\n";
            }
        }


        echo "
  return 0;
}

static int {$this->name}_plugin_deinit(PluginRegistry &registry __attribute((unused)))
{
{$this->deinitCode}
 
  return 0;
}
";


        if (count($this->statusVariables)) {
            echo "// Status variables\n";
            foreach ($this->statusVariables as $variable) {
                echo $variable->getDefinition();
            }
            echo "\n\n";

            echo CodeGen_Drizzle_Element_StatusVariable::startRegistrations($this->name."_");
            foreach ($this->statusVariables as $variable) {
                echo $variable->getRegistration();
            }
            echo CodeGen_Drizzle_Element_StatusVariable::endRegistrations($this->name."_");
            
        }
        
        if (count($this->systemVariables)) {
            echo "// System variables\n";
            foreach ($this->systemVariables as $variable) {
                echo $variable->getDefinition();
            }
            echo "\n\n";

            echo CodeGen_Drizzle_Element_SystemVariable::startRegistrations($this->name."_");
            foreach ($this->systemVariables as $variable) {
                echo $variable->getRegistration();
            }
            echo CodeGen_Drizzle_Element_SystemVariable::endRegistrations($this->name."_");
        }
        


        echo "drizzle_declare_plugin({$this->name})
{
  \"$name\",
  \"$version\",
  \"$authors\",
  N_(\"$desc\"),
  $license,
  {$name}_plugin_init,
  {$name}_plugin_deinit,
";

        if (count($this->statusVariables)) {
            echo "  {$name}_status_variables,\n";
        } else {
            echo "  NULL, /* no status variables declared */\n";
        }

        if (count($this->systemVariables)) {
            echo "  {$name}_system_variables,\n";
        } else {
            echo "  NULL, /* no system variables declared */\n";
        }

        echo "  NULL, /* placeholder for command line options, not available yet */\n}\n";

        echo "drizzle_declare_plugin_end;\n\n";

        return ob_get_clean();
    }





  // {{{ code file

    /**
     * Write the complete C code file
     *
     * @access protected
     */
    function writeCodeFile() {
        $filename = "{$this->name}.cc";  
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

        echo "#include <drizzled/server_includes.h>\n";

        foreach ($this->plugins as $plugin) {
            echo $plugin->getPluginIncludes()."\n";
        }

        echo "#include <drizzled/gettext.h>\n";
        
        echo "\n#include \"myplugin_{$this->name}.h\"\n\n";

        // non-prepended header files
        foreach ($this->headers as $header) {
            echo $header->hCode(false);
        }
        
        // any global code to add on top
        if (isset($this->code["code"]["top"])) {
            foreach ($this->code["code"]["top"] as $code) {
                echo $this->codegen->block($code, 0);
            }
        }

        foreach ($this->plugins as $plugin) {
            echo $plugin->getPluginCode()."\n";
        }

        echo $this->getPluginDeclaration();


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
        // Makefile.am
        $makefile = new CodeGen_Tools_Outbuf($this->dirpath."/Makefile.am");

        echo "EXTRA_LTLIBRARIES = lib{$this->name}.la\n\n";
        echo "pkgplugin_LTLIBRARIES =	@plugin_{$this->name}_shared_target@\n";

        echo "lib{$this->name}_la_CPPFLAGS= $(AM_CPPFLAGS) -DDRIZZLE_DYNAMIC_PLUGIN\n";

        echo "lib{$this->name}_la_LDFLAGS = -module -avoid-version -rpath $(pkgplugindir)\n";
        echo "lib{$this->name}_la_LIBADD = \n"; // TODO add lib dependencies here



        echo "lib{$this->name}_la_SOURCES = {$this->name}.cc";
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

        echo "EXTRA_LIBRARIES =	lib{$this->name}.a\n";
        echo "noinst_LIBRARIES = @plugin_{$this->name}_static_target@\n";
        echo "lib{$this->name}_a_SOURCES=	$(lib{$this->name}_la_SOURCES)\n";

        $makefile->write();
    
        



        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/plug.in");

        echo "DRIZZLE_PLUGIN({$this->name}, [{$this->name}], [{$this->summary}])\n";
        echo "DRIZZLE_PLUGIN_DYNAMIC({$this->name}, [lib{$this->name}.la])\n";

        // TODO: 
        echo "DRIZZLE_PLUGIN_STATIC({$this->name},   [lib{$this->name}.a])\n";
        echo "DRIZZLE_PLUGIN_MANDATORY({$this->name})  dnl Default\n";

        return $file->write();
    }


    function indentCode($code, $level=2)
    {
        $code = CodeGen_Tools_Indent::linetrim($code);
        $code = CodeGen_Tools_Indent::untabify($code);
        $code = CodeGen_Tools_Indent::indent($level, $code);

        return $code;
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
