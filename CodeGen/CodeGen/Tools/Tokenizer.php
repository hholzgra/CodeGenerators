<?php
/**
 * A simple tokenizer for e.g. proto parsing
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
 * @version    CVS: $Id: Tokenizer.php,v 1.2 2006/10/11 10:06:28 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * A simple tokenizer for e.g. proto parsing
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */
class CodeGen_Tools_Tokenizer
{
    /**
     * String to parse
     *
     * @var string
     */
    protected $string;

    /** 
     * Current parsing position
     *
     * @var int
     */
    protected $pos;

    /** 
     * Current token content 
     *
     * @var string
     */
    public $token;

    /** 
     * Current token type (name, numeric, string)
     *
     * @var string
     */
    public $type;

    /**
     * Parsing complete?
     *
     * @var bool
     */
    public $done = false;

    /** 
     * pushback stack for parsed tokens
     *
     * @var array
     */
    private $tokenStack = array();

    /**
     * Konstruktor
     *
     * @param   string  String to parse
     */
    public function __construct($string) 
    {
        $this->string = $string;

        $this->pos = 0;
    }


    /**
     * get next character to parse
     *
     * @return  string
     */
    protected function pullChar() 
    {
        if ($this->pos >= strlen($this->string)) {
            $this->done = true;
            return "";
        }

        return ($this->string{$this->pos++});
    }

    /**
     * Push back read character(s)
     *
     * @param  string  characters to pusch back
     */
    protected function pushChar($char) 
    {
        // we just rely on the user not cheating
        $this->pos -= strlen($char);
    }
    
    /**
     * Read next token into $this->type, $this->token
     *
     * @return  bool  success?
     */
    public function nextToken() 
    {
        // return pushed back token if any are available
        if (!empty($this->tokenStack)) {
            list($this->type, $this->token) = array_pop($this->tokenStack);
            return true;
        }

        // skip whitespace
        do {
            $char = $this->pullChar();
            if ($char === "") return false;
        } while (ctype_space($char));

        if (ctype_alnum($char) || $char === "_" || $char === "-") {
            // name or numeric token
            $this->type = "name";
            $this->token = $char;
            while (true) {
                $char = $this->pullChar();
                if (ctype_alnum($char) || $char === "_") {
                    $this->token .= $char;
                } else if ($this->token === "array" && $char === "(") {
                    $this->token .= $char;
                } else if ($this->token === "array(" && $char === ")") {
                    $this->token .= $char;
                } else {
                    $this->pushChar($char);
                    break;
                }
            } 
            if (is_numeric($this->token)) {
                $this->type = 'numeric';

                // check for decimal point and fraction
                if ('.' === ($char = $this->pullChar())) {
                    $this->token .= '.';
                    while (true) {
                        $char = $this->pullChar();
                        if (ctype_digit($char)) {
                            $this->token .= $char;
                        } else {
                            $this->pushChar($char);
                            break;
                        }
                    }
                } else {
                    $this->pushChar($char);
                }

                // check for exponent
                $char = $this->pullChar();
                if ($char == 'e' || $char == 'E') {
                    printf("E!\n");
                    $exp      = array($char);
                    $validExp = false;
                    
                    $char = $this->pullChar();
                    if ($char === '+' || $char === '-' || ctype_digit($char)) {
                        $validExp = ctype_digit($char);
                        $exp[]    = $char;
                        while (true) {
                            $char = $this->pullChar();
                            if (ctype_digit($char)) {
                                $exp[] = $char;
                                $valid = true;
                            } else {
                                $this->pushChar($char);
                                break;
                            }
                        }
                        if ($validExp) {
                            printf("E valid!\n");
                            foreach ($exp as $char) {
                                $this->token.= $char;
                            }
                        } else {
                            printf("E invalid 2!\n");
                            foreach (array_reverse($exp) as $char) {
                                $this->pushChar($char);
                            }
                        }
                    } else {
                        printf("E invalid!\n");
                        $this->pushChar($char);
                        $this->pushChar($exp[0]);
                    }
                } else {
                    $this->pushChar($char);
                }
            }
        } else if ($char == '"' || $char == "'") {
            // quoted string
            $this->type  = "string";
            $this->token = "";

            $quote  = $char;
            $escape = false;

            while (true) {
                $char = $this->pullChar();

                if ($char === "") {
                    $this->type = false;
                    return false;
                }

                if ($escape) {
                    $escape = false;
                    $this->token .= $char;
                    continue;
                }

                if ($char === $quote) {
                    break;
                }
                
                if ($char === "\\") {
                    $escape = true;
                    continue;
                }

                $this->token .= $char;
            }
        } else if ($char === ".") {
            // we need to distinguish between simple dots and '...'
            $ellipse = false;
            $char2   = $this->pullChar();
            if ($char2 === ".") {
                $char3 = $this->pullChar();
                if ($char3 === ".") {
                    $ellipse = true;
                } else {
                    $this->pushChar($char3);
                }
            } else {
                $this->pushChar($char2);
            }
            
            if ($ellipse) {
                $this->token = "...";
                $this->type  = "name";
            } else {
                $this->token = ".";
                $this->type  = "char";
            }
        } else {
            // any other character is returned "as is"
            $this->token = $char;
            $this->type  = "char";
        }

        return true;
    }

    /**
     * Push back a parsed token
     *
     */
    public function pushToken() 
    {
        array_push($this->tokenStack, array($this->type, $this->token));
    }
     
}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
