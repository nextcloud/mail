<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Jakob Sack <mail@jakobsack.de>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service\Avatar;

use Exception;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Vendor\Favicon\Favicon;
use OCP\Files\IMimeTypeDetector;
use OCP\Security\IRemoteHostValidator;
use OCP\Http\Client\IClientService;

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
	public function isExternal(): bool {
		return true;
	}

	/**
	 * @param string $email sender email address
	 * @param AvatarFactory $factory
	 * @return Avatar|null avatar URL if one can be found
	 */
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
