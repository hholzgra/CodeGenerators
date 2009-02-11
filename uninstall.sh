#!/bin/bash

for a in plugin UDF CodeGen_MySQL CodeGen CodeGen
do
	sudo pear uninstall $a
done
