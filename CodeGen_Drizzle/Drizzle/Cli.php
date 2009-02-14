<?php

/**
* Console script to generate Drizzle plugins from command line
*
* @author Hartmut Holzgraefe <hartmut@php.net>
* @version $Id: mysql-plugin-gen,v 1.2 2006/02/17 11:11:37 hholzgra Exp $
*/


// includes
require_once "CodeGen/Command.php";

require_once "CodeGen/Drizzle/Extension.php";
require_once "CodeGen/Drizzle/ExtensionParser.php";

// create extension object
$extension = new CodeGen_Drizzle_Extension;

$command = new CodeGen_Command($extension, "drizzle-gen");

$parser = new CodeGen_Drizzle_ExtensionParser($extension);
$command->execute($parser);

?>
