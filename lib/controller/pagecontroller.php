<?php
/**
 * ownCloud - Mail app
 *
 * @author Sebastian Schmid
 * @copyright 2013 Sebastian Schmid mail@sebastian-schmid.de
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

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\IRequest;

class PageController extends Controller {

	/**
	 * @var \OCA\Mail\Db\MailAccountMapper
	 */
	private $mailAccountMapper;

	/**
	 * @var string
	 */
	private $currentUserId;

	/**
	 * @param string $appName
	 * @param \OCP\IRequest $request
	 * @param $mailAccountMapper
	 * @param $currentUserId
	 */
	public function __construct($appName,
								IRequest $request,
								$mailAccountMapper,
								$currentUserId) {
		parent::__construct($appName, $request);
		$this->mailAccountMapper = $mailAccountMapper;
		$this->currentUserId = $currentUserId;
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

		$params = ['mailto' => $parts['path']];
		if (isset($parts['query'])) {
			$parts = explode('&', $parts['query']);
			foreach($parts as $part) {
				$pair = explode('=', $part, 2);
				$params[strtolower($pair[0])] = urldecode($pair[1]);
			}
		}
		$params = array_merge([
			'mailto' => '',
			'cc' => '',
			'bcc' => '',
			'subject' => '',
			'body' => ''
		], $params);

		return new TemplateResponse($this->appName, 'compose', $params);
	}
}
