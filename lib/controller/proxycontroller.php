<?php
/**
 * ownCloud - Mail app
 *
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.org
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Lesser General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Controller;

use \OCP\IURLGenerator;
use \OCP\Util;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCA\Mail\Http\ProxyDownloadResponse;

class ProxyController extends Controller {

	private $urlGenerator;

	public function __construct($appName, $request, IURLGenerator $urlGenerator){
		parent::__construct($appName, $request);
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 * @throws \Exception If the URL is not valid.
	 * @return TemplateResponse
	 */
	public function redirect() {
		$templateName = 'redirect';

		$route = 'mail.page.index';
		$mailURL = $this->urlGenerator->linkToRoute($route);
		$url = $this->request->getParam('src');
		$authorizedRedirect = false;

		if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
			throw new \Exception('URL is not valid.', 1);
		}

		// If the request has a referrer from this domain redirect the user without interaction
		// this is there to prevent an open redirector.
		// Since we can't prevent the referrer from being added with a HTTP only header we rely on an
		// additional JS file here.
		if(parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST) === Util::getServerHostName()) {
			Util::addScript('mail', 'autoredirect');
			$authorizedRedirect = true;
		}

		$params = array(
			'authorizedRedirect' => $authorizedRedirect,
			'url' => $url,
			'urlHost' => parse_url($url, PHP_URL_HOST),
			'mailURL' => $mailURL,
		);
		return new TemplateResponse($this->appName, $templateName, $params, 'guest');
	}

	/**
	 * @NoAdminRequired
	 *
	 * TODO: Cache the proxied content to prevent unnecessary requests from the oC server
	 *       The caching should also already happen in a cronjob so that the sender of the
	 *       mail does not know whether the mail has been opened.
	 *
	 * @return ProxyDownloadResponse
	 */
	public function proxy() {
		$resourceURL = $this->request->getParam('src');
		$content =  \OC_Util::getUrlContent($resourceURL);
		return new ProxyDownloadResponse($content, $resourceURL, 'application/octet-stream');
	}
}
