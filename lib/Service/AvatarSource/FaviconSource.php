<?php

/**
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

namespace OCA\Mail\Service\AvatarSource;

use OCA\Mail\Storage\AvatarStorage;
use OCP\Http\Client\IClientService;

use Mpclarkson\IconScraper\Scraper;

class FaviconSource {
	/** @var IClientService */
	private $clientService;

	/** @var AvatarStorage */
	private $storage;

	public function __construct(IClientService $clientService, AvatarStorage $avatarStorage) {
		$this->clientService = $clientService;
		$this->storage = $avatarStorage;
	}

	public function fetch($email) {
		$lastAt = strrpos($email, '@');
		$domain = "http://";
		$domain .= $lastAt === false ? $email : substr($email, $lastAt + 1);
		$scraper = new Scraper();
		$icons = $scraper->get($domain);

		if (count($icons) ===  0) return null;

		usort($icons, function ($a, $b) { return $b->getHeight() - $a->getHeight(); });

		$client = $this->clientService->newClient();
		foreach ($icons as $icon) {
			try {
				$response = $client->get($icon->getHref());

				// Don't save 0 byte images
				$body = $response->getBody();
				if (strlen($body) === 0) {
					continue;
				}
				$this->storage->save($email, $body);

				return [
					'email' => $email,
					'source' => 'favicon',
					'url' => $icon->getHref()
				];
			}
			catch (\Exception $exception){
				continue;
			}
		}

		return null;
	}
}
