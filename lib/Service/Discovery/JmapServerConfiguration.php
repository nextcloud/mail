<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Discovery;

use JsonSerializable;
use Override;
use function parse_url;
use function strtolower;

/**
 * A JMAP server, its endpoint and the path of its session resource
 */
final class JmapServerConfiguration implements JsonSerializable {
	public const AUTH_PASSWORD = 'password';
	public const AUTH_OAUTH2 = 'oauth2';

	public const DEFAULT_PATH = '/.well-known/jmap';

	private const DEFAULT_PORTS = [
		ServerEndpoint::SECURITY_SSL => 443,
		ServerEndpoint::SECURITY_NONE => 80,
	];

	/**
	 * @param list<self::AUTH_*> $authentication supported methods, empty when unknown
	 */
	public function __construct(
		private ServerEndpoint $endpoint,
		private string $path,
		private array $authentication,
	) {
	}

	/**
	 * @param list<self::AUTH_*> $authentication
	 * @return self|null null if the URL is not an absolute http(s) URL
	 */
	public static function fromUrl(string $url, string $username, array $authentication): ?self {
		$parts = parse_url($url);
		if ($parts === false || !isset($parts['host']) || $parts['host'] === '') {
			return null;
		}
		$security = match (strtolower($parts['scheme'] ?? '')) {
			'https' => ServerEndpoint::SECURITY_SSL,
			'http' => ServerEndpoint::SECURITY_NONE,
			default => null,
		};
		if ($security === null) {
			return null;
		}
		$path = $parts['path'] ?? '';

		return new self(
			new ServerEndpoint($username, strtolower($parts['host']), $parts['port'] ?? self::DEFAULT_PORTS[$security], $security),
			$path !== '' && $path !== '/' ? $path : self::DEFAULT_PATH,
			$authentication,
		);
	}

	public function getEndpoint(): ServerEndpoint {
		return $this->endpoint;
	}

	public function getPath(): string {
		return $this->path;
	}

	/**
	 * @return list<self::AUTH_*>
	 */
	public function getAuthentication(): array {
		return $this->authentication;
	}

	/**
	 * @return array{endpoint: ServerEndpoint, path: string, authentication: list<self::AUTH_*>}
	 */
	#[Override]
	public function jsonSerialize(): array {
		return [
			'endpoint' => $this->endpoint,
			'path' => $this->path,
			'authentication' => $this->authentication,
		];
	}
}
