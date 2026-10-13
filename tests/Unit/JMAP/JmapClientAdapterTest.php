<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use GuzzleHttp\Psr7\HttpFactory;
use OCA\Mail\JMAP\Exception\JmapTransportException;
use OCA\Mail\JMAP\JmapClientAdapter;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IResponse;
use PHPUnit\Framework\MockObject\MockObject;

class JmapClientAdapterTest extends TestCase {
	private IClient&MockObject $ncClient;
	private HttpFactory $factory;
	private JmapClientAdapter $client;

	protected function setUp(): void {
		parent::setUp();

		$this->ncClient = $this->createMock(IClient::class);
		$this->factory = new HttpFactory();
		$this->client = new JmapClientAdapter(
			$this->ncClient,
			$this->factory,
			$this->factory,
			['verify' => true],
		);
	}

	/**
	 * @param string|resource $body
	 */
	private function ncResponse(int $status, $body, array $headers): IResponse&MockObject {
		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn($status);
		$response->method('getBody')->willReturn($body);
		$response->method('getHeaders')->willReturn($headers);
		return $response;
	}

	public function testForwardsRequestAndAdaptsResponse(): void {
		$request = $this->factory->createRequest('POST', 'https://jmap.example.com/api')
			->withHeader('Authorization', 'Basic abc')
			->withBody($this->factory->createStream('{"a":1}'));
		$ncResponse = $this->ncResponse(200, '{"ok":true}', ['Content-Type' => ['application/json']]);

		$captured = [];
		$this->ncClient->expects(self::once())
			->method('request')
			->willReturnCallback(function (string $method, string $uri, array $options) use (&$captured, $ncResponse) {
				$captured = [$method, $uri, $options];
				return $ncResponse;
			});

		$response = $this->client->sendRequest($request);

		self::assertSame('POST', $captured[0]);
		self::assertSame('https://jmap.example.com/api', $captured[1]);
		self::assertFalse($captured[2]['allow_redirects']);
		self::assertFalse($captured[2]['http_errors']);
		self::assertTrue($captured[2]['stream']);
		self::assertTrue($captured[2]['verify']);
		self::assertSame('Basic abc', $captured[2]['headers']['Authorization']);
		self::assertSame('{"a":1}', $captured[2]['body']);
		self::assertSame(200, $response->getStatusCode());
		self::assertSame('{"ok":true}', (string)$response->getBody());
		self::assertSame('application/json', $response->getHeaderLine('Content-Type'));
	}

	public function testOmitsBodyWhenEmpty(): void {
		$request = $this->factory->createRequest('GET', 'https://jmap.example.com/.well-known/jmap');
		$ncResponse = $this->ncResponse(200, '', []);

		$captured = [];
		$this->ncClient->method('request')
			->willReturnCallback(function (string $method, string $uri, array $options) use (&$captured, $ncResponse) {
				$captured = $options;
				return $ncResponse;
			});

		$this->client->sendRequest($request);

		self::assertArrayNotHasKey('body', $captured);
	}

	public function testAdaptsStreamedResourceBody(): void {
		$resource = fopen('php://temp', 'r+');
		fwrite($resource, 'streamed-bytes');
		rewind($resource);
		$this->ncClient->method('request')->willReturn($this->ncResponse(200, $resource, []));

		$response = $this->client->sendRequest(
			$this->factory->createRequest('GET', 'https://jmap.example.com/blob'),
		);

		self::assertSame('streamed-bytes', (string)$response->getBody());
	}

	public function testWrapsClientFailureAsClientException(): void {
		$request = $this->factory->createRequest('GET', 'https://jmap.example.com/api');
		$this->ncClient->method('request')->willThrowException(new \RuntimeException('connection refused'));

		$this->expectException(JmapTransportException::class);
		$this->client->sendRequest($request);
	}
}
