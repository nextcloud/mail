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


	private function clearCache() {
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

	public function testJansGravatar() {
		$this->markTestSkipped('Unreliable test');
		return;

		$avatar = $this->service->getAvatar('hey@jancborchardt.net', 'john');
		$this->assertNotNull($avatar);
		$this->assertEquals('https://secure.gravatar.com/avatar/2fd3f4d5d762955e5b603794a888fa97?size=128&d=404', $avatar->getUrl());
		$image = $this->service->getAvatarImage('hey@jancborchardt.net', 'john');
		$this->assertNotNull($image);
	}
}
