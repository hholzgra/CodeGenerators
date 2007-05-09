<?php
require_once("CodeGen/Tools/IndentC.php");
echo CodeGen_Tools_IndentC::indent(8, "
  foo
  bar
  #foo
  foo"
);
