<?php
/**
 * A class that generates MySQL UDF soure and documenation files
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
 * @package    CodeGen_MySQL_UDF
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    CVS: $Id: Extension.php,v 1.15 2007/04/19 17:43:27 hholzgra Exp $
 * @link       http://pear.php.net/package/CodeGen_MySQL_UDF
 */

/**
 * includes
 */
// {{{ includes

require_once "CodeGen/MySQL/Extension.php";

require_once "System.php";
    
require_once "CodeGen/Element.php";
require_once "CodeGen/MySQL/UDF/Element/Function.php";
require_once "CodeGen/MySQL/UDF/Element/Test.php";

require_once "CodeGen/Maintainer.php";

require_once "CodeGen/License.php";

require_once "CodeGen/Tools/Platform.php";

require_once "CodeGen/Tools/Indent.php";

// }}} 

/**
 * A class that generates UDF extension soure and documenation files
 *
 * @category   Tools and Utilities
 * @package    CodeGen_MySQL_UDF
 * @author     Hartmut Holzgraefe <hartmut@php.net>
 * @copyright  2005 Hartmut Holzgraefe
 * @license    http://www.php.net/license/3_0.txt  PHP License 3.0
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/CodeGen_MySQL_UDF
 */
class CodeGen_MySQL_UDF_Extension 
    extends CodeGen_MySQL_Extension
{
    /**
    * Current CodeGen_MySQL_UDF version number
    * 
    * @return string
    */
    function version() 
    {
        return "@package_version@";
    }

    /**
    * CodeGen_MySQL_UDF Copyright message
    *
    * @return string
    */
    function copyright()
    {
        return "Copyright (c) 2003-2005 Hartmut Holzgraefe";
    }

    // {{{ member variables

    /**
     * The public UDF functions defined by this extension
     *
     * @var array
     */
    protected  $functions = array();
    

    // }}} 

    
    // {{{ constructor
    
    /**
     * The constructor
     *
     */
    function __construct() 
    {
        parent::__construct();

        $this->archivePrefix = "UDF";

        $this->addConfigFragment("MYSQL_USE_UDF_API()", "bottom");
    }
    
    // }}} 
    
    // {{{ member adding functions
    
    /**
     * Add a function to the extension
     *
     * @param  object   a function object
     */
    function addFunction(CodeGen_Mysql_UDF_Element_Function $function)
    {
        $name = $function->getName();

        if (isset($this->functions[$name])) {
            return PEAR::raiseError("public function '$name' has been defined before");
        }
        $this->functions[$name] = $function;
        return true;
    }


    // }}} 

    // {{{ output generation
        
    // {{{   docbook documentation

    // {{{ header file

    /**
     * Write the complete C header file
     *
     * @access protected
     */
    function writeHeaderFile() 
    {
        $filename = "udf_{$this->name}.h";
        
        $this->addPackageFile('header', $filename); 

        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/".$filename);
        
        $upname = strtoupper($this->name);
        
        echo $this->getLicenseComment();
        echo "#ifndef UDF_{$upname}_H\n";
        echo "#define UDF_{$upname}_H\n\n";   

        if (isset($this->code['header']['top'])) {
            echo "// {{{ user defined header code\n\n";
            foreach ($this->code['header']['top'] as $code) {
                echo CodeGen_Tools_Indent::indent(4, $code);
            }
            echo "// }}} \n\n";
        }

?>        

#define RETURN_NULL          { *is_null = 1; DBUG_RETURN(0); }

#define RETURN_INT(x)        { *is_null = 0; DBUG_RETURN(x); }

#define RETURN_REAL(x)       { *is_null = 0; DBUG_RETURN(x); }

#define RETURN_STRINGL(s, l) { \
  if (s == NULL) { \
    *is_null = 1; \
    DBUG_RETURN(NULL); \
  } \
  *is_null = 0; \
  *length = l; \
  if (l < 255) { \
    memcpy(result, s, l); \
    DBUG_RETURN(result); \
  } \
  if (l > data->_resultbuf_len) { \
    data->_resultbuf = realloc(data->_resultbuf, l); \
    if (!data->_resultbuf) { \
      *error = 1; \
      DBUG_RETURN(NULL); \
    } \
    data->_resultbuf_len = l; \
  } \
  memcpy(data->_resultbuf, s, l); \
  DBUG_RETURN(data->_resultbuf); \
}

#define RETURN_STRING(s) { \
  if (s == NULL) { \
    *is_null = 1; \
    DBUG_RETURN(NULL); \
  } \
  RETURN_STRINGL(s, strlen(s)); \
}

