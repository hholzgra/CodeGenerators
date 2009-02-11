#!/bin/sh
svn copy -m "$1 release tagged" svn+ssh://php-groupies.de/home/svn/CodeGenerators/trunk svn+ssh://php-groupies.de/home/svn/CodeGenerators/tags/$1
