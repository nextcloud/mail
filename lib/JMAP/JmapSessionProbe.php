<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\JMAP;

use Exception;
use OCA\Mail\Service\Discovery\JmapServerConfiguration;
use OCP\Http\Client\IClient;
use OCP\Http\Client\IClientService;
use OCP\IConfig;
use OCP\Security\IRemoteHostValidator;
use Psr\Log\LoggerInterface;
use function explode;
use function in_array;
use function is_array;
use function is_string;
use function json_decode;
use function parse_url;
use function str_contains;
use function str_starts_with;
use function strtok;
use function strtolower;
use function trim;

/**
 * Detects a JMAP session resource (RFC 8620 section 2) without credentials
 */
class JmapSessionProbe {
	private const MAX_REDIRECTS = 3;
	private const TIMEOUT = 5;

	private IClient $client;

	public function __construct(
		IClientService $clientService,
		private IConfig $config,
		private IRemoteHostValidator $hostValidator,
		private LoggerInterface $logger,
	) {
		$this->client = $clientService->newClient();
	}

	/**
	 * @param string $url an https URL, usually ending in /.well-known/jmap
	 * @return JmapServerConfiguration|null the server at the final URL after redirects, null if no JMAP server answered
	 */
	public function probe(string $url, string $username): ?JmapServerConfiguration {
		for ($hop = 0; $hop <= self::MAX_REDIRECTS; $hop++) {
			$host = parse_url($url, PHP_URL_HOST);
			if (!str_starts_with($url, 'https://') || !is_string($host) || !$this->hostValidator->isValid($host)) {
				$this->logger->debug('JMAP probe: refusing to contact <' . $url . '>');
				return null;
			}

			try {
				$response = $this->client->get($url, [
					'timeout' => self::TIMEOUT,
					'allow_redirects' => false,
					'http_errors' => false,
					'verify' => $this->config->getSystemValueBool('app.mail.verify-tls-peer', true),
				]);
			} catch (Exception $e) {
				$this->logger->debug('JMAP probe: <' . $url . '> failed: ' . $e->getMessage(), [
					'exception' => $e,
				]);
				return null;
			}

			$status = $response->getStatusCode();
			if ($status >= 300 && $status < 400) {
				$url = $this->resolveLocation($url, $response->getHeader('Location'));
				if ($url === null) {
					return null;
				}
				continue;
			}
			if ($status === 401) {
				return JmapServerConfiguration::fromUrl($url, $username, $this->parseAuthentication($response->getHeader('WWW-Authenticate')));
			}
			if ($status === 200 && $this->isSessionResource((string)$response->getBody())) {
				return JmapServerConfiguration::fromUrl($url, $username, []);
			}
			return null;
		}

		$this->logger->debug('JMAP probe: too many redirects');
		return null;
	}

	private function resolveLocation(string $url, string $location): ?string {
		if (str_starts_with($location, 'https://')) {
			return $location;
		}
		if (str_starts_with($location, '/') && !str_starts_with($location, '//')) {
			$parts = parse_url($url);
			$origin = 'https://' . ($parts['host'] ?? '') . (isset($parts['port']) ? ':' . $parts['port'] : '');
			return $origin . $location;
		}
		return null;
	}

	/**
	 * Map the challenge schemes of a WWW-Authenticate header (RFC 9110)
	 *
	 * @return list<JmapServerConfiguration::AUTH_*>
	 */
	private function parseAuthentication(string $header): array {
		$authentication = [];
		foreach (explode(',', $header) as $part) {
			$scheme = strtok(trim($part), ' ');
			// parameters of the previous challenge contain "="
			if ($scheme === false || str_contains($scheme, '=')) {
				continue;
			}
			$mapped = match (strtolower($scheme)) {
				'basic' => JmapServerConfiguration::AUTH_PASSWORD,
				'bearer' => JmapServerConfiguration::AUTH_OAUTH2,
				default => null,
			};
			if ($mapped !== null && !in_array($mapped, $authentication, true)) {
				$authentication[] = $mapped;
			}
		}
		return $authentication;
	}

	private function isSessionResource(string $body): bool {
		$session = json_decode($body, true);
		return is_array($session)
			&& is_array($session['capabilities'] ?? null)
			&& isset($session['capabilities']['urn:ietf:params:jmap:core']);
	}
}