#define RETURN_DATETIME(d)   { *length = my_datetime_to_str(d, result); *is_null = 0; DBUG_RETURN(result); }


<?php
        if (isset($this->code['header']['bottom'])) {
            echo "// {{{ user defined header code\n\n";
            foreach ($this->code['header']['bottom'] as $code) {
                echo CodeGen_Tools_Indent::indent(4, $code);
            }
            echo "// }}} \n\n";
        }


        echo "#endif /* UDF_{$upname}_H */\n\n";

        return $file->write();
    }

    // }}} 



    // {{{ code file

    /**
     * Write the complete C code file
     *
     * @access protected
     */
    function writeCodeFile() 
    {
        $filename = "{$this->name}.".$this->language;  

        $this->addPackageFile('c', $filename); 

        $file =  new CodeGen_Tools_Outbuf($this->dirpath."/".$filename);
        
        $upname = strtoupper($this->name);

        echo $this->getLicenseComment();

        echo "// {{{ CREATE and DROP statements for this UDF\n\n";
        echo "#if 0\n";
        echo  "register the functions provided by this UDF module using\n";
        foreach ($this->functions as $function) {
            echo $function->createStatement($this)."\n";
        }
        echo  "\n";        
        echo  "unregister the functions provided by this UDF module using\n";        
        foreach ($this->functions as $function) {
            echo $function->dropStatement($this)."\n";
        }
        echo "#endif \n// }}}\n\n";
        
        foreach ($this->headers as $header) {
            echo $header->hCode(true);
        }        
            
        echo 
"// {{{ standard header stuff
#ifdef HAVE_CONFIG_H
#include \"config.h\"
#endif

#ifdef STANDARD
#include <stdio.h>
#include <string.h>
#ifdef __WIN__
typedef unsigned __int64 ulonglong; /* Microsofts 64 bit types */
typedef __int64 longlong;
#else
typedef unsigned long long ulonglong;
typedef long long longlong;
#endif /*__WIN__*/
#else
#include <my_global.h>
#include <my_sys.h>
#endif
#include <mysql.h>
#include <m_ctype.h>
#include <m_string.h>       // To get strmov()

// }}}

";

        foreach ($this->headers as $header) {
            echo $header->hCode(false);
        }
        
        echo "#ifdef HAVE_DLOPEN\n\n";

        echo "#include \"udf_{$this->name}.h\"\n\n";

        if (isset($this->code['code']['top'])) {
            echo "// {{{ user defined code\n\n";
            foreach ($this->code['code']['top'] as $code) {
                echo CodeGen_Tools_Indent::indent(4, $code);
            }
            echo "// }}} \n\n";
        }


        echo "// {{{ prototypes\n\n";
        
        echo "#ifdef  __cplusplus\n";
        echo "extern \"C\" {\n";
        echo "#endif\n";

        foreach ($this->functions as $function) {
            echo $function->cPrototype();
        }

        echo "#ifdef  __cplusplus\n";
        echo "}\n";
        echo "#endif\n";

        echo "// }}}\n\n";


        echo "// {{{ UDF functions\n\n";
        foreach ($this->functions as $function) {
            echo "// {{{ ".$function->signature()."\n";
            echo $function->cData($this);
            echo $function->cCode($this);
            echo "// }}}\n\n";
        }        
        echo "// }}}\n\n";

        if (isset($this->code['code']['bottom'])) {
            echo "// {{{ user defined code\n\n";
            foreach ($this->code['code']['bottom'] as $code) {
                echo CodeGen_Tools_Indent::indent(4, $code);
            }
            echo "// }}} \n\n";
        }

        echo "#else\n";
        echo "#error your installation does not support loading UDFs\n";
        echo "#endif /* HAVE_DLOPEN */\n";

        echo $this->cCodeEditorSettings();

        return $file->write();
    }

    // }}} 


    /** 
    * Generate README file (custom or default)
    *
    * @param  protected
    */
    function writeReadme() 
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/README");

        $title = $this->archivePrefix."-".$this->name." ".$this->release->getVersion();

        echo "$title\n";
        echo str_repeat("=", strlen($title))."\n\n";

        if (isset($this->summary)) {
            echo $this->summary."\n\n";
        }

        if (isset($this->description)) {
            echo $this->description."\n\n";
        }

        echo "See the INSTALL file for installation instruction\n\n";

        echo "-- \nThis UDF extension was created using CodeGen_Mysql_UDF ".self::version()."\n\n";

        echo "http://codegenerators.php-baustelle.de/trac/wiki/CodeGen_MySQL_UDF\N";

        return $file->write();
    }


    /** 
    * Generate INSTALL file (custom or default)
    *
    * @access protected
    */
    function writeInstall() 
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/INSTALL");

        $title = $this->archivePrefix."-".$this->name." ".$this->release->getVersion();

        echo "$title\n";
        echo str_repeat("=", strlen($title))."\n";

        echo "\n== Configuration ==\n\n";

        if ($this->needSource) {
?>
This user defined function module relies on information only 
available in the MySQL source code, it can't be compiled if
you've only installed MySQL binary packages.

To compile this package you need to first tell configure 
where to find the MySQL source directory you want to compile
against using the --with-mysql-src configure option, e.g:

  configure --with-mysql-src=/home/username/src/mysql-5.0.37

<?php
        } else {
?>
You can configure this package as usual, in the simplest case
you just need to invoke 

  configure 

without options. This requires that the "mysql_config" binary 
of the server installation you want to compile this UDF module
for is in your environments $PATH

You may specify an explicit mysql installation to compile 
against by using the

  --with-mysql=...

option, this option expects either the path of this installations
"mysql_config" binary or the installations base dir (assuming
that "mysql_config" is in the "$prefix/bin" directory) as argument.

So any of the following may be used to configure the package
against a server installed in a "/usr/local/mysql" prefix 
(the default if you compiled mysql yourself or used one of the
tar.gz distribution packages provided by mysql.com):

  configure --with-mysql=/usr/local/mysql

  configure --with-mysql=/usr/local/mysql/bin/mysql_config

<?php
        }
