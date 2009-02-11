<?php

/**
* Console script to generate UDF extensions from command line
*
* @author Hartmut Holzgraefe <hartmut@php.net>
* @version $Id: udf-gen,v 1.3 2006/11/24 21:09:47 hholzgra Exp $
*/

// includes
require_once "CodeGen/Command.php";

require_once "CodeGen/MySQL/UDF/Extension.php";
require_once "CodeGen/MySQL/UDF/ExtensionParser.php";

// create extension object
$extension = new CodeGen_MySQL_UDF_Extension;

$command = new CodeGen_Command($extension, "udf-gen");

$parser = new CodeGen_MySQL_UDF_ExtensionParser($extension);

$command->execute($parser);

