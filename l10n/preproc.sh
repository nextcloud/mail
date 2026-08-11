#!/usr/bin/env bash
# SPDX-FileCopyrightText: 2015 ownCloud, Inc.
# SPDX-License-Identifier: AGPL-3.0-only
SCRIPT=`realpath $0`
SCRIPTPATH=`dirname $SCRIPT`

php "$SCRIPTPATH/../translation-extractor.php"
