<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Exception;
use OCA\Mail\JMAP\JmapSessionProbe;
use OCA\Mail\Service\Discovery\JmapServerConfiguration;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\Http\Client\IResponse;
use OCP\IConfig;
use OCP\Security\IRemoteHostValidator;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class JmapSessionProbeTest extends TestCase {
	private IClient&MockObject $client;
	private IRemoteHostValidator&MockObject $hostValidator;
	private JmapSessionProbe $probe;

	/** @var array<string, IResponse|Exception> */
	private array $responses = [];

	/** @var list<string> */
	private array $requested = [];

	protected function setUp(): void {
		parent::setUp();

		$this->client = $this->createMock(IClient::class);
		$this->client->method('get')
			->willReturnCallback(function (string $url, array $options): IResponse {
				$this->requested[] = $url;
				self::assertFalse($options['allow_redirects']);
				self::assertFalse($options['http_errors']);
				$response = $this->responses[$url] ?? $this->response(404);
				if ($response instanceof Exception) {
					throw $response;
				}
				return $response;
			});
		$clientService = $this->createMock(IClientService::class);
		$clientService->method('newClient')->willReturn($this->client);
		$config = $this->createMock(IConfig::class);
		$config->method('getSystemValueBool')->willReturn(true);
		$this->hostValidator = $this->createMock(IRemoteHostValidator::class);
		$this->hostValidator->method('isValid')
			->willReturnCallback(fn (string $host): bool => $host !== 'internal.example.org');

		$this->probe = new JmapSessionProbe(
			$clientService,
			$config,
			$this->hostValidator,
			$this->createMock(LoggerInterface::class),
		);
	}

	/**
	 * @param array<string, string> $headers
	 */
	private function response(int $status, array $headers = [], string $body = ''): IResponse {
		$response = $this->createMock(IResponse::class);
		$response->method('getStatusCode')->willReturn($status);
		$response->method('getHeader')
			->willReturnCallback(fn (string $name): string => $headers[$name] ?? '');
		$response->method('getBody')->willReturn($body);
		return $response;
	}

	public function testFollowsRedirectsToChallenge(): void {
		$this->responses = [
			'https://api.example.org/.well-known/jmap' => $this->response(302, ['Location' => 'https://api.example.org/jmap/session']),
			'https://api.example.org/jmap/session' => $this->response(401, ['WWW-Authenticate' => 'Bearer resource_metadata="https://api.example.org/meta", Basic realm="jmap, mail"']),
		];

		$result = $this->probe->probe('https://api.example.org/.well-known/jmap', 'user@example.org');

		self::assertNotNull($result);
		self::assertSame('user@example.org', $result->getEndpoint()->getUsername());
		self::assertSame('api.example.org', $result->getEndpoint()->getHost());
		self::assertSame('/jmap/session', $result->getPath());
		self::assertSame([JmapServerConfiguration::AUTH_OAUTH2, JmapServerConfiguration::AUTH_PASSWORD], $result->getAuthentication());
	}

	public function testResolvesRelativeRedirect(): void {
		$this->responses = [
			'https://api.example.org:8443/.well-known/jmap' => $this->response(307, ['Location' => '/jmap/session']),
			'https://api.example.org:8443/jmap/session' => $this->response(401, ['WWW-Authenticate' => 'Basic realm="mail"']),
		];

		$result = $this->probe->probe('https://api.example.org:8443/.well-known/jmap', 'user@example.org');

		self::assertSame(8443, $result?->getEndpoint()->getPort());
		self::assertSame('/jmap/session', $result?->getPath());
		self::assertSame([JmapServerConfiguration::AUTH_PASSWORD], $result?->getAuthentication());
	}

	public function testRefusesRedirectToInvalidHost(): void {
		$this->responses = [
			'https://example.org/.well-known/jmap' => $this->response(302, ['Location' => 'https://internal.example.org/jmap']),
		];

		$result = $this->probe->probe('https://example.org/.well-known/jmap', 'user@example.org');

		self::assertNull($result);
		self::assertSame(['https://example.org/.well-known/jmap'], $this->requested);
	}

	public function testRefusesRedirectToPlainHttp(): void {
		$this->responses = [
			'https://example.org/.well-known/jmap' => $this->response(302, ['Location' => 'http://example.org/jmap']),
		];

		self::assertNull($this->probe->probe('https://example.org/.well-known/jmap', 'user@example.org'));
		self::assertCount(1, $this->requested);
	}

	public function testRefusesProtocolRelativeRedirect(): void {
		$this->responses = [
			'https://example.org/.well-known/jmap' => $this->response(302, ['Location' => '//other.example.org/jmap']),
		];

		self::assertNull($this->probe->probe('https://example.org/.well-known/jmap', 'user@example.org'));
		self::assertCount(1, $this->requested);
	}

	public function testRefusesInvalidHost(): void {
		self::assertNull($this->probe->probe('https://internal.example.org/.well-known/jmap', 'user@example.org'));
		self::assertSame([], $this->requested);
	}

	public function testStopsAfterTooManyRedirects(): void {
		$this->responses = [
			'https://example.org/a' => $this->response(302, ['Location' => '/b']),
			'https://example.org/b' => $this->response(302, ['Location' => '/a']),
		];

		self::assertNull($this->probe->probe('https://example.org/a', 'user@example.org'));
		self::assertCount(4, $this->requested);
	}

	public function testAcceptsAnonymousSessionResource(): void {
		$this->responses = [
			'https://example.org/.well-known/jmap' => $this->response(200, [], '{"capabilities":{"urn:ietf:params:jmap:core":{}}}'),
		];

		$result = $this->probe->probe('https://example.org/.well-known/jmap', 'user@example.org');

		self::assertSame('example.org', $result?->getEndpoint()->getHost());
		self::assertSame('/.well-known/jmap', $result?->getPath());
		self::assertSame([], $result?->getAuthentication());
	}

	public function testRejectsOtherContent(): void {
		$this->responses = [
			'https://example.org/.well-known/jmap' => $this->response(200, [], '<html></html>'),
		];

		self::assertNull($this->probe->probe('https://example.org/.well-known/jmap', 'user@example.org'));
	}

	public function testRejectsNotFound(): void {
		self::assertNull($this->probe->probe('https://example.org/.well-known/jmap', 'user@example.org'));
	}

	public function testRequestFailure(): void {
		$this->responses = [
			'https://example.org/.well-known/jmap' => new Exception('cURL error 6: Could not resolve host'),
		];

		self::assertNull($this->probe->probe('https://example.org/.well-known/jmap', 'user@example.org'));
	}
}
