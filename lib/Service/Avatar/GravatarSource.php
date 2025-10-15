<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\Avatar;

use Exception;
use OCA\Mail\Vendor\Gravatar\Gravatar;
use OCP\Http\Client\IClientService;
use function is_resource;
use function stream_get_contents;

class GravatarSource implements IAvatarSource {
	/** @var IClientService */
	private $clientService;

	public function __construct(IClientService $clientService) {
		$this->clientService = $clientService;
	}

	/**
	 * Does this source query external services?
	 *
	 * @return bool
	 */
	#[\Override]
	public function isExternal(): bool {
		return true;
	}

	/**
	 * @param string $email sender email address
	 * @param AvatarFactory $factory
	 * @return Avatar|null avatar URL if one can be found
	 */
	#[\Override]
	public function fetch(string $email, AvatarFactory $factory) {
		$gravatar = new Gravatar(['size' => 128], true);
		$avatarUrl = $gravatar->avatar($email, ['d' => 404], true);

		$client = $this->clientService->newClient();

		try {
			$response = $client->get($avatarUrl);
		} catch (Exception $exception) {
			return null;
		}

		// Don't save 0 byte images
		$body = $response->getBody();
		if (is_resource($body)) {
			$body = stream_get_contents($body);
		}
		if ($body === null || strlen($body) === 0) {
			return null;
		}

		// TODO: check whether it's really always a jpeg
		return $factory->createExternal($avatarUrl, 'image/jpeg');
	}
}
