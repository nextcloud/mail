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

use OCA\Mail\AttachmentDownloadResponse;
use OCA\Mail\App;
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
		$this->userFolder = \OC::$server->getUserFolder();
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $from
	 * @param int $to
	 * @return JSONResponse
	 */
	public function index($from=0, $to=40)
	{
		$mailBox = $this->getFolder();
		$json = $mailBox->getMessages($from, $to-$from);

		$json = array_map(function($j) {
			$j['senderImage'] = App::getPhoto($j['fromEmail']);
			return $j;
		}, $json);

		return new JSONResponse($json);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	public function show($id)
	{
		$accountId = $this->params('accountId');
		$folderId = $this->params('folderId');
		$mailBox = $this->getFolder();

		$m = $mailBox->getMessage($id);
		$json = $m->as_array();
		$json['senderImage'] = App::getPhoto($m->getFromEmail());

		if (isset($json['attachment'])) {
			$json['attachment'] = $this->enrichDownloadUrl($accountId, $folderId, $id, $json['attachment']);
		}
		if (isset($json['attachments'])) {
			$json['attachments'] = array_map(function($a) use($accountId, $folderId, $id) {
				return $this->enrichDownloadUrl($accountId, $folderId, $id, $a);
			}, $json['attachments']);
		}

		return new JSONResponse($json);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $messageId
	 * @param int $attachmentId
	 * @return AttachmentDownloadResponse
	 */
	public function downloadAttachment($messageId, $attachmentId)
	{
		$mailBox = $this->getFolder();

		$attachment = $mailBox->getAttachment($messageId, $attachmentId);

		return new AttachmentDownloadResponse(
			$attachment->getContents(),
			$attachment->getName(),
			$attachment->getType());
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @param int $messageId
	 * @param int $attachmentId
	 * @param string $targetPath
	 * @return JSONResponse
	 */
	public function saveAttachment($messageId, $attachmentId, $targetPath) {
		$mailBox = $this->getFolder();

		$attachment = $mailBox->getAttachment($messageId, $attachmentId);

		$fileName = $attachment->getName();
		$fullPath = "$targetPath/$fileName";
		$counter = 0;
		while($this->userFolder->nodeExists($fullPath)) {
			$fullPath = "$targetPath/$counter-$fileName";
			$counter++;
		}

		$newFile = $this->userFolder->newFile($fullPath);
		$newFile->putContent($attachment->getContents());

		return new JSONResponse();
	}
	/**
	 * @IsAdminExemption
	 * @IsSubAdminExemption
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	public function destroy($id)
	{
		try {
			$mailBox = $this->getFolder();
			//
			// TODO: let's see how we implement delete
			//
			$mailBox->deleteMessage($id);

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

	/**
	 * @param $id
	 * @param $accountId
	 * @param $folderId
	 * @return callable
	 */
	private function enrichDownloadUrl($accountId, $folderId, $id, $attachment) {
		$downloadUrl = \OCP\Util::linkToRoute('mail.messages.downloadAttachment', array(
			'accountId' => $accountId,
			'folderId' => $folderId,
			'messageId' => $id,
			'attachmentId' => $attachment['id'],
		));
		$downloadUrl = \OC_Helper::makeURLAbsolute($downloadUrl);
		$attachment['downloadUrl'] = $downloadUrl;
		$attachment['mimeUrl'] = \OC_Helper::mimetypeIcon($attachment['mime']);
		return $attachment;
	}

}
