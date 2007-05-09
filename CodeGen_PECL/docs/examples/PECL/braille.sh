#!/bin/bash
(cd ../../.. ; sudo make install)
pecl-gen -f braille.xml && (cd braille; phpize && configure && make clean && make && sudo make install)
