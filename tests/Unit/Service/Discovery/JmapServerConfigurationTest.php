<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Discovery;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\Discovery\JmapServerConfiguration;
use OCA\Mail\Service\Discovery\ServerEndpoint;

class JmapServerConfigurationTest extends TestCase {
	public static function urlProvider(): array {
		return [
			'https with path' => ['https://api.example.org/jmap/session', 'api.example.org', 443, ServerEndpoint::SECURITY_SSL, '/jmap/session'],
			'custom port' => ['https://api.example.org:8443/jmap', 'api.example.org', 8443, ServerEndpoint::SECURITY_SSL, '/jmap'],
			'explicit default port' => ['https://api.example.org:443/jmap', 'api.example.org', 443, ServerEndpoint::SECURITY_SSL, '/jmap'],
			'no path' => ['https://API.example.org', 'api.example.org', 443, ServerEndpoint::SECURITY_SSL, JmapServerConfiguration::DEFAULT_PATH],
			'root path' => ['https://api.example.org/', 'api.example.org', 443, ServerEndpoint::SECURITY_SSL, JmapServerConfiguration::DEFAULT_PATH],
			'plain http' => ['http://api.example.org/jmap', 'api.example.org', 80, ServerEndpoint::SECURITY_NONE, '/jmap'],
		];
	}

	/** @dataProvider urlProvider */
	public function testFromUrl(string $url, string $host, int $port, string $security, string $path): void {
		$configuration = JmapServerConfiguration::fromUrl($url, 'user@example.org', [JmapServerConfiguration::AUTH_PASSWORD]);

		self::assertNotNull($configuration);
		self::assertEquals(new ServerEndpoint('user@example.org', $host, $port, $security), $configuration->getEndpoint());
		self::assertSame($path, $configuration->getPath());
		self::assertSame([JmapServerConfiguration::AUTH_PASSWORD], $configuration->getAuthentication());
	}

	public static function invalidUrlProvider(): array {
		return [
			'empty' => [''],
			'relative' => ['/jmap/session'],
			'other scheme' => ['ftp://api.example.org/jmap'],
			'no host' => ['https:///jmap'],
		];
	}

	/** @dataProvider invalidUrlProvider */
	public function testFromInvalidUrl(string $url): void {
		self::assertNull(JmapServerConfiguration::fromUrl($url, 'user@example.org', []));
	}

	public function testJsonSerialize(): void {
		$configuration = JmapServerConfiguration::fromUrl('https://api.example.org/jmap/session', 'user@example.org', [JmapServerConfiguration::AUTH_OAUTH2]);

		self::assertSame([
			'endpoint' => ['username' => 'user@example.org', 'host' => 'api.example.org', 'port' => 443, 'security' => 'ssl'],
			'path' => '/jmap/session',
			'authentication' => ['oauth2'],
		], json_decode(json_encode($configuration, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR));
	}
}
