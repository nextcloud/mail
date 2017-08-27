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

namespace OCA\Mail\Service;

use Exception;
use OCA\Mail\Service\ContactsIntegration;
use OCP\IUserManager;
use OCP\IAvatarManager;
use OCP\IURLGenerator;
use OCP\Http\Client\IClientService;
use Gravatar\Gravatar;
use Mpclarkson\IconScraper\Scraper;

class AvatarService {
	/** @var IClientService */
	private $clientService;

	/** @var ContactsIntegration */
	private $contactsIntegration;

	/** @var IUserManager */
	private $userManager;

	/** @var IAvatarManager */
	protected $avatarManager;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(IAvatarManager $avatarManager, ContactsIntegration $contactsIntegration, IUserManager $userManager, IURLGenerator $urlGenerator, IClientService $clientService) {
		$this->clientService = $clientService;
		$this->contactsIntegration = $contactsIntegration;
		$this->userManager = $userManager;
		$this->avatarManager = $avatarManager;
		$this->urlGenerator = $urlGenerator;
	}

	 public function findByEmail($email) {
		// 1) load the photo from the address book
		$result = $this->findAddressbookEntry($email);
		if(!$result) $result = $this->findGravatarEntry($email);
		if(!$result) $result = $this->findFavicon($email);
		if(!$result) $result = $this->emptyEntry($email);

		return $result;
	}

	private function findAddressbookEntry($email) {
		$result = $this->contactsIntegration->getPhoto($email);
		if ($result != null) {
			return [
				'email' => $email,
				'source' => 'addressbook',
				'url' => $result
			];
		}

		return null;
	}

	private function findGravatarEntry($email) {
		$gravatar = new Gravatar(['size' => 128], true);
		$gravatarUrl = $gravatar->avatar($email, ['d' => 404]);

		$client = $this->clientService->newClient();
		$foundGravatar = true;
		try {
			$response = $client->get($gravatarUrl);
		}
		catch (\Exception $exception) {
			$foundGravatar = false;
		}

		if ($foundGravatar) {
			return [
				'email' => $email,
				'source' => 'gravatar',
				'url' => $this->urlGenerator->linkToRoute('mail.proxy.proxy', [ 'src' => $gravatarUrl ])
			];
		}

		return null;
	}

	private function findFavicon($email) {
		$lastAt = strrpos($email, '@');
		$domain = "http://";
		$domain .= $lastAt === false ? $email : substr($email, $lastAt + 1);
		$scraper = new Scraper();
		$icons = $scraper->get($domain);

		if (count($icons) > 0) {
			usort($icons, function ($a, $b) { return $b->getHeight() - $a->getHeight(); });

			return [
				'email' => $email,
				'source' => 'favicon',
				'url' => $this->urlGenerator->linkToRoute('mail.proxy.proxy', [ 'src' => $icons[0]->getHref()])
			];
		}

		return null;
	}

	private function emptyEntry($email) {
		// No avatar found. Return 204 (no content)
		return [
			'email' => $email,
			'source' => 'none',
			'url' => null
		];
	}
}

__halt_compiler();

	public function __construct(
		IAvatarManager $avatarManager,
		ContactsIntegration $contactsIntegration,
		IUserManager $userManager,
		IURLGenerator $urlGenerator,
		IClientService $clientService) {
		$this->clientService = $clientService;
		$this->contactsIntegration = $contactsIntegration;
		$this->userManager = $userManager;
		$this->avatarManager = $avatarManager;
		$this->urlGenerator = $urlGenerator;
	}

}
