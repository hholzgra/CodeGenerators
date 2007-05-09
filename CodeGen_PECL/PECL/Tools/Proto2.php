<?php
require_once 'PEAR.php';

require_once "ProtoTokenizer.php";
require_once "ProtoParser.php";

function parse($proto)
{
  $lex    = new CodeGen_PECL_Tools_ProtoTokenizer($proto);
  $parser = new CodeGen_PECL_Tools_ProtoParser();

  while ($lex->nextToken()) {
#	printf("Token %d:'%s'\n", $lex->tokenId, $lex->token);
	try {
	  $parser->doParse($lex->tokenId, $lex->token);
	} catch (Exception $e) {
	  echo $e->getMessage();
	  return false;
	}
  }
  try {
    $parser->doParse(0, 0);
	echo "done!\n";
  } catch (Exception $e) {
	  echo $e->getMessage();
	  return false;	
  }

  return true;
}

foreach (file("protos.txt") as $proto) {
  echo "\n\n\nProto: $proto\n";
  parse($proto);
}
?>