?>

For a full list of configure options see 

  configure --help

By default the UDF library created by this package will install
into /usr/local/lib. The mysql server may not be able to load
it from there though as this directory may not be in its
library search path. 

You may solve this by:

  - adding /usr/local/lib to the LD_LIBRARY_PATH before
    invoking the mysql server

  - changing the UDF install prefix by using either the
    --prefix or --libdir configure option so that the
    UDF library gets installed into a directory that is
    in the servers load path

  - or both of the above

== Compilation ==

Once you have successfully configured the package 
you should be able to compile it by simply typing

  make
        
== Testing ==

This package includes test cases that can be invoked using
 
  mysql test

This relies on the following mysql binaries being available
in your environments search $PATH to function as the tests
rely on the mysql server test framework:

 * mysql            - the mysql command line client
 * mysqld           - the mysql server
 * mysqladmin       - the mysql administration command line tool
 * mysql_install_db - the database server initialisation tool
 * mysqltest        - the actual test framework tool

== Installing the library ==

To install the generated UDF library you simply need to invoke

  make install

Depending on the target directories user permissions you might
need to do this as superuser though, eg. by using "sudo":

  sudo make install

Remember that the mysql server will only be able to load the
library if it is installed in a directory in its library load
path, you may modify this search path by invoking the server
with the $LD_LIBRARY_PATH environment variable set appropriately
before starting.

== Installing the actual functions ==

To actually enable the functions provided by this UDF module
you need to make them known to the MySQL server using 
"CREATE FUNCTION" SQL commands:

<?php
      echo  "Register the functions provided by this UDF module using\n";
        foreach ($this->functions as $function) {
            echo $function->createStatement($this)."\n";
        }
        echo  "\n";        
        echo  "Unregister the functions provided by this UDF module using\n";        
        foreach ($this->functions as $function) {
            echo $function->dropStatement($this)."\n";
        }
?>

== Changing the source ==

Changes applied to any of the files in this project may be 
overwritten by further invocations of the udf-gen tool so
you should always try to apply all necessary changes to the
XML specification file the project was generated from instead
and then regenerate the project from the spec file instead.

