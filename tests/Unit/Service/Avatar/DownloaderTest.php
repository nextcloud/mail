<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Avatar;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use OCA\Mail\Service\Avatar\Downloader;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use PHPUnit_Framework_MockObject_MockObject;

class DownloaderTest extends TestCase {
	/** @var IClientService|PHPUnit_Framework_MockObject_MockObject */
	private $clientService;

	/** @var Downloader */
	private $downloader;

	protected function setUp(): void {
		parent::setUp();

		$this->clientService = $this->createMock(IClientService::class);

		$this->downloader = new Downloader($this->clientService);
	}

	public function testDownload() {
		$client = $this->createMock(IClient::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$response = $this->createMock(IResponse::class);
		$client->expects($this->once())
			->method('get')
			->with('https://domain.tld/favicon.ico')
			->willReturn($response);
		$response->expects($this->once())
			->method('getBody')
			->willReturn('data');

		$data = $this->downloader->download('https://domain.tld/favicon.ico');

		$this->assertEquals('data', $data);
	}

	public function testDownloadError() {
		$client = $this->createMock(IClient::class);
		$this->clientService->expects($this->once())
			->method('newClient')
			->willReturn($client);
		$client->expects($this->once())
			->method('get')
			->with('https://domain.tld/favicon.ico')
			->willThrowException(new Exception());

		$data = $this->downloader->download('https://domain.tld/favicon.ico');

		$this->assertNull($data);
	}
}
