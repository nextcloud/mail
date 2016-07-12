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

use OCA\Mail\Db\MailAccountMapper;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\ContentSecurityPolicy;
use OCP\AppFramework\Http\RedirectResponse;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IConfig;
use OCP\IRequest;
use OCP\IURLGenerator;

class PageController extends Controller {

	/**
	 * @var MailAccountMapper
	 */
	private $mailAccountMapper;

	/**
	 * @var IURLGenerator
	 */
	private $urlGenerator;

	/**
	 * @var IConfig
	 */
	private $config;

	/**
	 * @var string
	 */
	private $currentUserId;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param $mailAccountMapper
	 * @param IConfig $config
	 * @param $UserId
	 */
	public function __construct($appName, IRequest $request, MailAccountMapper $mailAccountMapper, IURLGenerator $urlGenerator, IConfig $config, $UserId) {
		parent::__construct($appName, $request);
		$this->mailAccountMapper = $mailAccountMapper;
		$this->urlGenerator = $urlGenerator;
		$this->config = $config;
		$this->currentUserId = $UserId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return TemplateResponse renders the index page
	 */
	public function index() {


		$coreVersion = $this->config->getSystemValue('version', '0.0.0');
		$hasDavSupport = (int) version_compare($coreVersion, '9.0.0', '>=');
		// TODO: remove DEBUG constant check once minimum oc
		// core version >= 8.2, see https://github.com/owncloud/core/pull/18510
		$response = new TemplateResponse($this->appName, 'index', [
			'debug' => (defined('DEBUG') && DEBUG) || $this->config->getSystemValue('debug', false),
			'app-version' => $this->config->getAppValue('mail', 'installed_version'),
			'has-dav-support' => $hasDavSupport,
		]);

		// set csp rules for ownCloud 8.1
		if (class_exists('OCP\AppFramework\Http\ContentSecurityPolicy')) {
			$csp = new ContentSecurityPolicy();
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
