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
 * @version    CVS: $Id: Element.php,v 1.11 2007/05/05 12:05:37 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen_MySQL_Plugin
 */

/**
 * includes
 */
// {{{ includes

require_once "CodeGen/Element.php";
require_once "CodeGen/Tools/Indent.php";

require_once "CodeGen/MySQL/Plugin/Element/Test.php";

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

abstract class CodeGen_MySQL_Plugin_Element
  extends CodeGen_Element
{
   /**
    * Plugin initialization code prefix
    *
    * @var string
    */
    protected $initPrefix = "";

   /**
    * Plugin shutdown code prefix
    *
    * @var string
    */
    protected $deinitPrefix = "";

   /**
    * Plugin initialization code
    *
    * @var string
    */
    protected $initCode = "return 0;";

   /**
    * Plugin shutdown code
    *
    * @var string
    */
    protected $deinitCode = "return 0;";

    /** 
     * Do we require MySQL source or can we do with public headers only?
     *
     * @var bool
     */
    protected $requiresSource = false;

    /**
     * Status variables for this plugin
     *
     * @var array
     */
    protected $statusVariables = array();
    
    /**
     * Constructor
     */
    function __construct()
    {
      $this->setSummary("no summary given");
    }

    /**
     * requiresSource getter
     *
     * @return bool
     */
    function getRequiresSource()
    {
      return $this->requiresSource;
    }

    /**
    * Name setter
    *
    * @param  string  function name
    * @return bool    success status
    */
    function setName($name) 
    {
        if (!self::isName($name)) {
            return PEAR::raiseError("'$name' is not a valid plugin name");
        }
    
        // keywords are not allowed as function names
        if (self::isKeyword($name)) {
            return PEAR::raiseError("'$name' is a reserved word which is not valid for plugin names");
        }
    
        return parent::setName($name);
    }

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


    function addStatusVariable($var)
    {
        if (isset($this->statusVariables[$var->getName()])) {
            return PEAR::raiseError("status variable '".$var->getName()."' already defined (x)");
        }

        $this->statusVariables[$var->getName()] = $var;

        return true;
    }

    /**
     * Plugin type specifier is needed for plugin registration
     *
     * @param  void
     * @return string
     */
    abstract function getPluginType();

    /**
     * Plugin registration
     *
     * @param  void
     * @return string
     */
    function getPluginRegistration(CodeGen_MySQL_Plugin_Extension $ext)
    {
        ob_start();

        foreach ($this->statusVariables as $variable) {
            echo $variable->getDefinition();
        }
        echo "\n\n";

        echo CodeGen_MySQL_Plugin_Element_StatusVariable::startRegistrations($this->name."_");
        foreach ($this->statusVariables as $variable) {
            echo $variable->getRegistration();
        }
        echo CodeGen_MySQL_Plugin_Element_StatusVariable::endRegistrations($this->name."_");

        return ob_get_clean();
    }

    function getPluginDeclaration(CodeGen_MySQL_Plugin_Extension $ext)
    {
        $name    = $this->name;
        $type    = $this->getPluginType();
        $desc    = $this->summary;

        $authors = array();
        foreach ($ext->getAuthors() as $author) {
            $author_name = $author->getName();
            $author_email = $author->getEmail();
            if (!empty($author_email)) {
                $author_email = " <".$author_email.">";
            } 
            $authors[] = $author_name.$author_email;
        }
        $authors = join(", ", $authors);

        $license = $ext->getLicense();
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

        $version = explode(".", $ext->getRelease()->getVersion());
        if (!isset($version[1])) {
            $version[1] = 0;
        }
        $version = "0x".sprintf("%02d%02d", $version[0], $version[1]);

        return "{
  $type,
  &{$name}_descriptor, 
  \"$name\",
  \"$authors\",
  \"$desc\",
  $license,
  {$name}_plugin_init,
  {$name}_plugin_deinit,
  $version,
  {$name}_status_variables,
  NULL, /* placeholder for system variables, not available yet */
  NULL, /* placeholder for command line options, not available yet */
}
";
    }

    function getPluginCode()
    {  
        return "
static int {$this->name}_plugin_init(void *data)
{
{$this->initPrefix}
{$this->initCode}
}

static int {$this->name}_plugin_deinit(void *data)
{
{$this->deinitPrefix}
{$this->deinitCode}
}
";
    }

    function getPluginHeader()
    {
        return "";
    }

    function indentCode($code, $level=2)
    {
        $code = CodeGen_Tools_Indent::linetrim($code);
        $code = CodeGen_Tools_Indent::untabify($code);
        $code = CodeGen_Tools_Indent::indent($level, $code);

        return $code;
    }

    function installStatement($extension) 
    {
        return "INSTALL PLUGIN `{$this->name}` SONAME '".$extension->getName().".so';\n";
    }

    function uninstallStatement($extension) 
    {
        return "UNINSTALL PLUGIN `{$this->name}`;\n";
    }

    function isValid()
    {
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
