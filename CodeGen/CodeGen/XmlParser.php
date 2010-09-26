<?php
/**
 * Yet another XML parsing class 
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
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: XmlParser.php,v 1.18 2007/04/26 11:06:36 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * Yet another XML parsing class 
 *
 * This is similar to the "func" mode of XML_Parser but it borrows
 * some concepts from DSSSL.
 * The tag handler method to call is not only determined by the tag
 * name but also potentially by the name of its parent tags, and the
 * most specific handler method (that is the one including the
 * maximum number of matching parent tags in its name) wins.
 * This way it is possible to have e.g. tagstart_name as a general
 * handler for a <name> tag while tagstart_function_name handles the
 * more special case of a <name> tag within a <function> tag.
 * Character data within a tag is collected and passed to the end
 * tag handler.
 * Tag names and attributes are managed using stack arrays.
 * Attributes are not only passed to both the start and end tag 
 * handlers.
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */

class CodeGen_XmlParser 
{
    /**
     * XML parser resource
     *
     * @var resource
     */
    protected $parser = null;

    /**
     * Parser stack for <include> management
     *
     * @var array
     */
    protected $parserStack = array();

    /**
     * We collect cData in here
     *
     * @var    string
     */
    protected $data = "";

    /**
     * We also try to remember where cData started
     *
     * @var    int
     */
    protected $dataLine = 0;

    /** 
     * We maintain the current tag nesting structure here
     *
     * @var    array
     */
    protected $tagStack = array();

    /** 
     * We also need to maintain a stack for tag aliases
     *
     * @var    array
     */
    protected $tagAliasStack = array();

    /** 
     * We keep track of tag attributes so that we can also provide them to the end tag handlers
     *
     * @var    array
     */
    protected $attrStack = array();

    /**
     * There is no clean way to terminate parsing from within a handler ...
     *
     * @var    bool
     */
    protected $error = false;

    /**
     * Input Filename
     * 
     * @var    string
     */
    protected $filename = false;

    /**
     * Input stream
     *
     * @var    resource
     */
    protected $fp = null;

    /**
     * Verbatim indicator
     *
     * @var    string
     */
    protected $verbatim = false;

    /**
     * Verbatim taglevel depth
     *
     * @var string
     */
    protected $verbatimDepth = 0;

    /**
     * Tag aliases 
     *
     */
    protected $tagAliases = array();

    /**
     * The constructor 
     *
     */
    function __construct()
    { 
        $this->parser = $this->newParser();
    }

    /**
     * Create a new SAX parser and associate it with this XmlParser instance
     *
     * @void
     */
    protected function newParser()
    {
        $parser = @xml_parser_create_ns(null, ' ');

        if (is_resource($parser)) {
            xml_parser_set_option($parser, XML_OPTION_CASE_FOLDING, false);

            xml_set_object($parser, $this);
            xml_set_element_handler($parser, 'startHandler', 'endHandler');
            xml_set_character_data_handler($parser, 'cDataHandler');
            xml_set_external_entity_ref_handler($parser, 'extEntityHandler');
            xml_set_processing_instruction_handler($parser, 'piHandler');
        }

        return $parser;
    }

    /**
     * Push current SAX parser instance to the parser stack
     *
     * @void
     */
    protected function pushParser()
    {
        if ($this->parser) {
            $entry = array($this->parser, $this->filename, $this->fp);    
            array_push($this->parserStack, $entry);
        }
    }

    /**
     * Replace current SAX parser with one popped from the parser stack
     *
     * @void
     */
    function popParser()
    {
        xml_parser_free($this->parser);
        list($this->parser, $this->filename, $this->fp) = array_pop($this->parserStack);
    }

    /**
     * Generate current parse position as string for error messages
     *
     * @void
     * @returns string
     */
    protected function posString()
    {
        return "in {$this->filename} on line ".
            xml_get_current_line_number($this->parser).
            ":".
            xml_get_current_column_number($this->parser);
    }
        
    /**
     * Create a new SAX parser to parse an external entity reference
     *
     * @void
     */
    protected function extEntityHandler($parser, $openEntityNames, $base, $systemId, $publicId) 
    {
        $this->pushParser();
        $this->parser = $this->newParser();
        $stat = $this->setInputFile($systemId);
        if ($stat) {
            $this->parse();
        } else {
            $this->error = PEAR::raiseError("Can't open system entity file '$systemId' ".$this->posString());
        }
        $this->popParser();
        return;
    }

    /**
     * Set file to parse
     *
     * @param string
     */
    public function setInputFile($filename) 
    {
        $this->filename = $filename;
            
        $this->fp = @fopen($filename, "r");

        return is_resource($this->fp);
    }

