<?php
/**
 * ownCloud - Mail app
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller thomas.mueller@tmit.eu
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

use OCA\Mail\Db\MailAccount;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\JSONResponse;

class MessagesController extends Controller
{
	/**
	 * @var \OCA\Mail\Db\MailAccountMapper
	 */
	private $mapper;

	/**
	 * @var string
	 */
	private $currentUserId;

	public function __construct($appName, $request, $mapper, $currentUserId){
		parent::__construct($appName, $request);
		$this->mapper = $mapper;
		$this->currentUserId = $currentUserId;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $from
	 * @param int $to
	 * @return JSONResponse
	 */
	public function index($from=0, $to=20)
	{
		$mailBox = $this->getFolder();
		$json = $mailBox->getMessages($from, $to);

		return new JSONResponse($json);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $messageId
	 * @return JSONResponse
	 */
	public function show($messageId)
	{
		$mailBox = $this->getFolder();

		$m = $mailBox->getMessage($messageId);
		$json = $m->as_array();

		return new JSONResponse($json);
	}

	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @param $messageId
	 * @return JSONResponse
	 */
	public function destroy($messageId)
	{
		try {
			$mailBox = $this->getFolder();
			//
			// TODO: let's see how we implement delete
			//
			$mailBox->deleteMessage($messageId);

			return new JSONResponse();
		} catch (DoesNotExistException $e) {
			return new JSONResponse();
		}
	}

	/**
	 * TODO: private functions below have to be removed from controller -> imap service to be build
	 */

	private function getAccount()
	{
		$accountId = $this->params('accountId');
		return $this->mapper->find($this->currentUserId, $accountId);
	}

	/**
	 * @return \OCA\Mail\Mailbox
	 */
	private function getFolder()
	{
		$account = $this->getAccount();
		$m = new \OCA\Mail\Account($account);
		$folderId = $this->params('folderId');
		return $m->getMailbox($folderId);
	}

}
