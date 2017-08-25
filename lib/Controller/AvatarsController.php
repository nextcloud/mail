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

namespace OCA\Mail\Controller;

use Exception;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http;
use OCP\AppFramework\Controller;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCP\ISession;
use OCA\Mail\Service\ContactsIntegration;
use Gravatar\Gravatar;
use Mpclarkson\IconScraper\Scraper;

class AvatarsController extends Controller {

	/** @var ISession */
	private $session;

	/** @var IClientService */
	private $clientService;

	/** @var string */
	private $referrer;

	/** @var string */
	private $hostname;

	/** @var ContactsIntegration */
	private $contactsIntegration;
	
	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param ISession $session
	 * @param IClientService $clientService
	 * @param string $referrer
	 * @param string $hostname
	 */
	public function __construct($appName, IRequest $request,
		ContactsIntegration $contactsIntegration,
		ISession $session,	IClientService $clientService, $referrer, $hostname) {
		parent::__construct($appName, $request);
		$this->session = $session;
		$this->clientService = $clientService;
		$this->referrer = $referrer;
		$this->hostname = $hostname;
		$this->contactsIntegration = $contactsIntegration;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $email
	 * @return Response
	 */
	public function show($email) {
		// close the session to allow parallel downloads
		$this->session->close();

		// 1) load the photo from the address book
		$result = $this->contactsIntegration->getPhoto($email);
		if ($result != null) {
			return [
				'source' => 'addressbook',
				'url' => $result
			];
		}

		// 2: Federated cloud

		// 3: Gravatar
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
				'source' => 'gravatar',
				'url' => $gravatarUrl
			];
		}

		// 4 Favicon
		$lastAt = strrpos($email, '@');
		$domain = "http://";
		$domain .= $lastAt === false ? $email : substr($email, $lastAt + 1);
		$scraper = new Scraper();
		$icons = $scraper->get($domain);

		if (count($icons) > 0) {
			usort($icons, function ($a, $b) { return $b->getHeight() - $a->getHeight(); });

			return [
				'source' => 'favicon',
				'url' => $icons[0]->getHref()
			];
		}

		// No avatar found. Return 204 (no content)
		return [
			'source' => 'none',
			'url' => null
		];
	}

}
