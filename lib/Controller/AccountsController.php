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
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\AutoConfig\AutoConfig;
use OCA\Mail\Service\Logger;
use OCA\Mail\Service\SetupService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
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

	/** @var SetupService */
	private $setup;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param AccountService $accountService
	 * @param $UserId
	 * @param Logger $logger
	 * @param IL10N $l10n
	 * @param ICrypto $crypto
	 * @param SetupService $setup
	 */
	public function __construct($appName, IRequest $request, AccountService $accountService, $UserId, Logger $logger, IL10N $l10n, ICrypto $crypto, AliasesService $aliasesService, IMailTransmission $mailTransmission, SetupService $setup
	) {
		parent::__construct($appName, $request);
		$this->accountService = $accountService;
		$this->currentUserId = $UserId;
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->crypto = $crypto;
		$this->aliasesService = $aliasesService;
		$this->mailTransmission = $mailTransmission;
		$this->setup = $setup;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JSONResponse
	 */
	public function index() {
		$mailAccounts = $this->accountService->findByUserId($this->currentUserId);

		$json = [];
		foreach ($mailAccounts as $mailAccount) {
			$conf = $mailAccount->jsonSerialize();
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

			return new JSONResponse($account);
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], 404);
		}
	}

	/**
	 * @param array $config
	 * @param integer $id
	 */
	private function createAccount($config, $id = null) {
		$account = null;
		$autoDetect = $config['autoDetect'] === 'true';

		try {
			if ($autoDetect) {
				$account = $this->setup->createNewAutoconfiguredAccount($config['accountName'], $config['emailAddress'], $config['password']);
				if (is_null($account)) {
					throw new Exception();
				}
			} else {
				$account = $this->setup->createNewAccount($config['accountName'], $config['emailAddress'], $config['imapHost'], $config['imapPort'], $config['imapSslMode'], $config['imapUser'], $config['imapPassword'], $config['smtpHost'], $config['smtpPort'], $config['smtpSslMode'], $config['smtpUser'], $config['smtpPassword'], $this->currentUserId, $id);
			}
		} catch (Exception $ex) {
			if ($autoDetect) {
				return new JSONResponse([
					'message' => $this->l10n->t('Auto detect failed. Please use manual mode.')
					], Http::STATUS_BAD_REQUEST);
			} else {
				$this->logger->error('Creating account failed: ' . $ex->getMessage());
				return new JSONResponse([
					'message' => $this->l10n->t('Creating account failed: ') . $ex->getMessage()
					], Http::STATUS_BAD_REQUEST);
			}
		}

		return new JSONResponse(['data' => ['id' => $account->getId()]], Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param array $config
	 * @return JSONResponse
	 */
	public function update($id, $config) {
		return $this->createAccount($config, $id);
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
	 * @param array $config
	 * @return JSONResponse
	 */
	public function create($config) {
		return $this->createAccount($config);
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
	public function send($accountId, $folderId, $subject, $body, $to, $cc, $bcc, $draftUID, $messageId, $attachments, $aliasId) {
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
	 * @return JSONResponse
	 */
	public function draft($accountId, $subject, $body, $to, $cc, $bcc, $uid) {
		if (is_null($uid)) {
			$this->logger->info("Saving a new draft in account <$accountId>");
		} else {
			$this->logger->info("Updating draft <$uid> in account <$accountId>");
		}

		$account = $this->accountService->find($this->currentUserId, $accountId);
		$messageData = NewMessageData::fromRequest($account, $to, $cc, $bcc, $subject, $body, []);

		try {
			$newUID = $this->mailTransmission->saveDraft($messageData, $uid);
			return new JSONResponse([
				'uid' => $newUID,
			]);
		} catch (ServiceException $ex) {
			$this->logger->error('Saving draft failed: ' . $ex->getMessage());
			return new JSONResponse([
				'message' => $ex->getMessage()
				], Http::STATUS_INTERNAL_SERVER_ERROR
			);
		}
	}

}
