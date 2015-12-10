#!/usr/bin/env bash
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`

php "$SCRIPTPATH/../translation-extractor.php"
