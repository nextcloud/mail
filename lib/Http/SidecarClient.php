<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 ncmail-turbo contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Http;

use OCP\Http\Client\IClientService;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

/**
 * HTTP client for communicating with the ncmail-turbo Go sidecar.
 *
 * The sidecar runs as a separate process (e.g., sidecar:3000 in Docker)
 * and handles IMAP operations with persistent connections and caching.
 */
class SidecarClient {
	private IClientService $clientService;
	private IConfig $config;
	private LoggerInterface $logger;

	public function __construct(
		IClientService $clientService,
		IConfig $config,
		LoggerInterface $logger,
	) {
		$this->clientService = $clientService;
		$this->config = $config;
		$this->logger = $logger;
	}

	/**
	 * Forward a request to the Go sidecar.
	 *
	 * @param string $method HTTP method (GET, POST, PUT, DELETE)
	 * @param string $path API path (e.g., /mailboxes)
	 * @param array $data Request data (sent as JSON body)
	 * @return array Decoded JSON response
	 * @throws \Exception If the sidecar is unreachable or returns an error
	 */
	public function forward(string $method, string $path, array $data = []): array {
		$baseUrl = $this->config->getSystemValueString(
			'app.mail.sidecar_url',
			'http://sidecar:3000'
		);

		$url = rtrim($baseUrl, '/') . '/' . ltrim($path, '/');
		$client = $this->clientService->newClient();

		$options = [
			'headers' => [
				'Content-Type' => 'application/json',
				'Accept' => 'application/json',
			],
			'timeout' => 10,
		];

		if (!empty($data)) {
			$options['body'] = json_encode($data);
		}

		try {
			$response = match (strtoupper($method)) {
				'GET' => $client->get($url, $options),
				'POST' => $client->post($url, $options),
				'PUT' => $client->put($url, $options),
				'DELETE' => $client->delete($url, $options),
				default => throw new \InvalidArgumentException("Unsupported method: $method"),
			};

			return json_decode($response->getBody(), true) ?? [];
		} catch (\Exception $e) {
			$this->logger->warning('Sidecar request failed: {error}', [
				'error' => $e->getMessage(),
				'path' => $path,
			]);
			throw $e;
		}
	}

	/**
	 * Check if the sidecar is reachable.
	 */
	public function isAvailable(): bool {
		try {
			$result = $this->forward('GET', '/health');
			return ($result['status'] ?? '') === 'ok';
		} catch (\Exception $e) {
			return false;
		}
	}

	/**
	 * Build the IMAP credentials array to pass to the sidecar.
	 */
	public static function buildImapCredentials(
		string $host,
		int $port,
		string $user,
		string $password,
		string $sslMode,
	): array {
		return [
			'host' => $host,
			'port' => $port,
			'user' => $user,
			'password' => $password,
			'ssl' => $sslMode,
		];
	}
}
