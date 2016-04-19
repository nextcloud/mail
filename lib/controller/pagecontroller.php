<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Timo Witte <timo.witte@gmail.com>
 *
 * ownCloud - Mail
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

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCA\Mail\Db\MailAccountMapper;

class PageController extends Controller {

	/**
	 * @var \OCA\Mail\Db\MailAccountMapper
	 */
	private $mailAccountMapper;

	/**
	 * @var IURLGenerator
	 */
	private $urlGenerator;

	/**
	 * @var string
	 */
	private $currentUserId;

	/**
	 * @param string $appName
	 * @param \OCP\IRequest $request
	 * @param $mailAccountMapper
	 * @param $UserId
	 */
	public function __construct($appName, IRequest $request, MailAccountMapper $mailAccountMapper, IURLGenerator $urlGenerator, $UserId) {
		parent::__construct($appName, $request);
		$this->mailAccountMapper = $mailAccountMapper;
		$this->urlGenerator = $urlGenerator;
		$this->currentUserId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse renders the index page
	 */
	public function index() {
		$response = new TemplateResponse($this->appName, 'index', []);
		// set csp rules for ownCloud 8.1
		if (class_exists('OCP\AppFramework\Http\ContentSecurityPolicy')) {
			$csp = new \OCP\AppFramework\Http\ContentSecurityPolicy();
			$csp->addAllowedFrameDomain('\'self\'');
			$response->setContentSecurityPolicy($csp);
		}

		return $response;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param string $uri
	 * @return TemplateResponse renders the compose page
	 */
	public function compose($uri) {
		$parts = parse_url($uri);
		$params = ['to' => $parts['path']];
		if (isset($parts['query'])) {
			$parts = explode('&', $parts['query']);
			foreach ($parts as $part) {
				$pair = explode('=', $part, 2);
				$params[strtolower($pair[0])] = urldecode($pair[1]);
			}
		}

		array_walk($params, function (&$value, $key) {
			$value = "$key=" . urlencode($value);
		});

		$hashParams = '#mailto?' . implode('&', $params);

		$baseUrl = $this->urlGenerator->linkToRoute("mail.page.index");
		return new RedirectResponse($baseUrl . $hashParams);
	}

}
