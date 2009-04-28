#!/bin/bash

(cd CodeGen_PECL/PECL/Tools/ && plex ProtoLexer.plex >/dev/null && phplemon ProtoParser.y )

for a in CodeGen CodeGen_PECL CodeGen_MySQL CodeGen_MySQL_UDF CodeGen_MySQL_Plugin CodeGen_Drizzle
do
	echo $a
	(cd $a; pear install --force --offline package2.xml)
done
