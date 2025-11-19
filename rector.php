<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Nextcloud\Rector\Set\NextcloudSets;
use Rector\Config\RectorConfig;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\PHPUnit\Set\PHPUnitSetList;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/lib',
		__DIR__ . '/tests',
	])
	->withSkip([
		__DIR__ . '/lib/Vendor',
		__DIR__ . '/tests/stubs',
	])
	->withSets([
		NextcloudSets::NEXTCLOUD_27,
		PHPUnitSetList::PHPUNIT_120
	])
	->withPreparedSets(
		deadCode: true,
		phpunitCodeQuality: true,
		typeDeclarations: true,
	)->withPhpSets(
		php81: true,
	)->withConfiguredRule(ClassPropertyAssignToConstructorPromotionRector::class, [
		'inline_public' => true,
		'rename_property' => true,
	]);
