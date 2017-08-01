<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Christoph Wurst <wurst.christoph@gmail.com>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 *
 * Mail
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

use Exception;
use Horde_Exception;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Model\Message;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\AutoConfig\AutoConfig;
use OCA\Mail\Service\Logger;
use OCA\Mail\Service\UnifiedAccount;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Http\Response;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Security\ICrypto;

class AccountsController extends Controller {

	/** @var AccountService */
	private $accountService;

	/** @var string */
	private $currentUserId;

	/** @var AutoConfig */
	private $autoConfig;

	/** @var Logger */
	private $logger;

	/** @var IL10N */
	private $l10n;

	/** @var ICrypto */
	private $crypto;

	/** @var AliasesService  */
	private $aliasesService;

	/** @var IMailTransmission */
	private $mailTransmission;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param AccountService $accountService
	 * @param $UserId
	 * @param AutoConfig $autoConfig
	 * @param Logger $logger
	 * @param IL10N $l10n
	 * @param ICrypto $crypto
	 */
	public function __construct($appName,
		IRequest $request,
		AccountService $accountService,
		$UserId,
		AutoConfig $autoConfig,
		Logger $logger,
		IL10N $l10n,
		ICrypto $crypto,
		AliasesService $aliasesService,
		IMailTransmission $mailTransmission
	) {
		parent::__construct($appName, $request);
		$this->accountService = $accountService;
		$this->currentUserId = $UserId;
		$this->autoConfig = $autoConfig;
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->crypto = $crypto;
		$this->aliasesService = $aliasesService;
		$this->mailTransmission = $mailTransmission;
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
			$conf = $mailAccount->getConfiguration();
			$conf['aliases'] = $this->aliasesService->findAll($conf['accountId'], $this->currentUserId);
			$json[] = $conf;
		}
		return new JSONResponse($json);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $accountId
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
	 * @param int $id
	 * @return JSONResponse
	 */
	public function destroy($id) {
		try {
			$this->accountService->delete($this->currentUserId, $id);
			return new JSONResponse();
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $accountName
	 * @param string $emailAddress
	 * @param string $password
	 * @param string $imapHost
	 * @param int $imapPort
	 * @param string $imapSslMode
	 * @param string $imapUser
	 * @param string $imapPassword
	 * @param string $smtpHost
	 * @param int $smtpPort
	 * @param string $smtpSslMode
	 * @param string $smtpUser
	 * @param string $smtpPassword
	 * @param bool $autoDetect
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
		} catch (Exception $ex) {
			$this->logger->error('Creating account failed: ' . $ex->getMessage());
			return new JSONResponse(
				array('message' => $this->l10n->t('Creating account failed: ') . $ex->getMessage()),
				Http::STATUS_BAD_REQUEST);
		}

		$this->logger->info('Auto detect failed');
		return new JSONResponse(
			array('message' => $this->l10n->t('Auto detect failed. Please use manual mode.')),
			Http::STATUS_BAD_REQUEST);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $accountId
	 * @param string $folderId
	 * @param string $subject
	 * @param string $body
	 * @param string $to
	 * @param string $cc
	 * @param string $bcc
	 * @param int $draftUID
	 * @param int $messageId
	 * @param mixed $attachments
	 * @return JSONResponse
	 */
	public function send($accountId, $folderId, $subject, $body, $to, $cc, $bcc, $draftUID, $messageId, $attachments,
		$aliasId) {
		$account = $this->accountService->find($this->currentUserId, $accountId);
		$alias = $aliasId ? $this->aliasesService->find($aliasId, $this->currentUserId) : null;

		$messageData = NewMessageData::fromRequest($account, $to, $cc, $bcc, $subject, $body, $attachments);
		$repliedMessageData = new RepliedMessageData($account, $folderId, $messageId);

		try {
			$this->mailTransmission->sendMessage($this->currentUserId, $messageData, $repliedMessageData, $alias, $draftUID);
		} catch (Horde_Exception $ex) {
			$this->logger->error('Sending mail failed: ' . $ex->getMessage());
			return new JSONResponse([
				'message' => $ex->getMessage()
				], Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * 
	 * @param int $accountId
	 * @param string $subject
	 * @param string $body
	 * @param string $to
	 * @param string $cc
	 * @param string $bcc
	 * @param int $uid
	 * @param string $messageId
	 * @return JSONResponse
	 */
	public function draft($accountId, $subject, $body, $to, $cc, $bcc, $uid, $messageId) {
		if (is_null($uid)) {
			$this->logger->info("Saving a new draft in account <$accountId>");
		} else {
			$this->logger->info("Updating draft <$uid> in account <$accountId>");
		}

		$account = $this->accountService->find($this->currentUserId, $accountId);
		if ($account instanceof UnifiedAccount) {
			list($account) = $account->resolve($messageId);
		}
		if (!$account instanceof Account) {
			return new JSONResponse(
				array('message' => 'Invalid account'),
				Http::STATUS_BAD_REQUEST
			);
		}

		$message = $account->newMessage();
		$message->setTo(Message::parseAddressList($to));
		$message->setSubject($subject ? : '');
		$message->setFrom($account->getEMailAddress());
		$message->setCC(Message::parseAddressList($cc));
		$message->setBcc(Message::parseAddressList($bcc));
		$message->setContent($body);

		// create transport and save message
		try {
			$newUID = $account->saveDraft($message, $uid);
		} catch (Horde_Exception $ex) {
			$this->logger->error('Saving draft failed: ' . $ex->getMessage());
			return new JSONResponse(
				[
					'message' => $ex->getMessage()
				],
				Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}

		return new JSONResponse([
			'uid' => $newUID
		]);
	}

}
