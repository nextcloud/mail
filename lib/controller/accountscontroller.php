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

use Horde_Imap_Client;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AutoConfig;
use OCA\Mail\Service\ContactsIntegration;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http;

class AccountsController extends Controller
{
	/**
	 * @var \OCA\Mail\Db\MailAccountMapper
	 */
	private $mapper;

	/**
	 * @var string
	 */
	private $currentUserId;

	/**
	 * @var ContactsIntegration
	 */
	private $contactsIntegration;

	/**
	 * @var AutoConfig
	 */
	private $autoConfig;

	/**
	 * @var \OCP\Files\Folder
	 */
	private $userFolder;

	public function __construct($appName,
		$request,
		$mailAccountMapper,
		$currentUserId,
		$userFolder,
		$contactsIntegration,
		$autoConfig) {
		parent::__construct($appName, $request);
		$this->mapper = $mailAccountMapper;
		$this->currentUserId = $currentUserId;
		$this->userFolder = $userFolder;
		$this->contactsIntegration = $contactsIntegration;
		$this->autoConfig = $autoConfig;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	public function index() {
		$mailAccounts = $this->mapper->findByUserId($this->currentUserId);

		$json = array();
		foreach ($mailAccounts as $mailAccount) {
			$json[] = $mailAccount->toJson();
		}

		return new JSONResponse($json);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param $accountId
	 * @return JSONResponse
	 */
	public function show($accountId) {
		try {
			$account = $this->mapper->find($this->currentUserId, $accountId);

			return new JSONResponse($account->toJson());
		} catch (DoesNotExistException $e) {
			return new JSONResponse(array(), 404);
		}
	}

	/**
	 * @NoAdminRequired
	 */
	public function update() {
		$response = new Response();
		$response->setStatus(Http::STATUS_NOT_IMPLEMENTED);
		return $response;
	}

	/**
	 * @NoAdminRequired
	 */
	public function destroy($accountId) {
		try {
			$mailAccount = $this->mapper->find($this->currentUserId, $accountId);
			$this->mapper->delete($mailAccount);

			return new JSONResponse();
		} catch (DoesNotExistException $e) {
			return new JSONResponse();
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param bool $autoDetect
	 * @return JSONResponse
	 */
	public function create($autoDetect)
	{
		try {
			if ($autoDetect) {
				$name = $this->params('accountName');
				$email = $this->params('emailAddress');
				$password = $this->params('password');
				$newAccount = $this->autoConfig->createAutoDetected($email, $password, $name);
			} else {
				$newAccount = new MailAccount($this->getParams());
				$newAccount->setUserId($this->currentUserId);

				$a = new Account($newAccount);
				// connect to imap
				$a->getImapConnection();

				// connect to smtp
				$smtp = $a->createTransport();
				$smtp->getSMTPObject();
			}

			if ($newAccount) {
				$this->mapper->save($newAccount);
				return new JSONResponse(
					array('data' => array('id' => $newAccount->getId())),
					Http::STATUS_CREATED);
			}
		} catch (\Exception $ex) {
			$l = new \OC_L10N('mail');
			return new JSONResponse(
				array('message' => $l->t('Creating account failed: ' . $ex->getMessage())),
				HTTP::STATUS_BAD_REQUEST);
		}

		$l = new \OC_L10N('mail');
		return new JSONResponse(
			array('message' => $l->t('Auto detect failed. Please use manual mode.')),
			HTTP::STATUS_BAD_REQUEST);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $accountId
	 * @return JSONResponse
	 */
	public function send($accountId) {

		$subject = $this->params('subject');
		$body = $this->params('body');
		$to = $this->params('to');
		$cc = $this->params('cc');
		$bcc = $this->params('bcc');

		$dbAccount = $this->mapper->find($this->currentUserId, $accountId);
		$account = new Account($dbAccount);

		// get sender data
		$headers = array();
		$from = new Horde_Mail_Rfc822_Address($account->getEMailAddress());
		$from->personal = $account->getName();
		$headers['From']= $from;
		$headers['Subject'] = $subject;

		if (trim($cc) !== '') {
			$headers['Cc'] = trim($cc);
		}
		if (trim($bcc) !== '') {
			$headers['Bcc'] = trim($bcc);
		}

		// in reply to handling
		$folderId = base64_decode($this->params('folderId'));
		$messageId = $this->params('messageId');
		$mailbox = null;
		if (!is_null($folderId) && !is_null($messageId)) {
			$mailbox = $account->getMailbox($folderId);
			$message = $mailbox->getMessage($messageId);

			if (is_null($subject)) {
				$headers['Subject'] = "RE: " . $message->getSubject();
			}
			$headers['In-Reply-To'] = $message->getMessageId();
			if (is_null($to)) {
				$to = $message->getToEmail();
			}
		}
		$headers['To'] = $to;

		// build mime body
		$mail = new \Horde_Mime_Mail();
		$mail->addHeaders($headers);
		$mail->setBody($body);

		$attachments = $this->params('attachments');
		if (is_array($attachments)) {
			foreach($attachments as $attachment) {
				$fileName = $attachment['fileName'];
				if ($this->userFolder->nodeExists($fileName)) {
					$f = $this->userFolder->get($fileName);
					if ($f instanceof \OCP\Files\File) {
						$a = new \Horde_Mime_Part();
						$a->setCharset('us-ascii');
						$a->setDisposition('attachment');
						$a->setName($f->getName());
						$a->setContents($f->getContent());
						$a->setType($f->getMimeType());
						$mail->addMimePart($a);
					}
				}
			}
		}

		// create transport and send
		try {
			$transport = $account->createTransport();
			$mail->send($transport);

			// in case of reply we flag the message as answered
			if ($mailbox) {
				$mailbox->setMessageFlag($messageId, Horde_Imap_Client::FLAG_ANSWERED, true);
			}

			// save the message in the sent folder
			$sentFolder = $account->getSentFolder();
                        $raw = stream_get_contents($mail->getRaw());
                        $sentFolder->saveMessage($raw);
		} catch (\Horde_Exception $ex) {
			return new JSONResponse(
				array('message' => $ex->getMessage()),
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		return new JSONResponse();
	}

	/**
	 * @param $term
	 */
	public function autoComplete($term) {
		return $this->contactsIntegration->getMatchingRecipient( $term );
	}

}
