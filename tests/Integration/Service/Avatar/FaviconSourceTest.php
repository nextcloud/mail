<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Service\Avatar;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OC;
use OCA\Mail\Service\Avatar\AvatarFactory;
use OCA\Mail\Service\Avatar\FaviconSource;
use OCA\Mail\Vendor\Favicon\Favicon;
use OCP\Files\IMimeTypeDetector;
use OCP\Http\Client\IClientService;
use OCP\IServerContainer;
use OCP\Security\IRemoteHostValidator;

class FaviconSourceTest extends TestCase {
	/** @var IServerContainer */
	private $serverContainer;

	protected function setUp(): void {
		parent::setUp();

		$this->serverContainer = OC::$server;
	}

	public function testFetchNoCacheFiles() {
		$email = 'noreply@duckduckgo.com';
		$avatarFactory = $this->serverContainer->query(AvatarFactory::class);
		/** @var IClientService $clientService */
		$clientService = $this->serverContainer->query(IClientService::class);
		/** @var IMimeTypeDetector $clientService */
		$mimeDetector = $this->serverContainer->query(IMimeTypeDetector::class);
		$source = new FaviconSource(
			$clientService,
			new Favicon(),
			$mimeDetector,
			$this->serverContainer->get(IRemoteHostValidator::class),
		);

		$avatar = $source->fetch($email, $avatarFactory);

		$this->assertNotNull($avatar);
	}
}
