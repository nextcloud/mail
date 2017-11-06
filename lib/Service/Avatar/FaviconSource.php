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
use Horde_Mail_Rfc822_Address;
use Mpclarkson\IconScraper\Scraper;
use OCP\Http\Client\IClientService;

class FaviconSource implements IAvatarSource {

	/** @var IClientService */
	private $clientService;

	/** @var Scraper */
	private $scraper;

	/**
	 * @param IClientService $clientService
	 * @param Scraper $scraper
	 */
	public function __construct(IClientService $clientService, Scraper $scraper) {
		$this->clientService = $clientService;
		$this->scraper = $scraper;
	}

	/**
	 * @param string $email
	 * @param string $uid
	 * @return string|null
	 */
	public function fetch($email, $uid) {
		$horde = new Horde_Mail_Rfc822_Address($email);
		// TODO: fall back to insecure HTTP?
		$domain = 'https://' . $horde->host;

		$icons = $this->scraper->get($domain);

		if (empty($icons)) {
			return null;
		}

		usort($icons, function ($a, $b) {
			return $b->getHeight() - $a->getHeight();
		});

		$client = $this->clientService->newClient();
		foreach ($icons as $icon) {
			$url = $icon->getHref();
			try {
				$response = $client->get($url);
			} catch (Exception $exception) {
				continue;
			}

			// Don't save 0 byte images
			$body = $response->getBody();
			if (strlen($body) === 0) {
				continue;
			}

			return $url;
		}

		return null;
	}

}
