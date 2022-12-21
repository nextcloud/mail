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

use Horde_Imap_Client;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\CouldNotConnectException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\JsonResponse as MailJsonResponse;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\SetupService;
use OCA\Mail\Service\Sync\SyncService;
use OCA\Mail\Validation\RemoteHostValidator;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class AccountsController extends Controller {
	private AccountService $accountService;
	private string $currentUserId;
	private LoggerInterface $logger;
	private IL10N $l10n;
	private AliasesService $aliasesService;
	private IMailTransmission $mailTransmission;
	private SetupService $setup;
	private IMailManager $mailManager;
	private SyncService $syncService;
	private IConfig $config;
	private RemoteHostValidator $hostValidator;

	public function __construct(string $appName,
								   IRequest $request,
								   AccountService $accountService,
								   $UserId,
								   LoggerInterface $logger,
								   IL10N $l10n,
								   AliasesService $aliasesService,
								   IMailTransmission $mailTransmission,
								   SetupService $setup,
								   IMailManager $mailManager,
								   SyncService $syncService,
									IConfig $config,
									RemoteHostValidator $hostValidator
	) {
		parent::__construct($appName, $request);
		$this->accountService = $accountService;
		$this->currentUserId = $UserId;
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->aliasesService = $aliasesService;
		$this->mailTransmission = $mailTransmission;
		$this->setup = $setup;
		$this->mailManager = $mailManager;
		$this->syncService = $syncService;
		$this->config = $config;
		$this->hostValidator = $hostValidator;
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
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	public function show(int $id): JSONResponse {
		return new JSONResponse($this->accountService->find($this->currentUserId, $id));
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 * @param string $accountName
	 * @param string $emailAddress
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
	 * @param string $authMethod
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	public function update(int $id,
						   string $accountName,
						   string $emailAddress,
						   string $imapHost = null,
						   int $imapPort = null,
						   string $imapSslMode = null,
						   string $imapUser = null,
						   string $imapPassword = null,
						   string $smtpHost = null,
						   int $smtpPort = null,
						   string $smtpSslMode = null,
						   string $smtpUser = null,
						   string $smtpPassword = null,
						   string $authMethod = 'password'): JSONResponse {
		try {
			// Make sure the account actually exists
			$this->accountService->find($this->currentUserId, $id);
		} catch (ClientException $e) {
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}
		if (!$this->hostValidator->isValid($imapHost)) {
			return MailJsonResponse::fail(
				[
					'error' => 'CONNECTION_ERROR',
					'service' => 'IMAP',
					'host' => $imapHost,
					'port' => $imapPort,
				],
			);
		}
		if (!$this->hostValidator->isValid($smtpHost)) {
			return MailJsonResponse::fail(
				[
					'error' => 'CONNECTION_ERROR',
					'service' => 'SMTP',
					'host' => $smtpHost,
					'port' => $smtpPort,
				],
			);
		}

		try {
			return MailJsonResponse::success(
				$this->setup->createNewAccount($accountName, $emailAddress, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->currentUserId, $authMethod, $id)
			);
		} catch (CouldNotConnectException $e) {
			$data = [
				'error' => $e->getReason(),
				'service' => $e->getService(),
				'host' => $e->getHost(),
				'port' => $e->getPort(),
			];

			$this->logger->info('Creating account failed: ' . $e->getMessage(), $data);
			return MailJsonResponse::fail($data);
		} catch (ServiceException $e) {
			$this->logger->error('Creating account failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return MailJsonResponse::error('Could not create account');
		}
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 * @param string|null $editorMode
	 * @param int|null $order
	 * @param bool|null $showSubscribedOnly
	 * @param int|null $draftsMailboxId
	 * @param int|null $sentMailboxId
	 * @param int|null $trashMailboxId
	 * @param int|null $archiveMailboxId
	 * @param bool|null $signatureAboveQuote
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 */
	public function patchAccount(int $id,
								 string $editorMode = null,
								 int $order = null,
								 bool $showSubscribedOnly = null,
								 int $draftsMailboxId = null,
								 int $sentMailboxId = null,
								 int $trashMailboxId = null,
								 int $archiveMailboxId = null,
								 bool $signatureAboveQuote = null): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $id);

		$dbAccount = $account->getMailAccount();

		if ($draftsMailboxId !== null) {
			$this->mailManager->getMailbox($this->currentUserId, $draftsMailboxId);
			$dbAccount->setDraftsMailboxId($draftsMailboxId);
		}
		if ($sentMailboxId !== null) {
			$this->mailManager->getMailbox($this->currentUserId, $sentMailboxId);
			$dbAccount->setSentMailboxId($sentMailboxId);
		}
		if ($trashMailboxId !== null) {
			$this->mailManager->getMailbox($this->currentUserId, $trashMailboxId);
			$dbAccount->setTrashMailboxId($trashMailboxId);
		}
		if ($archiveMailboxId !== null) {
			$this->mailManager->getMailbox($this->currentUserId, $archiveMailboxId);
			$dbAccount->setarchiveMailboxId($archiveMailboxId);
		}
		if ($editorMode !== null) {
			$dbAccount->setEditorMode($editorMode);
		}
		if ($order !== null) {
			$dbAccount->setOrder($order);
		}
		if ($showSubscribedOnly !== null) {
			$dbAccount->setShowSubscribedOnly($showSubscribedOnly);
		}
		if ($signatureAboveQuote !== null) {
			$dbAccount->setSignatureAboveQuote($signatureAboveQuote);
		}
		return new JSONResponse(
			$this->accountService->save($dbAccount)
		);
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 * @param string|null $signature
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function updateSignature(int $id, string $signature = null): JSONResponse {
		$this->accountService->updateSignature($id, $this->currentUserId, $signature);
		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 */
	public function destroy(int $id): JSONResponse {
		$this->accountService->delete($this->currentUserId, $id);
		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param string $accountName
	 * @param string $emailAddress
	 * @param string|null $imapHost
	 * @param int|null $imapPort
	 * @param string|null $imapSslMode
	 * @param string|null $imapUser
	 * @param string|null $imapPassword
	 * @param string|null $smtpHost
	 * @param int|null $smtpPort
	 * @param string|null $smtpSslMode
	 * @param string|null $smtpUser
	 * @param string|null $smtpPassword
	 * @param string $authMethod
	 *
	 * @return JSONResponse
	 */
	public function create(string $accountName,
		string $emailAddress,
		string $imapHost = null,
		int $imapPort = null,
		string $imapSslMode = null,
		string $imapUser = null,
		?string $imapPassword = null,
		string $smtpHost = null,
		int $smtpPort = null,
		string $smtpSslMode = null,
		string $smtpUser = null,
		?string $smtpPassword = null,
		string $authMethod = 'password'): JSONResponse {
		if ($this->config->getAppValue(Application::APP_ID, 'allow_new_mail_accounts', 'yes') === 'no') {
			$this->logger->info('Creating account disabled by admin.');
			return MailJsonResponse::error('Could not create account');
		}
		if (!$this->hostValidator->isValid($imapHost)) {
			return MailJsonResponse::fail(
				[
					'error' => 'CONNECTION_ERROR',
					'service' => 'IMAP',
					'host' => $imapHost,
					'port' => $imapPort,
				],
			);
		}
		if (!$this->hostValidator->isValid($smtpHost)) {
			return MailJsonResponse::fail(
				[
					'error' => 'CONNECTION_ERROR',
					'service' => 'SMTP',
					'host' => $smtpHost,
					'port' => $smtpPort,
				],
			);
		}
		try {
			return MailJsonResponse::success(
				$this->setup->createNewAccount($accountName, $emailAddress, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->currentUserId, $authMethod), Http::STATUS_CREATED
			);
		} catch (CouldNotConnectException $e) {
			$data = [
				'error' => $e->getReason(),
				'service' => $e->getService(),
				'host' => $e->getHost(),
				'port' => $e->getPort(),
			];

			$this->logger->info('Creating account failed: ' . $e->getMessage(), $data);
			return MailJsonResponse::fail($data);
		} catch (ServiceException $e) {
			$this->logger->error('Creating account failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return MailJsonResponse::error('Could not create account');
		}
	}

	/**
	 * @NoAdminRequired
	 * @TrapError
	 *
	 * @param int $id
	 * @param string $subject
	 * @param string $body
	 * @param string $to
	 * @param string $cc
	 * @param string $bcc
	 * @param int $uid
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 */
	public function draft(int $id,
						  string $subject,
						  string $body,
						  string $to,
						  string $cc,
						  string $bcc,
						  bool $isHtml = true,
						  int $draftId = null): JSONResponse {
		if ($draftId === null) {
			$this->logger->info("Saving a new draft in account <$id>");
		} else {
			$this->logger->info("Updating draft <$draftId> in account <$id>");
		}

		$account = $this->accountService->find($this->currentUserId, $id);
		$previousDraft = null;
		if ($draftId !== null) {
			try {
				$previousDraft = $this->mailManager->getMessage($this->currentUserId, $draftId);
			} catch (ClientException $e) {
				$this->logger->info("Draft " . $draftId . " could not be loaded: " . $e->getMessage());
			}
		}
		$messageData = NewMessageData::fromRequest($account, $to, $cc, $bcc, $subject, $body, [], $isHtml);

		try {
			/** @var Mailbox $draftsMailbox */
			[, $draftsMailbox, $newUID] = $this->mailTransmission->saveDraft($messageData, $previousDraft);
			$this->syncService->syncMailbox(
				$account,
				$draftsMailbox,
				Horde_Imap_Client::SYNC_NEWMSGSUIDS,
				[],
				false
			);
			return new JSONResponse([
				'id' => $this->mailManager->getMessageIdForUid($draftsMailbox, $newUID)
			]);
		} catch (ClientException | ServiceException $ex) {
			$this->logger->error('Saving draft failed: ' . $ex->getMessage());
			throw $ex;
		}
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	public function getQuota(int $id): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $id);

		$quota = $this->mailManager->getQuota($account);
		if ($quota === null) {
			return MailJsonResponse::fail([], Http::STATUS_NOT_IMPLEMENTED);
		}
		return MailJsonResponse::success($quota)->cacheFor(5 * 60, false, true);
	}
}
