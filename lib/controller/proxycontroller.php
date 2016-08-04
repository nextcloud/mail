<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
use OC;
use OCA\Mail\Http\ProxyDownloadResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\Http\Client\IClientService;
use OCP\IRequest;
use OCP\ISession;
use OCP\IURLGenerator;

class ProxyController extends Controller {

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var ISession */
	private $session;

	/** @var IClientService */
	private $clientService;

	/** @var string */
	private $referrer;

	/** @var string */
	private $hostname;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IURLGenerator $urlGenerator
	 * @param ISession $session
	 * @param IClientService $clientService
	 */
	public function __construct($appName, IRequest $request,
		IURLGenerator $urlGenerator, ISession $session,	IClientService $clientService, $referrer, $hostname) {
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
		$this->session = $session;
		$this->clientService = $clientService;
		$this->referrer = $referrer;
		$this->hostname = $hostname;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $src
	 *
	 * @throws \Exception If the URL is not valid.
	 * @return TemplateResponse
	 */
	public function redirect($src) {
		$authorizedRedirect = false;

		if (strpos($src, 'http://') !== 0 && strpos($src, 'https://') !== 0) {
			throw new Exception('URL is not valid.', 1);
		}

		// If the request has a referrer from this domain redirect the user without interaction
		// this is there to prevent an open redirector.
		// Since we can't prevent the referrer from being added with a HTTP only header we rely on an
		// additional JS file here.
		if (parse_url($this->referrer, PHP_URL_HOST) === $this->hostname) {
			$authorizedRedirect = true;
		}

		$params = [
			'authorizedRedirect' => $authorizedRedirect,
			'url' => $src,
			'urlHost' => parse_url($src, PHP_URL_HOST),
			'mailURL' => $this->urlGenerator->linkToRoute('mail.page.index'),
		];
		return new TemplateResponse($this->appName, 'redirect', $params, 'guest');
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $src
	 *
	 * TODO: Cache the proxied content to prevent unnecessary requests from the oC server
	 *       The caching should also already happen in a cronjob so that the sender of the
	 *       mail does not know whether the mail has been opened.
	 *
	 * @return ProxyDownloadResponse
	 */
	public function proxy($src) {
		// close the session to allow parallel downloads
		$this->session->close();

		$client = $this->clientService->newClient();
		$content = $client->get($src);
		return new ProxyDownloadResponse($content, $src, 'application/octet-stream');
	}

}
