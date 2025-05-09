<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\Avatar;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\MockObject\MockObject;

class FaviconDataAccessTest extends TestCase {

	private IClientService|MockObject $clientService;
	private FaviconDataAccess $dataAccess;

	protected function setUp(): void {
		parent::setUp();

		$this->clientService = $this->createMock(IClientService::class);

		$this->dataAccess = new FaviconDataAccess(
			$this->clientService,
		);
	}

	public function testRetrieveUrl() {
		$client = $this->createMock(IClient::class);
		$this->clientService->expects(self::once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client->expects(self::once())
			->method('get')
			->with('https://localhost/favicon.ico', self::anything())
			->willReturn($response);
		$response->method('getBody')
			->willReturn('html');

		$body = $this->dataAccess->retrieveUrl('https://localhost/favicon.ico');

		self::assertNotNull($body);
		self::assertSame('html', $body);
	}

	public function testRetrieveHeader() {
		$client = $this->createMock(IClient::class);
		$this->clientService->expects(self::once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client->expects(self::once())
			->method('get')
			->with('https://localhost/favicon.ico', self::anything())
			->willReturn($response);
		$response->method('getStatusCode')
			->willReturn(200);
		$response->method('getHeaders')
			->willReturn([
				'Content-Type' => 'image/png',
			]);

		$headers = $this->dataAccess->retrieveHeader('https://localhost/favicon.ico');

		self::assertIsArray($headers);
		self::assertSame(
			[
				0 => 'HTTP/1.1 200 FOO',
				'content-type' => 'image/png',
			],
			$headers,
		);
	}
}
