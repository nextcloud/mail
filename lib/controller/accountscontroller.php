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
use Horde_Mail_Transport_Smtphorde;
use Horde_Mime_Headers_Date;
use Horde_Mime_Mail;
use Horde_Mime_Part;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AutoConfig;
use OCA\Mail\Service\ContactsIntegration;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http;
use OCP\Files\File;
use OCP\IL10N;
use OCP\ILogger;
use OCP\Security\ICrypto;

class AccountsController extends Controller {

	/** @var AccountService */
	private $accountService;

	/** @var string */
	private $currentUserId;

	/** @var ContactsIntegration */
	private $contactsIntegration;

	/** @var AutoConfig */
	private $autoConfig;

	/** @var \OCP\Files\Folder */
	private $userFolder;

	/** @var ILogger */
	private $logger;

	/** @var IL10N */
	private $l10n;

	/** @var ICrypto */
	private $crypto;

	/**
	 * @param string $appName
	 * @param \OCP\IRequest $request
	 * @param $accountService
	 * @param $currentUserId
	 * @param $userFolder
	 * @param $contactsIntegration
	 * @param $autoConfig
	 * @param $logger
	 * @param IL10N $l10n
	 * @param ICrypto $crypto
	 */
	public function __construct($appName,
		$request,
		$accountService,
		$currentUserId,
		$userFolder,
		$contactsIntegration,
		$autoConfig,
		$logger,
		IL10N $l10n,
		ICrypto $crypto
	) {
		parent::__construct($appName, $request);
		$this->accountService = $accountService;
		$this->currentUserId = $currentUserId;
		$this->userFolder = $userFolder;
		$this->contactsIntegration = $contactsIntegration;
		$this->autoConfig = $autoConfig;
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->crypto = $crypto;
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 *
	 * @return JSONResponse
	 */
	public function index() {
		$mailAccounts = $this->accountService->findByUserId($this->currentUserId);

		$json = [];
		foreach ($mailAccounts as $mailAccount) {
			$json[] = $mailAccount->getConfiguration();
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
			$account = $this->accountService->find($this->currentUserId, $accountId);

			return new JSONResponse($account->getConfiguration());
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], 404);
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
	 *
	 * @param string $accountId
	 * @return JSONResponse
	 */
	public function destroy($accountId) {
		try {
			$this->accountService->delete($this->currentUserId, $accountId);

			return new JSONResponse();
		} catch (DoesNotExistException $e) {
			return new JSONResponse();
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $accountName
	 * @param string $emailAddress
	 * @param string $password
	 * @param string $imapHost
	 * @param int    $imapPort
	 * @param string $imapSslMode
	 * @param string $imapUser
	 * @param string $imapPassword
	 * @param string $smtpHost
	 * @param int    $smtpPort
	 * @param string $smtpSslMode
	 * @param string $smtpUser
	 * @param string $smtpPassword
	 * @param bool   $autoDetect
	 * @return JSONResponse
	 */
	public function create($accountName, $emailAddress, $password,
		$imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword,
		$smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword,
		$autoDetect) {
		try {
			if ($autoDetect) {
				$this->logger->info('setting up auto detected account');
				$newAccount = $this->autoConfig->createAutoDetected($emailAddress, $password, $accountName);
			} else {
				$this->logger->info('Setting up manually configured account');
				$newAccount = new MailAccount([
					'accountName'  => $accountName,
					'emailAddress' => $emailAddress,
					'imapHost'     => $imapHost,
					'imapPort'     => $imapPort,
					'imapSslMode'  => $imapSslMode,
					'imapUser'     => $imapUser,
					'imapPassword' => $imapPassword,
					'smtpHost'     => $smtpHost,
					'smtpPort'     => $smtpPort,
					'smtpSslMode'  => $smtpSslMode,
					'smtpUser'     => $smtpUser,
					'smtpPassword' => $smtpPassword
				]);
				$newAccount->setUserId($this->currentUserId);
				$newAccount->setInboundPassword(
					$this->crypto->encrypt(
						$newAccount->getInboundPassword()
					)
				);
				$newAccount->setOutboundPassword(
					$this->crypto->encrypt(
						$newAccount->getOutboundPassword()
					)
				);

				$a = new Account($newAccount);
				$this->logger->debug('Connecting to account {account}', ['account' => $newAccount->getEmail()]);
				$a->testConnectivity();
			}

			if ($newAccount) {
				$this->accountService->save($newAccount);
				$this->logger->debug("account created " . $newAccount->getId());
				return new JSONResponse(
					['data' => ['id' => $newAccount->getId()]],
					Http::STATUS_CREATED);
			}
		} catch (\Exception $ex) {
			$this->logger->error('Creating account failed: ' . $ex->getMessage());
			return new JSONResponse(
				array('message' => $this->l10n->t('Creating account failed: ') . $ex->getMessage()),
				HTTP::STATUS_BAD_REQUEST);
		}

		$this->logger->info('Auto detect failed');
		return new JSONResponse(
			array('message' => $this->l10n->t('Auto detect failed. Please use manual mode.')),
			HTTP::STATUS_BAD_REQUEST);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int      $accountId
	 * @param string   $folderId
	 * @param string   $subject
	 * @param string   $body
	 * @param string   $to
	 * @param string   $cc
	 * @param string   $bcc
	 * @param int|bool $draftUID
	 * @param int      $messageId
	 * @param mixed    $attachments
	 * @return JSONResponse
	 */
	public function send($accountId, $folderId, $subject, $body, $to, $cc,
		$bcc, $draftUID, $messageId, $attachments) {
		$account = $this->accountService->find($this->currentUserId, $accountId);
		if (!$account instanceof Account) {
			return new JSONResponse(
				array('message' => 'Invalid account'),
				Http::STATUS_BAD_REQUEST
			);
		}

		// get sender data
		$headers = [];
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
		$folderId = base64_decode($folderId);
		$mailbox = null;
		if (!is_null($folderId) && !is_null($messageId)) {
			$mailbox = $account->getMailbox($folderId);
			$message = $mailbox->getMessage($messageId);

			if (is_null($subject)) {
				// prevent 'Re: Re:' stacking
				if(strcasecmp(substr($message->getSubject(), 0, 4), 'Re: ') === 0) {
					$headers['Subject'] = $message->getSubject();
				} else {
					$headers['Subject'] = 'Re: ' . $message->getSubject();
				}
			}
			$headers['In-Reply-To'] = $message->getMessageId();
			if (is_null($to)) {
				$to = $message->getToEmail();
			}
		}
		$headers['To'] = $to;

		// build mime body
		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);
		$mail->setBody($body);

		if (is_array($attachments)) {
			foreach($attachments as $attachment) {
				$fileName = $attachment['fileName'];
				if ($this->userFolder->nodeExists($fileName)) {
					$f = $this->userFolder->get($fileName);
					if ($f instanceof File) {
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
			$sentFolder->saveMessage($raw, [
				Horde_Imap_Client::FLAG_SEEN
			]);

			// delete draft message
			if (!is_null($draftUID)) {
				$draftsFolder = $account->getDraftsFolder();
				$folderId = $draftsFolder->getFolderId();
				$this->logger->debug("deleting sent draft <$draftUID> in folder <$folderId>");
				$draftsFolder->setMessageFlag($draftUID, \Horde_Imap_Client::FLAG_DELETED, true);
				$account->deleteDraft($draftUID);
				$this->logger->debug("sent draft <$draftUID> deleted");
			}
		} catch (\Horde_Exception $ex) {
			$this->logger->error('Sending mail failed: ' . $ex->getMessage());
			return new JSONResponse(
				array('message' => $ex->getMessage()),
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * 
	 * @param int      $accountId
	 * @param string   $subject
	 * @param string   $body
	 * @param string   $to
	 * @param string   $cc
	 * @param string   $bcc
	 * @param int|null $uid
	 * @return JSONResponse
	 */
	public function draft($accountId, $subject, $body, $to, $cc, $bcc, $uid) {

		if (!is_null($uid)) {
			$this->logger->info("Saving a new draft in accout <$accountId>");
		} else {
			$this->logger->info("Updating draft <$uid> in account <$accountId>");
		}

		$account = $this->accountService->find($this->currentUserId, $accountId);
		if (!$account instanceof Account) {
			return new JSONResponse(
				array('message' => 'Invalid account'),
				Http::STATUS_BAD_REQUEST
			);
		}

		// get sender data
		$headers = [];
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

		$headers['To'] = $to;
		$headers['Date'] = Horde_Mime_Headers_Date::create();

		// build mime body
		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);
		$bodyPart = new Horde_Mime_Part();
		$bodyPart->appendContents($body, [
			'encoding' => \Horde_Mime_Part::ENCODE_8BIT
		]);
		$mail->setBasePart($bodyPart);

		// create transport and save message
		try {
			// save the message in the drafts folder
			$draftsFolder = $account->getDraftsFolder();
			$raw = stream_get_contents($mail->getRaw());
			$newUid = $draftsFolder->saveDraft($raw);

			// delete old version if one exists
			if (!is_null($uid)) {
				$folderId = $draftsFolder->getFolderId();
				$this->logger->debug("deleting outdated draft <$uid> in folder <$folderId>");
				$draftsFolder->setMessageFlag($uid, \Horde_Imap_Client::FLAG_DELETED, true);
				$account->deleteDraft($uid);
				$this->logger->debug("draft <$uid> deleted");
			}
		} catch (\Horde_Exception $ex) {
			$this->logger->error('Saving draft failed: ' . $ex->getMessage());
			return new JSONResponse(
				[
					'message' => $ex->getMessage()
				],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		return new JSONResponse(
			[
				'uid' => $newUid
			]);
	}

	/**
	 * @NoAdminRequired
	 * @param string $term
	 * @return array
	 */
	public function autoComplete($term) {
		return $this->contactsIntegration->getMatchingRecipient($term);
	}

}
