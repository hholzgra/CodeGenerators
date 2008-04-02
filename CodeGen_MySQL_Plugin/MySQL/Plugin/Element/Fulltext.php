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
 * @version    CVS: $Id: Fulltext.php,v 1.4 2006/02/15 02:31:51 hholzgra Exp $
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

class CodeGen_MySQL_Plugin_Element_Fulltext
  extends CodeGen_MySQL_Plugin_Element
{
   /**
    * Parser initialization code
    *
    * @var string
    */
    protected $initParser;

   /**
    * Parser shutdown code
    *
    * @var string
    */
    protected $deinitParser;

   /**
    * Parser code
    *
    * @var string
    */
    protected $parserCode;

    /**
     * Constructor
     */
    function __construct()
    {
      parent::__construct();
      $this->setInitParser("return 0;");
      $this->setDeinitParser("return 0;");

      // default: just use the real thing
      $this->setParserCode("return param->mysql_parse(param->mysql_ftparam, param->doc, param->length);");
    }
    
    /**
     * Parser Init Code setter
     *
     * @param  string  code snippet
     * @return bool    success status
     */
    function setInitParser($code) 
    {
        $this->initParser = $this->indentCode($code);
        return true;
    }

    /**
     * Parser Deinit Code setter
     *
     * @param  string  code snippet
     * @return bool    success status
     */
    function setDeinitParser($code) 
    {
        $this->deinitParser = $this->indentCode($code);
        return true;
    }

    /**
     * Parser Code setter
     *
     * @param  string  code snippet
     * @return bool    success status
     */
    function setParserCode($code) 
    {
        $this->parserCode = $this->indentCode($code);
        return true;
    }

    /**
     * Plugin type specifier is needed for plugin registration
     *
     * @param  void
     * @return string
     */
    function getPluginType() 
    {
      return "MYSQL_FTPARSER_PLUGIN";
    }
    
    
    function getPluginCode()
    {
      $name   = $this->name;
      
      return parent::getPluginCode().
"
static int {$name}_init(MYSQL_FTPARSER_PARAM *param)
{   
{$this->initParser}
} 

static int {$name}_deinit(MYSQL_FTPARSER_PARAM *param)
{   
{$this->deinitParser}
} 

static int {$name}_parse(MYSQL_FTPARSER_PARAM *param)
{   
{$this->parserCode}
} 


static struct st_mysql_ftparser {$name}_descriptor=
{
  MYSQL_FTPARSER_INTERFACE_VERSION,
  {$name}_parse,              
  {$name}_init,               
  {$name}_deinit              
};
";

    }
}