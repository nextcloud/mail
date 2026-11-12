<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Contracts\IAvatarService;
use OCA\Mail\Controller\AvatarsController;
use OCA\Mail\Service\Avatar\Avatar;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

class AvatarControllerTest extends TestCase {
	private IAvatarService&MockObject $avatarService;
	private AvatarsController $controller;

	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$this->avatarService = $this->createMock(IAvatarService::class);

		$this->controller = new AvatarsController('mail', $request, $this->avatarService, 'jane');
	}

	private function assertCachedFor(Response $response, int $seconds): void {
		$headers = $response->getHeaders();

		$this->assertSame("private, max-age=$seconds, immutable", $headers['Cache-Control']);
		$this->assertGreaterThan(time(), strtotime($headers['Expires']));
	}

	public function testGetUrl(): void {
		$email = 'john@doe.com';
		$avatar = new Avatar('https://doe.com/favicon.ico');
		$this->avatarService->expects($this->once())
			->method('getAvatar')
			->with($email, 'jane')
			->willReturn($avatar);

		$resp = $this->controller->url($email);

		$this->assertSame(Http::STATUS_OK, $resp->getStatus());
		$this->assertSame($avatar, $resp->getData());
		$this->assertCachedFor($resp, 7 * 24 * 60 * 60);
	}

	public function testGetUrlNoAvatarFound(): void {
		$email = 'john@doe.com';
		$this->avatarService->expects($this->once())
			->method('getAvatar')
			->with($email, 'jane')
			->willReturn(null);

		$resp = $this->controller->url($email);

		$this->assertSame(Http::STATUS_NO_CONTENT, $resp->getStatus());
		$this->assertCachedFor($resp, 24 * 60 * 60);
	}

	public function testGetImage(): void {
		$email = 'john@doe.com';
		$this->avatarService->expects($this->once())
			->method('getAvatarImage')
			->with($email, 'jane')
			->willReturn([new Avatar('johne@doe.com', 'image/jpeg'), 'data']);

		$resp = $this->controller->image($email);

		$this->assertSame(Http::STATUS_OK, $resp->getStatus());
		$this->assertSame('image/jpeg', $resp->getHeaders()['Content-Type']);
		$this->assertSame('data', $resp->render());
		$this->assertCachedFor($resp, 7 * 24 * 60 * 60);
	}

	public function testGetImageNotFound(): void {
		$email = 'john@doe.com';
		$this->avatarService->expects($this->once())
			->method('getAvatarImage')
			->with($email, 'jane')
			->willReturn(null);

		$resp = $this->controller->image($email);

		$this->assertSame(Http::STATUS_NOT_FOUND, $resp->getStatus());
		$this->assertCachedFor($resp, 60 * 60);
	}
}
