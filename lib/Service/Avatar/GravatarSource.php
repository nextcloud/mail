<?php

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
use Gravatar\Gravatar;
use OCP\Http\Client\IClientService;

class GravatarSource implements IAvatarSource {

	/** @var IClientService */
	private $clientService;

	/**
	 * @param IClientService $clientService
	 */
	public function __construct(IClientService $clientService) {
		$this->clientService = $clientService;
	}

	/**
	 * @param string $email
	 * @return string|null
	 */
	public function fetch($email) {
		$gravatar = new Gravatar(['size' => 128], true);
		$avatar = $gravatar->avatar($email, ['d' => 404], true);

		$client = $this->clientService->newClient();

		try {
			$response = $client->get($avatar);
		} catch (Exception $exception) {
			return null;
		}

		// Don't save 0 byte images
		$body = $response->getBody();
		if (strlen($body) === 0) {
			return null;
		}

		return $avatar;
	}

}
