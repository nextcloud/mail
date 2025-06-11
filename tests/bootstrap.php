<?php


/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
define('PHPUNIT_RUN', 1);

require_once __DIR__ . '/../../../tests/autoload.php';
require_once __DIR__ . '/../../../lib/base.php';
require_once __DIR__ . '/../vendor/autoload.php';

\OC_App::loadApp('mail');

OC_Hook::clear();
