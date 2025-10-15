<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Avatar;

use Exception;
use OCP\Http\Client\IClientService;
use function is_resource;
use function stream_get_contents;

class Downloader {
	/** @var IClientService */
	private $clientService;

	public function __construct(IClientService $clientService) {
		$this->clientService = $clientService;
	}

	public function download(string $url): ?string {
		$client = $this->clientService->newClient();

		try {
			$resp = $client->get($url);
		} catch (Exception $e) {
			return null;
		}

		$body = $resp->getBody();
		if (is_resource($body)) {
			return stream_get_contents($body) ?: null;
		}
		return $body;
	}
}
