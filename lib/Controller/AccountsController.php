<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Christoph Wurst <wurst.christoph@gmail.com>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Matthias Rella <mrella@pisys.eu>
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
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\JSONResponse;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\GroupsIntegration;
use OCA\Mail\Service\Logger;
use OCA\Mail\Service\SetupService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Security\ICrypto;

class AccountsController extends Controller {

	/** @var AccountService */
	private $accountService;

	/** @var GroupsIntegration */
	private $groupsIntegration;

	/** @var string */
	private $currentUserId;

	/** @var Logger */
	private $logger;

	/** @var IL10N */
	private $l10n;

	/** @var ICrypto */
	private $crypto;

	/** @var AliasesService */
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
	public function __construct($appName, IRequest $request, AccountService $accountService, GroupsIntegration $groupsIntegration, $UserId, Logger $logger, IL10N $l10n, ICrypto $crypto, AliasesService $aliasesService, IMailTransmission $mailTransmission, SetupService $setup
	) {
		parent::__construct($appName, $request);
		$this->accountService = $accountService;
		$this->groupsIntegration = $groupsIntegration;
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
	 * @TrapError
	 *
	 * @return JSONResponse
	 */
	public function index(): JSONResponse {
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
	 * @TrapError
	 *
	 * @param int $accountId
	 * @return JSONResponse
	 * @throws Exception
	 */
	public function show($accountId): JSONResponse {
		return new JSONResponse($this->accountService->find($this->currentUserId, $accountId));
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
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
	 * @throws ClientException
	 */
	public function update(int $id, string $accountName, string $emailAddress, string $password, string $imapHost, int $imapPort, string $imapSslMode, string $imapUser, string $imapPassword, string $smtpHost, int $smtpPort, string $smtpSslMode, string $smtpUser, string $smtpPassword, bool $autoDetect): JSONResponse {
		$account = null;
		$errorMessage = null;
		try {
			if ($autoDetect) {
				$account = $this->setup->createNewAutoconfiguredAccount($accountName, $emailAddress, $password);
			} else {
				$account = $this->setup->createNewAccount($accountName, $emailAddress, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->currentUserId, $id);
			}
		} catch (Exception $ex) {
			$errorMessage = $ex->getMessage();
		}

		if (is_null($account)) {
			if ($autoDetect) {
				throw new ClientException($this->l10n->t('Auto detect failed. Please use manual mode.'));
			} else {
				$this->logger->error('Updating account failed: ' . $errorMessage);
				throw new ClientException($this->l10n->t('Updating account failed: ') . $errorMessage);
			}
		}

		return new JSONResponse([
			'data' => [
				'id' => $account->getId()
			]
		]);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 * @return JSONResponse
	 */
	public function destroy($id): JSONResponse {
		$this->accountService->delete($this->currentUserId, $id);
		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
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
	 * @throws ClientException
	 */
	public function create(string $accountName, string $emailAddress, string $password, string $imapHost = null, int $imapPort = null, string $imapSslMode = null, string $imapUser = null, string $imapPassword = null, string $smtpHost = null, int $smtpPort = null, string $smtpSslMode = null, string $smtpUser = null, string $smtpPassword = null, bool $autoDetect = true): JSONResponse {
		$account = null;
		$errorMessage = null;
		try {
			if ($autoDetect) {
				$account = $this->setup->createNewAutoconfiguredAccount($accountName, $emailAddress, $password);
			} else {
				$account = $this->setup->createNewAccount($accountName, $emailAddress, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->currentUserId);
			}
		} catch (Exception $ex) {
			$errorMessage = $ex->getMessage();
		}

		if (is_null($account)) {
			if ($autoDetect) {
				throw new ClientException($this->l10n->t('Auto detect failed. Please use manual mode.'));
			} else {
				$this->logger->error('Creating account failed: ' . $errorMessage);
				throw new ClientException($this->l10n->t('Creating account failed: ') . $errorMessage);
			}
		}

		return new JSONResponse([
			'data' => [
				'id' => $account->getId()
			]
		], Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $accountId
	 * @param string $subject
	 * @param string $body
	 * @param string $to
	 * @param string $cc
	 * @param string $bcc
	 * @param int|null $draftUID
	 * @param string|null $folderId
	 * @param int|null $messageId
	 * @param mixed $attachments
	 * @param int|null $aliasId
	 * @return JSONResponse
	 * @throws Horde_Exception
	 */
	public function send(int $accountId, string $subject = null, string $body, string $to, string $cc, string $bcc, int $draftUID = null, string $folderId = null, int $messageId = null, array $attachments = [], int $aliasId = null): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $accountId);
		$alias = $aliasId ? $this->aliasesService->find($aliasId, $this->currentUserId) : null;

		$expandedTo = $this->groupsIntegration->expand($to);
		$expandedCc = $this->groupsIntegration->expand($cc);
		$expandedBcc = $this->groupsIntegration->expand($bcc);

		$messageData = NewMessageData::fromRequest($account, $expandedTo, $expandedCc, $expandedBcc, $subject, $body, $attachments);
		$repliedMessageData = new RepliedMessageData($account, $folderId, $messageId);

		try {
			$this->mailTransmission->sendMessage($this->currentUserId, $messageData, $repliedMessageData, $alias, $draftUID);
			return new JSONResponse();
		} catch (Horde_Exception $ex) {
			$this->logger->error('Sending mail failed: ' . $ex->getMessage());
			throw $ex;
		}
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
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
	public function draft(int $accountId, string $subject = null, string $body, string $to, string $cc, string $bcc, int $uid = null): JSONResponse {
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
			throw $ex;
		}
	}

}