The udf-gen tool will only overwrite files that actually 
changed, so preserving file system time stamps of unmodified
files, to play nice with "make" and to avoid unnecessary
recompilation of source files.

<?

        $file->write();
    }

    
   /**
    * Generate DocBook documentation
    *
    * @access protected
    */
    function generateDocumentation()
    {
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/manual.xml");

        $title = $this->archivePrefix."-".$this->name." ".$this->release->getVersion();

        echo "<?xml version='1.0'?>\n";
        echo "<!DOCTYPE book PUBLIC '-//OASIS//DTD DocBook XML V4.3//EN'\n";
        echo "          'http://www.oasis-open.org/docbook/xml/4.3/docbookx.dtd' [\n";
        echo "]>\n\n";
        
        echo "<book>\n";

        echo " <title><literal>$title</literal> - {$this->summary}</title>\n";

        echo " <chapter>\n  <title>Introduction</title>\n   <para>\n";
        echo $this->docbookify($this->description)."\n";
        echo "  </para>\n </chapter>\n";

        echo " <chapter>\n  <title>Installation</title>\n";
        echo "  <section>\n   <title>Configuration</title>\n";

        if ($this->needSource) {
?>
    <para>
     This user defined function module relies on information only 
     available in the MySQL source code, it can't be compiled if
     you've only installed MySQL binary packages.
    </para>
    <para>
     To compile this package you need to first tell configure 
     where to find the MySQL source directory you want to compile
     against using the <option>--with-mysql-src</option> configure 
     option, e.g:
    </para>
    <informalexample>
     <programlisting>
      configure --with-mysql-src=/home/username/src/mysql-5.0.37
     </programlisting>
    </informalexample>
<?php
        } else {
?>
    <para>
     You can configure this package as usual, in the simplest case
     you just need to invoke 
    </para>
    <informalexample>
     <programlisting>
      configure 
     </programlisting>
    </informalexample>
    <para>
     without options. This requires that the <command>mysql_config</command> 
     binary of the server installation you want to compile this UDF module
     for is in your environments <literal>$PATH</literal>.
    </para>
    <para>
     You may specify an explicit mysql installation to compile 
     against by using the <option>--with-mysql=...</option> option, 
     this option expects either the path of this installations
     <command>mysql_config</command> binary or the installations base dir 
     (assuming that <command>mysql_config</command> is in the 
      <filename>$prefix/bin</filename> directory) as argument.
    </para>
    <para>
     So any of the following may be used to configure the package
     against a server installed in a <filename>/usr/local/mysql</filename>
     prefix (the default if you compiled mysql yourself or used one of the
     <literal>tar.gz</literal> distribution packages provided by 
     <literal>mysql.com</literal>):
    </para>
    <informalexample>
     <programlisting>
  configure --with-mysql=/usr/local/mysql

  configure --with-mysql=/usr/local/mysql/bin/mysql_config
     </programlisting>
    </informalexample>

<?php
        }
?>
    <para>
     For a full list of configure options see 
    </para>
    <informalexample>
     <programlisting>
      configure --help
     </programlisting>
    </informalexample>
    <warning>
     <para>
      By default the UDF library created by this package will install
      into <filename>/usr/local/lib</filename>. The mysql server may 
      not be able to load it from there though as this directory may 
      not be in its library search path. 
     </para>
     <para>
      You may solve this by:
      <itemizedlist>
       <listitem>
        <para>
         adding <filename>/usr/local/lib</filename> to the 
         <literal>LD_LIBRARY_PATH</literal> before invoking the mysql 
         server
        </para>
       </listitem>
       <listitem>
        <para>
         changing the UDF install prefix by using either the
         <option>--prefix</option> or <option>--libdir</option>
         configure option so that the UDF library gets installed 
         into a directory that is in the servers load path
        </para>
       </listitem>
       <listitem>
        <para>
         or both of the above
        </para>
       </listitem>
      </itemizedlist>
     </para>
    </warning>
   </section>
   <section>
    <title>Compilation</title>
    <para>
     Once you have successfully configured the package 
     you should be able to compile it by simply typing
    </para>
    <informalexample>
     <programlisting>
     make
     </programlisting>
    </informalexample>
   </section>
   <section>
    <title>Testing</title>
    <para>
     This package includes test cases that can be invoked using
     <informalexample>
      <programlisting>
      make test
      </programlisting>
     </informalexample>
   </para>
   <para>
    This relies on the following mysql binaries being available
    in your environments search <literal>$PATH</literal> to function 
    as the tests rely on the mysql server test framework:
    <itemizedlist>
     <listitem><para><command>mysql</command> - the mysql command line client</para></listitem>
     <listitem><para><command>mysqld</command> - the mysql server</para></listitem>
     <listitem><para><command>mysqladmin</command> - the mysql administration command line tool</para></listitem>
     <listitem><para><command>mysql_install_db</command> - the database server initialisation tool</para></listitem>
     <listitem><para><command>mysqltest</command> - the actual test framework tool</para></listitem>
    </itemizedlist>
   </para>
  </section>
  <section>
   <title>Installing the library</title>
    <para>
     To install the generated UDF library you simply need to invoke
     <informalexample>
      <programlisting>
       make install
      </programlisting>
     </informalexample>
    </para>
    <para>
     Depending on the target directories user permissions you might
     need to do this as superuser though, eg. by using <command>sudo</command>:
     <informalexample>
      <programlisting>
       sudo make install
      </programlisting>
     </informalexample>
    </para>
    <note>
     <para>
      Remember that the mysql server will only be able to load the
      library if it is installed in a directory in its library load
      path, you may modify this search path by invoking the server
      with the <literal>$LD_LIBRARY_PATH</literal> environment variable 
      set appropriately before starting.
     </para>
    </note>
   </section>
   <section>
    <title>Installing the actual functions</title>
    <para>
To actually enable the functions provided by this UDF module
you need to make them known to the MySQL server using 
<literal>CREATE FUNCTION</literal> SQL commands:
    </para>
    <para>
     Register the functions provided by this UDF module using
     <informalexample>
      <programlisting> 
<?php
        foreach ($this->functions as $function) {
            echo $function->createStatement($this)."\n";
        }
?>
      </programlisting>
     </informalexample>
    </para>
    <para>
     Unregister the functions provided by this UDF module using
     <informalexample>
      <programlisting>
<?php
        foreach ($this->functions as $function) {
            echo $function->dropStatement($this)."\n";
        }
?>
      </programlisting>
     </informalexample>
    </para>
   </section>
   <section>
    <title>Changing the source</title>
    <para>
     Changes applied to any of the files in this project may be 
     overwritten by further invocations of the <command>udf-gen</command>
     tool so you should always try to apply all necessary changes to the
     XML specification file the project was generated from instead
     and then regenerate the project from the spec file instead.
    </para>
    <para>
     The udf-gen tool will only overwrite files that actually 
     changed, so preserving file system time stamps of unmodified
     files, to play nice with <command>make</command> and to avoid 
     unnecessary recompilation of source files.
    </para>
   </section>
 </chapter>
 <chapter>
  <title>Functions provided by this UDF module</title>
<?

        foreach ($this->functions as $function) {
            echo $function->docbook($this);
        }

        echo "  </chapter>\n</book>\n";
        
        $file->write();
    }

    function docbookify($text) 
    {
        $text = htmlspecialchars($text);
        
        $text = preg_replace('|^\s*$|m', "</para>\n<para>", $text);

        return $text;
    }

    function writeTests()
    {
        parent::writeTests();

        $this->addPackageFile("test", "tests/create_functions.inc");
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/tests/create_functions.inc");		
        echo "--disable_warnings\n";
        foreach ($this->functions as $function) {
            echo $function->dropIfExistsStatement($this)."\n";
            echo $function->createStatement($this)."\n";
        }
        echo "--enable_warnings\n";
        $file->write();

        $this->addPackageFile("test", "tests/drop_functions.inc");
        $file = new CodeGen_Tools_Outbuf($this->dirpath."/tests/drop_functions.inc");		
        echo "--disable_warnings\n";
        foreach ($this->functions as $function) {
            echo $function->dropStatement($this)."\n";
        }
        echo "--enable_warnings\n";
        $file->write();

        // function related tests
        foreach ($this->functions as $function) {
            $function->writeTest($this);
        }

    }

    function testFactory()
    {
        return new CodeGen_MySQL_UDF_Element_Test(); 
    }
}   


/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode:nil
 * End:
 */
?>
