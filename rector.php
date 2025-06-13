<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Rector\Config\RectorConfig;
use Rector\TypeDeclaration\Rector\Class_\AddTestsVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/lib',
		__DIR__ . '/tests',
	])
	->withSkip([
		__DIR__ . '/lib/Vendor'
	])
	->withPreparedSets(
		phpunitCodeQuality: true,
	)
	->withPhpSets(
		php73: true,
	)
	->withRules([
		AddTestsVoidReturnTypeWhereNoReturnRector::class,
	]);
