<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Controller\ContactIntegrationController;
use OCA\Mail\Service\ContactIntegration\ContactIntegrationService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\ICache;
use OCP\ICacheFactory;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

class ContactIntegrationControllerTest extends TestCase {
	/** @var ContactIntegrationService&MockObject */
	private $service;

	/** @var ICache&MockObject */
	private $cache;

	/** @var ContactIntegrationController */
	private $controller;

	private string $userId = 'testuser';

	protected function setUp(): void {
		parent::setUp();

		$request = $this->createMock(IRequest::class);
		$this->service = $this->createMock(ContactIntegrationService::class);
		$this->cache = $this->createMock(ICache::class);

		$cacheFactory = $this->createMock(ICacheFactory::class);
		$cacheFactory->expects($this->once())
			->method('createLocal')
			->with('mail.contacts')
			->willReturn($this->cache);

		$this->controller = new ContactIntegrationController(
			'mail',
			$request,
			$this->service,
			$cacheFactory,
			$this->userId,
		);
	}

	public function testMatch(): void {
		$mail = 'john@doe.com';
		$expected = [
			['id' => '1', 'label' => 'John Doe', 'email' => 'john@doe.com'],
		];

		$this->service->expects($this->once())
			->method('findMatches')
			->with($mail)
			->willReturn($expected);

		$response = $this->controller->match($mail);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals($expected, $response->getData());
	}

	public function testAddMail(): void {
		$uid = 'contact-uid';
		$mail = 'new@example.com';
		$expected = ['status' => 'success'];

		$this->service->expects($this->once())
			->method('addEMailToContact')
			->with($uid, $mail)
			->willReturn($expected);

		$response = $this->controller->addMail($uid, $mail);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals($expected, $response->getData());
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
	}

	public function testAddMailNotFound(): void {
		$uid = 'nonexistent';
		$mail = 'test@example.com';

		$this->service->expects($this->once())
			->method('addEMailToContact')
			->with($uid, $mail)
			->willReturn(null);

		$response = $this->controller->addMail($uid, $mail);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testNewContact(): void {
		$name = 'Jane Doe';
		$mail = 'jane@doe.com';
		$expected = ['status' => 'success'];

		$this->service->expects($this->once())
			->method('newContact')
			->with($name, $mail)
			->willReturn($expected);

		$response = $this->controller->newContact($name, $mail);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals($expected, $response->getData());
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
	}

	public function testNewContactFailed(): void {
		$name = 'Jane Doe';
		$mail = 'jane@doe.com';

		$this->service->expects($this->once())
			->method('newContact')
			->with($name, $mail)
			->willReturn(null);

		$response = $this->controller->newContact($name, $mail);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals(Http::STATUS_NOT_ACCEPTABLE, $response->getStatus());
	}

	public function testAutoCompleteReturnsCachedResult(): void {
		$term = 'john';
		$cachedData = [
			['id' => '1', 'label' => 'John Doe', 'email' => 'john@doe.com'],
		];

		$this->cache->expects($this->once())
			->method('get')
			->with("{$this->userId}:$term")
			->willReturn(json_encode($cachedData));

		$this->service->expects($this->never())
			->method('autoComplete');

		$this->cache->expects($this->never())
			->method('set');

		$response = $this->controller->autoComplete($term);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals($cachedData, $response->getData());
	}

	public function testAutoCompleteCacheMissCallsService(): void {
		$term = 'jane';
		$serviceResult = [
			['id' => '2', 'label' => 'Jane Doe', 'email' => 'jane@doe.com'],
		];

		$this->cache->expects($this->once())
			->method('get')
			->with("{$this->userId}:$term")
			->willReturn(null);

		$this->service->expects($this->once())
			->method('autoComplete')
			->with($term)
			->willReturn($serviceResult);

		$this->cache->expects($this->once())
			->method('set')
			->with(
				"{$this->userId}:$term",
				json_encode($serviceResult),
				24 * 3600,
			);

		$response = $this->controller->autoComplete($term);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals($serviceResult, $response->getData());
	}

	public function testAutoCompleteCacheInvalidJsonFallsBackToService(): void {
		$term = 'bob';
		$serviceResult = [
			['id' => '3', 'label' => 'Bob Smith', 'email' => 'bob@smith.com'],
		];

		// Cache returns a string that is not valid JSON (json_decode returns null)
		$this->cache->expects($this->once())
			->method('get')
			->with("{$this->userId}:$term")
			->willReturn('not valid json {{{');

		$this->service->expects($this->once())
			->method('autoComplete')
			->with($term)
			->willReturn($serviceResult);

		$this->cache->expects($this->once())
			->method('set')
			->with(
				"{$this->userId}:$term",
				json_encode($serviceResult),
				24 * 3600,
			);

		$response = $this->controller->autoComplete($term);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals($serviceResult, $response->getData());
	}

	public function testAutoCompleteEmptyResult(): void {
		$term = 'nonexistent';

		$this->cache->expects($this->once())
			->method('get')
			->with("{$this->userId}:$term")
			->willReturn(null);

		$this->service->expects($this->once())
			->method('autoComplete')
			->with($term)
			->willReturn([]);

		$this->cache->expects($this->once())
			->method('set')
			->with(
				"{$this->userId}:$term",
				json_encode([]),
				24 * 3600,
			);

		$response = $this->controller->autoComplete($term);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals([], $response->getData());
	}

	public function testAutoCompleteCacheKeyIsUserSpecific(): void {
		$term = 'test';

		// Verify the cache key includes the user ID prefix
		$this->cache->expects($this->once())
			->method('get')
			->with('testuser:test')
			->willReturn(null);

		$this->service->expects($this->once())
			->method('autoComplete')
			->with($term)
			->willReturn([]);

		$this->cache->expects($this->once())
			->method('set')
			->with('testuser:test', $this->anything(), $this->anything());

		$this->controller->autoComplete($term);
	}
}
