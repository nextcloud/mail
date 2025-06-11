<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\Avatar;

use Exception;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Vendor\Favicon\Favicon;
use OCP\Files\IMimeTypeDetector;
use OCP\Http\Client\IClientService;
use OCP\Security\IRemoteHostValidator;

class FaviconSource implements IAvatarSource {
	/** @var IClientService */
	private $clientService;

	/** @var Favicon */
	private $favicon;

	/** @var IMimeTypeDetector */
	private $mimeDetector;
	private IRemoteHostValidator $remoteHostValidator;

	public function __construct(IClientService $clientService,
		Favicon $favicon,
		IMimeTypeDetector $mimeDetector,
		IRemoteHostValidator $remoteHostValidator) {
		$this->clientService = $clientService;
		$this->favicon = $favicon;
		$this->favicon->setCacheTimeout(0);
		$this->mimeDetector = $mimeDetector;
		$this->remoteHostValidator = $remoteHostValidator;
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
		$horde = new Horde_Mail_Rfc822_Address($email);
		// TODO: fall back to insecure HTTP?
		$domain = 'https://' . $horde->host;

		if (!$this->remoteHostValidator->isValid($domain)) {
			return null;
		}
		$iconUrl = $this->favicon->get($domain);

		if ($iconUrl === false || empty($iconUrl)) {
			return null;
		}

		/** @var string $iconUrl */
		$client = $this->clientService->newClient();
		try {
			$response = $client->get($iconUrl);
		} catch (Exception $exception) {
			return null;
		}

		// Don't save 0 byte images
		$body = $response->getBody();
		if (strlen($body) === 0) {
			return null;
		}
		$mime = $this->mimeDetector->detectString($body);

		return $factory->createExternal($iconUrl, $mime);
	}
}
