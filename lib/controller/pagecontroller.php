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

		\OCP\Util::addScript('mail','mail');
		\OCP\Util::addScript('mail','send-mail');
		\OCP\Util::addScript('mail','jquery.endless-scroll');
		\OCP\Util::addStyle('mail','mail');

		$accounts = $this->mailAccountMapper->findByUserId($this->currentUserId);
		if (!empty($accounts)) {
			$templateName = 'index';
			$params = array(
				'accounts' => $accounts,
			);
		} else {
			$templateName = 'no-accounts';
			$params = array(
				'accounts' => false,
				'legend' => 'Connect your mail account',
				'mailAddress' => 'Mail Address',
				'imapPassword' => 'IMAP Password',
				'connect' => 'Connect'
			);
		}

		return new TemplateResponse($this->appName, $templateName, $params);
	}
}
