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
	public function isExternal(): bool {
		return true;
	}

	/**
	 * @param string $email sender email address
	 * @param AvatarFactory $factory
	 * @return Avatar|null avatar URL if one can be found
	 */
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
		if (strlen($body) === 0) {
			return null;
		}

		// TODO: check whether it's really always a jpeg
		return $factory->createExternal($avatarUrl, 'image/jpeg');
	}
}
