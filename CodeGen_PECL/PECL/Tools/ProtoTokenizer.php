<?php
/**
 * Class describing a function within a PECL extension 
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
 * @version    CVS: $Id: Function.php,v 1.35 2006/08/22 12:15:11 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen
 */

/** 
 * includes
 */
require_once "CodeGen/PECL/Element.php";

require_once "CodeGen/Tools/Tokenizer.php";

/**
 * A simple tokenizer for e.g. proto parsing
 *
 * @category   Tools and Utilities
 * @package    CodeGen_PECL
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_PECL
 */
class CodeGen_PECL_Tools_ProtoTokenizer
    extends CodeGen_Tools_Tokenizer
{
    /**
     * Parser token ID
     *
     * @var integer
     */
    public $tokenId = -1;

    /**
     * Read next token into $this->type, $this->token
     *
     * @return  bool  success?
     */
    public function nextToken() 
    {
        if (!parent::nextToken()) {
            return false;
        }

        switch ($this->type) {
        case 'name':
            switch ($this->token) {
            case "void":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::VOID;
                break;
            case "bool":
            case "boolean":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::BOOL;
                break;
            case "int":
            case "integer":
            case "long":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::INT;
                break;
            case "float":
            case "double":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::FLOAT;
                break;
            case "string":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::STRING;
                break;
            case "array":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::ARRAY_;
                break;
            case "class":
            case "object":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::CLASS_;
                break;
            case "resource":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::RESOURCE;
                break;
            case "mixed":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::MIXED;
                break;
            case "callback":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::CALLBACK;
                break;
            case "stream":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::STREAM;
                break;
            case "...":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::ELLIPSE;
                break;
            case "true":
            case "TRUE":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::TRUE_;
                break;
            case "false":
            case "FALSE":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::FALSE_;
                break;
            case "null":
            case "NULL":
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::NULL_;
                break;
            default:
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::NAME;
                break;
            }
            break;
        case 'numeric':
            $this->tokenId = CodeGen_PECL_Tools_ProtoParser::NUMVAL;
            break;
        case 'string':
            $this->tokenId = CodeGen_PECL_Tools_ProtoParser::STRVAL;
            break;
        case 'char':
            switch ($this->token) {
            case '=':
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::EQ;
                break;
            case ',':
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::COMMA;
                break;
            case ';':
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::SEMICOLON;
                break;
            case '(':
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::PAR_OPEN;
                break;
            case ')':
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::PAR_CLOSE;
                break;
            case '[':
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::SQUARE_OPEN;
                break;
            case ']':
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::SQUARE_CLOSE;
                break;
            case '&':
            case '@': // alternative char to avoid XML escaping
                $this->tokenId = CodeGen_PECL_Tools_ProtoParser::AMPERSAND;
                break;
            default: 
                return PEAR::raiseError("invalid character '{$this->token}' in prototype");
                
            }
            break;
        default:
            // invalid token type
            return PEAR::raiseError("invalid token '{$this->token}' in prototype");
            break;
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
