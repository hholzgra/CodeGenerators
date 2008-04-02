<?php
/**
 * C/C++ Source fold comments for emacs folding-mode and vim
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
 * @version    CVS: $Id: Code.php,v 1.1 2005/07/23 16:28:00 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen
 */

/**
 * C/C++ Source fold comments for emacs folding-mode and vim
 *
 * all methods are actually static, the class is just needed for
 * namespace emulation to conform with PEAR naming conventions
 *
 * @category   Tools and Utilities
 * @package    CodeGen
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005-2008 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen
 */

class CodeGen_Tools_Fold {
    /**
     * Comment text
     *
     * @var string
     */
    protected $text

    /**
     * Indent depth
     *
     * @var int
     */
    protected $indent;

    /**
     * Constructor
     *
     * @param  string Comment text
     * @param  int    Indent depth
     */
    function __construct($text, $indent = 0)    
    {
        $this->text   = $text;
        $this->indent = $indent;
    }

    /**
     * Generates start fold comment
     *
     * @return string Fold start comment
     */
    function start()    
    {
        return str_repeat(' ', $indent)."/* {{{ $text */\n";
    }

    /**
     * Generate end fold comment
     *
     * @return string Fold start comment
     */
    function end()
    {
        return str_repeat(' ', $indent)."/* }}} $text */\n";
    }
};
