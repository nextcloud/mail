<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Avatar;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\Avatar\AvatarFactory;
use OCA\Mail\Service\Avatar\FaviconSource;
use OCA\Mail\Vendor\Favicon\Favicon;
use OCP\Files\IMimeTypeDetector;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\Security\IRemoteHostValidator;
use PHPUnit\Framework\MockObject\MockObject;

class FaviconSourceTest extends TestCase {
	/** @var IClientService|MockObject */
	private $clientService;

	/** @var Favicon|MockObject */
	private $favicon;

	/** @var IMimeTypeDetector|MockObject */
	private $mimeDetector;

	/** @var FaviconSource */
	private $source;
	/** @var IRemoteHostValidator|(IRemoteHostValidator&MockObject)|MockObject */
	private IRemoteHostValidator|MockObject $remoteHostValidator;

	protected function setUp(): void {
		parent::setUp();

		$this->clientService = $this->createMock(IClientService::class);
		$this->favicon = $this->createMock(Favicon::class);
		$this->mimeDetector = $this->createMock(IMimeTypeDetector::class);
		$this->remoteHostValidator = $this->createMock(IRemoteHostValidator::class);

		$this->source = new FaviconSource(
			$this->clientService,
			$this->favicon,
			$this->mimeDetector,
			$this->remoteHostValidator,
		);
	}

	public function testFetchInvaild(): void {
		$email = 'hey@jancborchardt.net';
		$this->remoteHostValidator->expects(self::once())
			->method('isValid')
			->with('https://jancborchardt.net')
			->willReturn(false);
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->favicon->expects(self::never())
			->method('get');

		$avatar = $this->source->fetch($email, $avatarFactory);

		$this->assertNull($avatar);
	}

	public function testFetchNoIconsFound(): void {
		$email = 'hey@jancborchardt.net';
		$this->remoteHostValidator->expects(self::once())
			->method('isValid')
			->with('https://jancborchardt.net')
			->willReturn(true);
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->favicon->expects($this->once())
			->method('get')
			->with('https://jancborchardt.net')
			->willReturn(false);

		$avatar = $this->source->fetch($email, $avatarFactory);

		$this->assertNull($avatar);
	}

	public function testFetchSingleIcon(): void {
		$email = 'hey@jancborchardt.net';
		$iconUrl = 'https://domain.tld/favicon.ic';
		$this->remoteHostValidator->expects(self::once())
			->method('isValid')
			->with('https://jancborchardt.net')
			->willReturn(true);
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$avatar = new Avatar('https://domain.tld/favicon.ico');
		$this->favicon->expects($this->once())
			->method('get')
			->with('https://jancborchardt.net')
			->willReturn($iconUrl);
		$client = $this->createMock(IClient::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client->expects($this->once())
			->method('get')
			->with($iconUrl)
			->willReturn($response);
		$response->expects($this->once())
			->method('getBody')
			->willReturn('data');
		$this->mimeDetector->expects($this->once())
			->method('detectString')
			->with('data')
			->willReturn('image/png');
		$avatarFactory->expects($this->once())
			->method('createExternal')
			->with($iconUrl, 'image/png')
			->willReturn($avatar);

		$actualAvatar = $this->source->fetch($email, $avatarFactory);

		$this->assertSame($avatar, $actualAvatar);
	}

	public function testFetchEmptyIcon(): void {
		$email = 'hey@jancborchardt.net';
		$iconUrl = 'https://domain.tld/favicon.ic';
		$this->remoteHostValidator->expects(self::once())
			->method('isValid')
			->with('https://jancborchardt.net')
			->willReturn(true);
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->favicon->expects($this->once())
			->method('get')
			->with('https://jancborchardt.net')
			->willReturn($iconUrl);
		$client = $this->createMock(IClient::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client->expects($this->once())
			->method('get')
			->with($iconUrl)
			->willReturn($response);
		$response->expects($this->once())
			->method('getBody')
			->willReturn('');

		$avatar = $this->source->fetch($email, $avatarFactory);

		$this->assertNull($avatar);
	}
}
