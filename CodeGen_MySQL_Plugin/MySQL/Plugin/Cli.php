<?php

/**
* Console script to generate MySQL plugins from command line
*
* @author Hartmut Holzgraefe <hartmut@php.net>
* @version $Id: mysql-plugin-gen,v 1.2 2006/02/17 11:11:37 hholzgra Exp $
*/


// includes
require_once "CodeGen/Command.php";

require_once "CodeGen/MySQL/Plugin/Extension.php";
require_once "CodeGen/MySQL/Plugin/ExtensionParser.php";
#require_once "CodeGen/Element.php";

// create extension object
$extension = new CodeGen_MySQL_Plugin_Extension;

$command = new CodeGen_Command($extension, "mysql-plugin-gen");

$parser = new CodeGen_MySQL_Plugin_ExtensionParser($extension);
$command->execute($parser);

?>