    /**
     * Perform the actual parsing
     *
     * @return boolean true on success
     */
    public function parse() 
    {
        if (!is_resource($this->parser)) {
            return PEAR::raiseError("Can't create XML parser");
        }

        if (!is_resource($this->fp)) {
            return PEAR::raiseError("No valid input file");
        }


        while (($data = fread($this->fp, 4096))) {
            if (!xml_parse($this->parser, $data, feof($this->fp))) {
                $this->error = PEAR::RaiseError(xml_error_string(xml_get_error_code($this->parser))." ".$this->posString());
            }
            if ($this->error) {
                return $this->error;
            }
        }

        $stat = $this->error ? $this->error : true;

        return $stat;
    }

    /**
     * Start verbatim mode
     *
     */
    protected function verbatim()
    {
        $this->verbatim = true;
        $this->verbatimDepth = 1;
    }

    /**
     * Try to find best matching tag handler for current tag nesting
     * 
     * @param  string  handler method prefix
     * @return string  hndler method name or false if no handler found
     */
    protected function findHandler($prefix)
    {
        for ($tags = $this->tagAliasStack; count($tags); array_shift($tags)) {
            $method = "{$prefix}_".join("_", $tags);
            if (method_exists($this, $method)) {
                return $method;
            }
        }

        return false;
    }


    /**
     * Try to find a tagstart handler for this tag and call it
     *
     * @param  resource internal parser handle
     * @param  string   tag name
     * @param  array    tag attributes         
     */
    protected function startHandler($XmlParser, $fulltag, $attr)
    {
        if ($this->error) return;

        $pos = strrpos($fulltag, " ");
            
        $ns  = $pos ? substr($fulltag, 0, $pos)  : "";
        $tag = $pos ? substr($fulltag, $pos + 1) : $fulltag;

        // XInclude handling
        if ($ns === "http://www.w3.org/2001/XInclude") {
            // TODO better error checking
            if ($tag === "include") {
                $path = isset($attr['href']) ? $attr['href'] : $attr['http://www.w3.org/2001/XInclude href'];

                if (isset($attr["parse"]) && $attr["parse"] == "text") {
                    if (is_readable($path)) {
                        $data = file_get_contents($path);
                        $this->cDataHandler($XmlParser, $data);
                    } else {
                        $this->error = PEAR::raiseError("Can't open XInclude file '$path' ".$this->posString());
                    }
                } else {
                    $this->pushParser();
                    $this->parser = $this->newParser();
                    $stat = $this->setInputFile($path);
                    if ($stat) {
                        $this->parse();
                    } else {
                        $this->error = PEAR::raiseError("Can't open XInclude file '$path' ".$this->posString());
                    }
                    $this->popParser();
                }
            }
                
            return;
        }

        // this *has* to be done *after* XInclude processing !!!
        array_push($this->tagStack, $tag);
        array_push($this->tagAliasStack, isset($this->tagAliases[$tag]) ? $this->tagAliases[$tag] : $tag);
        array_push($this->attrStack, $attr);

        if ($this->verbatim) {
            $this->verbatimDepth++;
            $this->data .= "<$tag";
            foreach ($attr as $key => $value) {
                $this->data .= " $key='$value'";
            }
            $this->data .= ">";
            return;
        }

        $this->data = "";
        $this->dataLine = xml_get_current_line_number($XmlParser);

        $method = $this->findHandler("tagstart");
        if ($method) {
            $err = $this->$method($attr);
            if (PEAR::isError($err)) {
                $this->error = $err;
                $this->error->addUserInfo($this->posString());
            }
        } else if (!$this->findHandler("tagend")) {
            $this->error = PEAR::raiseError("no matching tag handler for ".join(":", $this->tagStack));
            $this->error->addUserInfo($this->posString());              
        }
    }

    /**
     * Try to find a tagend handler for this tag and call it
     *
     * @param  resource internal parser handle
     * @param  string   tag name
     */
    protected function endHandler($XmlParser, $fulltag)
    {
        if ($this->error) return;

        $pos = strrpos($fulltag, " ");
            
        $ns  = $pos ? substr($fulltag, 0, $pos)  : "";
        $tag = $pos ? substr($fulltag, $pos + 1) : $fulltag;

        // XInclude handling
        if ($ns === "http://www.w3.org/2001/XInclude") {
            return;
        }

        // this *has* to be done *before* popping the tag stack!!!
        $method = $this->findHandler("tagend");

        $oldTag   = array_pop($this->tagStack);
        $oldAlias = array_pop($this->tagAliasStack);
        $attr     = array_pop($this->attrStack);

        if ($this->verbatim) {
            if (--$this->verbatimDepth > 0) {
                $this->data .= "</$tag>";
                return;
            } else {
                $this->verbatim = false;
            }               
        }

        if ($method) {
            $err = $this->$method($attr, $this->data, $this->dataLine, $this->filename);
            if (PEAR::isError($err)) {
                $this->error = $err;
                $this->error->addUserInfo($this->posString());                                   
            }
        }
        $this->data = "";
        $this->dataLine = 0;
    }


