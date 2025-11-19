<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use ChristophWurst\Nextcloud\Testing\TestUser;
use OCA\Mail\Contracts\IAvatarService;
use OCP\ICacheFactory;
use OCP\Server;

class AvatarServiceIntegrationTest extends TestCase {
	use TestUser;

	/** @var IAvatarService */
	private $service;


	private function clearCache(): void {
		/* @var $cacheFactory ICacheFactory */
		$cacheFactory = Server::get(ICacheFactory::class);
		/* @var $cache ICache */
		$cache = $cacheFactory->createDistributed('mail.avatars');
		$cache->clear();
	}

	protected function setUp(): void {
		parent::setUp();

		$this->clearCache();
		$this->service = Server::get(IAvatarService::class);
	}

	public function testJansGravatar(): void {
		$this->markTestSkipped('Unreliable test');
	}
}
