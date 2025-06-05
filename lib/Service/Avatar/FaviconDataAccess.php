<?php

declare(strict_types=1);
/*
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\Avatar;

use Exception;
use OCA\Mail\Vendor\Favicon\DataAccess;
use OCP\Http\Client\IClientService;
use function array_merge;

class FaviconDataAccess extends DataAccess {

	public function __construct(
		private IClientService $clientService,
	) {
	}

	#[\Override]
	public function retrieveUrl($url) {
		$client = $this->clientService->newClient();
		try {
			$response = $client->get($url);
		} catch (Exception $e) {
			// Ignore any error, like the parent method
			return false;
		}
		return $response->getBody();
	}

	#[\Override]
	public function retrieveHeader($url) {
		$client = $this->clientService->newClient();
		try {
			$response = $client->get($url, [
				'allow_redirects' => [
					'max' => 1,
				],
			]);
		} catch (Exception $e) {
			// Ignore any error, like the parent method
			return false;
		}
		// Build the data structure get_headers returns. The status reason
		// and protocol are inaccurate, but the favicon lib will only extract
		// the status code.
		return array_merge(
			[
				0 => 'HTTP/1.1 ' . $response->getStatusCode() . ' FOO',
			],
			array_change_key_case($response->getHeaders()),
		);
	}

}