    /**
     * Just collect cData for later use in tag end handlers
     *
     * @param  resource internal parser handle
     * @param  string   cData to collect
     */
    protected function cDataHandler($XmlParser, $data)
    {
        $this->data.= $this->verbatim ? htmlspecialchars($data) : $data;
    }

    /**
     * Delegate processing instructions
     *
     * @param  resource internal parser handle
     * @param  string   PI name
     * @param  string   PI content data
     */
    protected function piHandler($XmlParser, $name, $data)
    {
        $methodName = $name."PiHandler";

        if (method_exists($this, $methodName)) {
            $this->$methodName($XmlParser, $data);
        } else {
            $this->error = PEAR::raiseError("unknown processing instruction '<$name'");
        }
    }

    /**
     * Tread <?data PI sections like <![CDATA[
     *
     * @param  resource internal parser handle
     * @param  string   cData to collect
     */
    protected function dataPiHandler($XmlParser, $data) 
    {
        $this->cDataHandler($XmlParser, $data);
    }

    /**
     * A helper stack for collecting stuff 
     *
     * @var    array
     */
    protected $helperStack = array();

    /**
     * The current helper (top of stack) 
     *
     * @var    mixed
     */
    protected $helper = false;

    /**
     * The previous helper (top-1 of stack) 
     *
     * @var    mixed
     */
    protected $helperPrev = false;
        
    /**
     * Push something on the helper stack
     *
     * @param mixed
     */
    protected function pushHelper($helper)
    {
        array_push($this->helperStack, $this->helper);
        $this->helperPrev = $this->helper;
        $this->helper = $helper;
    }


    /**
     * Pop something from the helper stack
     *
     */
    protected function popHelper()
    {
        // TODO add optional expectedType parameter?

        $oldHelper = $this->helper;

        $this->helper = array_pop($this->helperStack);
        if (count($this->helperStack)) {
            end($this->helperStack);
            $this->helperPrev = current($this->helperStack); 
        } else {
            $this->helperPrev = false;
        }

        return $oldHelper;
    }

        
    /**
     * Convert various boolean value representation
     *
     * @param  mixed  value
     * @param  string optional attribute name string for error messages
     * @return bool
     */
    protected function toBool($arg, $name="")
    {
        if (is_bool($arg)) {
            return $arg;
        }

        if (is_numeric($arg)) {
            switch ($arg) {
            case 0:
            case 1:
                return (bool)$arg;                
            }
        }

        if (is_string($arg)) {
            switch (strtolower($arg)) {
            case 'on':
            case 'yes':
            case 'true':
                return true;
            case 'off':
            case 'no':
            case 'false':
                return false;
            }
        }
                
        return PEAR::raiseError("'$arg' is not a valid value for boolean attribute $attribute");
    }


    /**
     * Check that no attributes are given
     *
     */
    protected function noAttributes($attr) 
    {
        if (!empty($attr)) {
            return PEAR::raiseError("<".end($this->tagStack)."> does not allow any attributes");
        }

        return true;
    }

    /**
     * Check attributes
     *
     * @param array   actual attribute/value pairs
     * @param array  optinal attribute names with default values
     * @param array required attribute names
     */
    protected function checkAttributes(&$attr, $optional, $required = array())
    {
        // check for missing required attributes
        foreach ($required as $key) {
            if (!isset($attr[$key])) {
                return PEAR::raiseError("required attribute '$key' missing in <".end($this->tagStack)."> ");
            }
        }

        // add defaults for missing optional arguments
        foreach ($optional as $key => $value) {
            if (is_numeric($key)) {
                $key   = $value;
                $value = null;
            }
            if (!isset($attr[$key])) {
                $attr[$key] = $value;
            }
        }

        // check for unknown attributes
        foreach ($attr as $key => $value) {
            if (!in_array($key, $required) && !in_array($key, $optional)) {
                return PEAR::raiseError("'$key' is not a valid attribute for <".end($this->tagStack)."> ");
            }
        }
        return true;
    }

    /**
     * Add a tag alias
     *
     * @param string  Tag name 
     * @param string  Alias for this tag
     */
    function addTagAlias($tag, $alias)
    {
        $this->tagAliases[$tag] = $alias;
    }
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
