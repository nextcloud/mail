<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Avatar;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\Avatar\AvatarFactory;
use OCA\Mail\Service\Avatar\GravatarSource;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit_Framework_MockObject_MockObject;

class GravatarSourceTest extends TestCase {
	/** @var IClientService|PHPUnit_Framework_MockObject_MockObject */
	private $clientService;

	/** @var GravatarSource */
	private $source;

	protected function setUp(): void {
		parent::setUp();

		$this->clientService = $this->createMock(IClientService::class);

		$this->source = new GravatarSource($this->clientService);
	}

	public function testFetchExisting() {
		$email = 'hey@jancborchardt.net';
		$client = $this->createMock(IClient::class);
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client->expects($this->once())
			->method('get')
			->with('https://secure.gravatar.com/avatar/2fd3f4d5d762955e5b603794a888fa97?size=128&d=404')
			->willReturn($response);
		$response->expects($this->once())
			->method('getBody')
			->willReturn('data');
		$avatar = new Avatar('https://next.cloud/photo');
		$avatarFactory->expects($this->once())
			->method('createExternal')
			->with('https://secure.gravatar.com/avatar/2fd3f4d5d762955e5b603794a888fa97?size=128&d=404')
			->willReturn($avatar);

		$actualAvatar = $this->source->fetch($email, $avatarFactory);

		$this->assertEquals($avatar, $actualAvatar);
	}

	public function testFetchHttpError() {
		$email = 'hey@jancborchardt.net';
		$client = $this->createMock(IClient::class);
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$client->expects($this->once())
			->method('get')
			->with('https://secure.gravatar.com/avatar/2fd3f4d5d762955e5b603794a888fa97?size=128&d=404')
			->willThrowException(new Exception());

		$actualAvatar = $this->source->fetch($email, $avatarFactory);

		$this->assertNull($actualAvatar);
	}
}
