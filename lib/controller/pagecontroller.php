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

class PageController extends Controller {

	/**
	 * @var \OCA\Mail\Db\MailAccountMapper
	 */
	private $mailAccountMapper;

	/**
	 * @var string
	 */
	private $currentUserId;

	public function __construct($appName, $request, $mailAccountMapper, $currentUserId){
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

		\OCP\Util::addScript('mail','handlebars-v1.3.0');
		\OCP\Util::addScript('mail','jquery.autosize');
		\OCP\Util::addScript('mail','backbone');
		\OCP\Util::addScript('mail','models/attachment');
		\OCP\Util::addScript('mail','views/attachment');
		\OCP\Util::addScript('mail','views/sendmail');
		\OCP\Util::addScript('mail','views/message');
		\OCP\Util::addScript('mail','mail');
		\OCP\Util::addScript('mail','send-mail');
		\OCP\Util::addStyle('mail','mail');
		\OCP\Util::addStyle('mail','mobile');

		return new TemplateResponse($this->appName, 'index', array());
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

		$params = array('mailto' => $parts['path']);
		$parts = explode('&', $parts['query']);
		foreach($parts as $part) {
			$pair = explode('=', $part, 2);
			$params[strtolower($pair[0])] = urldecode($pair[1]);
		}

		$params = array_merge(array(
			'mailto' => '',
			'cc' => '',
			'bcc' => '',
			'subject' => '',
			'body' => ''
		), $params);

		\OCP\Util::addScript('mail','handlebars-v1.3.0');
		\OCP\Util::addScript('mail','jquery.autosize');
		\OCP\Util::addScript('mail','backbone');
		\OCP\Util::addScript('mail','models/attachment');
		\OCP\Util::addScript('mail','views/attachment');
		\OCP\Util::addScript('mail','views/sendmail');
		\OCP\Util::addScript('mail','compose');
		\OCP\Util::addStyle('mail','mail');
		\OCP\Util::addStyle('mail','compose');
		\OCP\Util::addStyle('mail','mobile');

		return new TemplateResponse($this->appName, 'compose', $params);
	}
}
