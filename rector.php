<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Rector\Config\RectorConfig;

return RectorConfig::configure()
	->withPaths([
		__DIR__ . '/lib',
		__DIR__ . '/templates',
		__DIR__ . '/tests',
	])
	->withSkip([
		__DIR__ . '/lib/Vendor'
	])
	->withPreparedSets(
		phpunitCodeQuality: true,
		phpunit: true,
	)
	->withBootstrapFiles([
		__DIR__ . '/../../lib/composer/autoload.php',
	])
	->withSets([
		\ChristophWurst\Nextcloud\Rector\Set\NextcloudSets::NEXTCLOUD_ALL,
		\ChristophWurst\Nextcloud\Rector\Set\NextcloudSets::NEXTCLOUD_25,
		\ChristophWurst\Nextcloud\Rector\Set\NextcloudSets::NEXTCLOUD_26,
		\ChristophWurst\Nextcloud\Rector\Set\NextcloudSets::NEXTCLOUD_27,
	]);
