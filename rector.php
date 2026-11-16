<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Nextcloud\Rector\Set\NextcloudSets;
use Rector\Config\RectorConfig;
use Rector\Php70\Rector\If_\IfToSpaceshipRector;
use Rector\Php80\Rector\Class_\ClassPropertyAssignToConstructorPromotionRector;
use Rector\PHPUnit\CodeQuality\Rector\Class_\PreferPHPUnitThisCallRector;
use Rector\PHPUnit\CodeQuality\Rector\MethodCall\WithCallbackIdenticalToStandaloneAssertsRector;
use Rector\TypeDeclaration\Rector\Class_\AddTestsVoidReturnTypeWhereNoReturnRector;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/lib',
		__DIR__ . '/tests',
	])
	->withBootstrapFiles([
		__DIR__ . '/vendor-bin/phpunit/vendor/autoload.php',
	])
	->withSkip([
		__DIR__ . '/lib/Vendor',
		// Evaluates class constants via defined(), which autoloads app classes that extend OCP\AppFramework\Db\Entity
		IfToSpaceshipRector::class,
		// Tests call the static assertions and matchers via self::
		PreferPHPUnitThisCallRector::class,
		// Rewrites callback() closures to return void, but PHPUnit 9 requires a bool
		WithCallbackIdenticalToStandaloneAssertsRector::class,
	])
	->withPreparedSets(
		phpunitCodeQuality: true,
	)
	->withSets([
		NextcloudSets::NEXTCLOUD_30,
	])
	->withPhpSets(
		php74: true,
	)
	->withRules([
		AddTestsVoidReturnTypeWhereNoReturnRector::class,
		ClassPropertyAssignToConstructorPromotionRector::class,
	]);
