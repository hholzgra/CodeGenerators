%name CodeGen_PECL_Tools_ProtoParser_
%declare_class {class CodeGen_PECL_Tools_ProtoParser}
%include_class {
  protected $extension;
  protected $function;
  protected $optParams;

  function __construct(CodeGen_PECL_Extension $extension, CodeGen_PECL_Element_Function $function)
  {
    $this->extension = $extension;
    $this->function  = $function;
    $this->optParams = array();
  }
}
%syntax_error {
  $expect = array();
  foreach ($this->yy_get_expected_tokens($yymajor) as $token) {
    $expect[] = self::$yyTokenName[$token];
  }
  throw new Exception('Unexpected ' . $this->tokenName($yymajor) . '(' . $TOKEN
                      . '), expected one of: ' . implode(',', $expect));
}

proto_line ::= proto.
proto_line ::= proto SEMICOLON.

proto ::= rettype(A) NAME(B) PAR_OPEN param_spec PAR_CLOSE. {
  $this->function->setReturns(A);
  $this->function->setName(B);
}

rettype(A) ::= VOID.                   { A = array("type" => "void"); }
rettype(A) ::= typespec(B).            { A = B; }

typespec(A) ::= typename(B).           { A = B; }
typespec(A) ::= typename(B) AMPERSAND. { A = B; A["byRef"] = true; }

typename(A) ::= BOOL.                  { A = array("type" => "bool"); }
typename(A) ::= INT.                   { A = array("type" => "int"); }
typename(A) ::= FLOAT.                 { A = array("type" => "float"); }
typename(A) ::= STRING.                { A = array("type" => "string"); }
typename(A) ::= ARRAY_.                { A = array("type" => "array"); }
typename(A) ::= CLASS_ NAME(B).        { A = array("type" => "object",   "subtype" => B); }
typename(A) ::= RESOURCE NAME(B).      { A = array("type" => "resource", "subtype" => B); }
typename(A) ::= MIXED.                 { A = array("type" => "mixed"); }
typename(A) ::= CALLBACK.              { A = array("type" => "callback"); }
typename(A) ::= STREAM.                { A = array("type" => "stream"); }

param_spec ::= param_list.
param_spec ::= SQUARE_OPEN param(P) SQUARE_CLOSE. {
  P["optional"] = true;
  $stat = $this->function->addParam(P);
  if ($stat !== true) {
	throw new Exception($stat->getMessage());
  }
}
param_spec ::= SQUARE_OPEN param(P) optional_params SQUARE_CLOSE. {
  P["optional"] = true;
  $stat = $this->function->addParam(P);
  if ($stat !== true) {
	throw new Exception($stat->getMessage());
  }
  foreach ($this->optParams as $param) {
	$stat = $this->function->addParam($param);
	if ($stat !== true) {
	  throw new Exception($stat->getMessage());
	}
  }
}
param_spec ::= ELLIPSE. { 
  $this->function->setVarargs(true); 
}
param_spec ::= typename(T) ELLIPSE. { 
  $stat = $this->function->setVarargsType(T["type"]);
  if ($stat !== true) {
	throw new Exception($stat->getMessage());
  }
  $this->function->setVarargs(true); 
}
param_spec ::= VOID.
param_spec ::= .

param_list ::= param_list COMMA ELLIPSE. { 
  $this->function->setVarargs(true);
}
param_list ::= param_list COMMA typename(T) ELLIPSE. { 
  $stat = $this->function->setVarargsType(T["type"]);
  if ($stat !== true) {
	throw new Exception($stat->getMessage());
  }
  $this->function->setVarargs(true);
}
param_list ::= param_list COMMA param(P). {
  $stat = $this->function->addParam(P);
  if ($stat !== true) {
	throw new Exception($stat->getMessage());
  }
}
param_list ::= param_list optional_params. {
  foreach ($this->optParams as $param) {
	$stat = $this->function->addParam($param);
	if ($stat !== true) {
	  throw new Exception($stat->getMessage());
	}
  }
}
param_list ::= param(P). {
  $stat = $this->function->addParam(P);
  if ($stat !== true) {
	throw new Exception($stat->getMessage());
  }
}
optional_params ::= SQUARE_OPEN COMMA param(P) SQUARE_CLOSE. {
  P["optional"] = true;
  array_unshift($this->optParams, P);
}
optional_params ::= SQUARE_OPEN COMMA param(P) optional_params SQUARE_CLOSE. {
  P["optional"] = true;
  array_unshift($this->optParams, P);
}

param(P) ::= typespec(A) NAME(B). {
  P = A;
  P["name"] = B;
}
param(P) ::= typespec(A) NAME(B) EQ default(C). {
  P = A;
  P["name"]     = B;
  P["default"]  = C;        
  P["optional"] = true;
}

default(A) ::= TRUE_.     { A = "true"; }
default(A) ::= FALSE_.    { A = "false"; }
default(A) ::= NULL_.     { A = "null"; }
default(A) ::= NUMVAL(B). { A = B; }
default(A) ::= STRVAL(B). { A = '"'.B.'"'; }
default(A) ::= ARRAY_ PAR_OPEN PAR_CLOSE. { A = "array()"; }
default(A) ::= NAME(B). { 
    $constant = $this->extension->getConstant(B);
    if ($constant) {
        A = $constant;
    } else {
        throw new Exception("invalid default value '".B."'");
    }
}


