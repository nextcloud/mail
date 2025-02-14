<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Contracts\IAvatarService;
use OCA\Mail\Controller\AvatarsController;
use OCA\Mail\Http\AvatarDownloadResponse;
use OCA\Mail\Service\Avatar\Avatar;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use PHPUnit_Framework_MockObject_MockObject;

class AvatarControllerTest extends TestCase {
	/** @var IAvatarService|PHPUnit_Framework_MockObject_MockObject */
	private $avatarService;

	/** @var AvatarsController */
	private $controller;

	/** @var ITimeFactory */
	private $oldFactory;

	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$this->avatarService = $this->createMock(IAvatarService::class);

		$timeFactory = $this->createMocK(ITimeFactory::class);
		$timeFactory->expects($this->any())
			->method('getTime')
			->willReturn(10000);
		$this->oldFactory = \OC::$server->offsetGet(ITimeFactory::class);
		\OC::$server->registerService(ITimeFactory::class, function () use ($timeFactory) {
			return $timeFactory;
		});

		$this->controller = new AvatarsController('mail', $request, $this->avatarService, 'jane');
	}

	protected function tearDown(): void {
		parent::tearDown();

		\OC::$server->offsetUnset(ITimeFactory::class);
		\OC::$server->offsetSet(ITimeFactory::class, $this->oldFactory);
	}

	public function testGetUrl() {
		$email = 'john@doe.com';
		$avatar = new Avatar('https://doe.com/favicon.ico');
		$this->avatarService->expects($this->once())
			->method('getAvatar')
			->with($email, 'jane')
			->willReturn($avatar);

		$resp = $this->controller->url($email);

		$expected = new JSONResponse($avatar);
		$expected->cacheFor(7 * 24 * 60 * 60, false, true);
		$this->assertEquals($expected, $resp);
	}

	public function testGetUrlNoAvatarFound() {
		$email = 'john@doe.com';
		$this->avatarService->expects($this->once())
			->method('getAvatar')
			->with($email, 'jane')
			->willReturn(null);

		$resp = $this->controller->url($email);

		$expected = new JSONResponse([], Http::STATUS_NO_CONTENT);
		$expected->cacheFor(24 * 60 * 60, false, true);
		$this->assertEquals($expected, $resp);
	}

	public function testGetImage() {
		$email = 'john@doe.com';
		$this->avatarService->expects($this->once())
			->method('getAvatarImage')
			->with($email, 'jane')
			->willReturn([new Avatar('johne@doe.com', 'image/jpeg'), 'data']);

		$resp = $this->controller->image($email);

		$expected = new AvatarDownloadResponse('data');
		$expected->addHeader('Content-Type', 'image/jpeg');
		$expected->cacheFor(7 * 24 * 60 * 60, false, true);
		$this->assertEquals($expected, $resp);
	}

	public function testGetImageNotFound() {
		$email = 'john@doe.com';
		$this->avatarService->expects($this->once())
			->method('getAvatarImage')
			->with($email, 'jane')
			->willReturn(null);

		$resp = $this->controller->image($email);

		$expected = new Response();
		$expected->setStatus(Http::STATUS_NOT_FOUND);
		$expected->cacheFor(0);
		$this->assertEquals($expected, $resp);
	}
}
