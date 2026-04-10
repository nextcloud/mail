<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use Horde_Imap_Client;
use OCA\Mail\Account;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\CouldNotConnectException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\JsonResponse as MailJsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\DelegationService;
use OCA\Mail\Service\SetupService;
use OCA\Mail\Service\Sync\SyncService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IRequest;
use OCP\Security\IRemoteHostValidator;
use Psr\Log\LoggerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
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
	private IRemoteHostValidator $hostValidator;
	private MailboxSync $mailboxSync;
	private DelegationService $delegationService;

	public function __construct(
		string $appName,
		IRequest $request,
		AccountService $accountService,
		$userId,
		LoggerInterface $logger,
		IL10N $l10n,
		AliasesService $aliasesService,
		IMailTransmission $mailTransmission,
		SetupService $setup,
		IMailManager $mailManager,
		SyncService $syncService,
		IConfig $config,
		IRemoteHostValidator $hostValidator,
		MailboxSync $mailboxSync,
		private ITimeFactory $timeFactory,
		DelegationService $delegationService,
	) {
		parent::__construct($appName, $request);
		$this->accountService = $accountService;
		$this->currentUserId = $userId;
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->aliasesService = $aliasesService;
		$this->mailTransmission = $mailTransmission;
		$this->setup = $setup;
		$this->mailManager = $mailManager;
		$this->syncService = $syncService;
		$this->config = $config;
		$this->hostValidator = $hostValidator;
		$this->mailboxSync = $mailboxSync;
		$this->delegationService = $delegationService;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JSONResponse
	 */
	#[TrapError]
	public function index(): JSONResponse {
		$mailAccounts = $this->accountService->findByUserId($this->currentUserId);

		$json = [];
		foreach ($mailAccounts as $mailAccount) {
			$conf = $mailAccount->jsonSerialize();
			$conf['aliases'] = $this->aliasesService->findAll($conf['accountId'], $this->currentUserId);
			$conf['isDelegated'] = false;
			$json[] = $conf;
		}

		$delegatedAccounts = $this->accountService->findDelegatedAccounts($this->currentUserId);
		foreach ($delegatedAccounts as $delegatedAccount) {
			$conf = $delegatedAccount->jsonSerialize();
			$conf['isDelegated'] = true;
			$conf['aliases'] = $this->aliasesService->findAll($conf['accountId'], $delegatedAccount->getUserId());
			$json[] = $conf;
		}
		return new JSONResponse($json);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	#[TrapError]
	public function show(int $id): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($id, $this->currentUserId);
		return new JSONResponse($this->accountService->find($effectiveUserId, $id));
	}

	/**
	 * @NoAdminRequired
	 *
	 * @throws ClientException
	 */
	#[TrapError]
	public function update(int $id,
		string $accountName,
		string $emailAddress,
		string $imapHost,
		int $imapPort,
		string $imapSslMode,
		string $imapUser,
		string $smtpHost,
		int $smtpPort,
		string $smtpSslMode,
		string $smtpUser,
		?string $imapPassword = null,
		?string $smtpPassword = null,
		string $authMethod = 'password'): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($id, $this->currentUserId);
		try {
			// Make sure the account actually exists
			$this->accountService->find($effectiveUserId, $id);
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
			$result = MailJsonResponse::success(
				$this->setup->createNewAccount($accountName, $emailAddress, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $effectiveUserId, $authMethod, $id)
			);
			return $result;
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
	 *
	 * @param int $id
	 * @param string|null $editorMode
	 * @param int|null $order
	 * @param bool|null $showSubscribedOnly
	 * @param int|null $draftsMailboxId
	 * @param int|null $sentMailboxId
	 * @param int|null $trashMailboxId
	 * @param int|null $archiveMailboxId
	 * @param int|null $snoozeMailboxId
	 * @param bool|null $signatureAboveQuote
	 * @param bool|null $classificationEnabled
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 */
	#[TrapError]
	public function patchAccount(int $id,
		?string $editorMode = null,
		?int $order = null,
		?bool $showSubscribedOnly = null,
		?int $draftsMailboxId = null,
		?int $sentMailboxId = null,
		?int $trashMailboxId = null,
		?int $archiveMailboxId = null,
		?int $snoozeMailboxId = null,
		?bool $signatureAboveQuote = null,
		?int $trashRetentionDays = null,
		?int $junkMailboxId = null,
		?bool $searchBody = null,
		?bool $classificationEnabled = null,
		?bool $imipCreate = null,
	): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($id, $this->currentUserId);
		$account = $this->accountService->find($effectiveUserId, $id);

		$dbAccount = $account->getMailAccount();

		if ($draftsMailboxId !== null) {
			$this->mailManager->getMailbox($effectiveUserId, $draftsMailboxId);
			$dbAccount->setDraftsMailboxId($draftsMailboxId);
		}
		if ($sentMailboxId !== null) {
			$this->mailManager->getMailbox($effectiveUserId, $sentMailboxId);
			$dbAccount->setSentMailboxId($sentMailboxId);
		}
		if ($trashMailboxId !== null) {
			$this->mailManager->getMailbox($effectiveUserId, $trashMailboxId);
			$dbAccount->setTrashMailboxId($trashMailboxId);
		}
		if ($archiveMailboxId !== null) {
			$this->mailManager->getMailbox($effectiveUserId, $archiveMailboxId);
			$dbAccount->setarchiveMailboxId($archiveMailboxId);
		}
		if ($snoozeMailboxId !== null) {
			$this->mailManager->getMailbox($effectiveUserId, $snoozeMailboxId);
			$dbAccount->setSnoozeMailboxId($snoozeMailboxId);
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
		if ($trashRetentionDays !== null) {
			// Passing 0 (or lower) disables retention
			$dbAccount->setTrashRetentionDays($trashRetentionDays <= 0 ? null : $trashRetentionDays);
		}
		if ($junkMailboxId !== null) {
			$this->mailManager->getMailbox($effectiveUserId, $junkMailboxId);
			$dbAccount->setJunkMailboxId($junkMailboxId);
		}
		if ($searchBody !== null) {
			$dbAccount->setSearchBody($searchBody);
		}
		if ($classificationEnabled !== null) {
			$dbAccount->setClassificationEnabled($classificationEnabled);
		}
		if ($imipCreate !== null) {
			$dbAccount->setImipCreate($imipCreate);
		}
		$result = new JSONResponse(
			new Account($this->accountService->save($dbAccount))
		);
		return $result;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string|null $signature
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	#[TrapError]
	public function updateSignature(int $id, ?string $signature = null): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($id, $this->currentUserId);
		$this->accountService->updateSignature($id, $effectiveUserId, $signature);
		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 */
	#[TrapError]
	public function destroy(int $id): JSONResponse {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($id, $this->currentUserId);
		$this->accountService->delete($effectiveUserId, $id);
		return new JSONResponse();
	}

	/**
	 * @NoAdminRequired
	 */
	#[TrapError]
	public function create(string $accountName,
		string $emailAddress,
		string $imapHost,
		int $imapPort,
		string $imapSslMode,
		string $imapUser,
		string $smtpHost,
		int $smtpPort,
		string $smtpSslMode,
		string $smtpUser,
		?string $imapPassword = null,
		?string $smtpPassword = null,
		string $authMethod = 'password',
		?bool $classificationEnabled = null): JSONResponse {
		if ($this->config->getAppValue(Application::APP_ID, 'allow_new_mail_accounts', 'yes') === 'no') {
			$this->logger->info('Creating account disabled by admin.');
			return MailJsonResponse::error('Could not create account');
		}
		if (!$this->hostValidator->isValid($imapHost)) {
			$this->logger->debug('Prevented access to invalid IMAP host', [
				'host' => $imapHost,
			]);
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
			$this->logger->debug('Prevented access to invalid SMTP host', [
				'host' => $smtpHost,
			]);
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
			$account = $this->setup->createNewAccount($accountName, $emailAddress, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->currentUserId, $authMethod, null, $classificationEnabled);
			// Set initial heartbeat
			$this->config->setUserValue(
				$account->getUserId(),
				Application::APP_ID,
				'ui-heartbeat',
				(string)$this->timeFactory->getTime(),
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
		if ($authMethod != 'xoauth2') {
			try {
				$this->mailboxSync->sync($account, $this->logger);
			} catch (ServiceException $e) {
				$this->logger->error('Failed syncing the newly created account' . $e->getMessage(), [
					'exception' => $e,
				]);
			}
		}
		return MailJsonResponse::success(
			$account, Http::STATUS_CREATED
		);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 */
	#[TrapError]
	public function draft(int $id,
		string $subject,
		string $body,
		string $to,
		string $cc,
		string $bcc,
		bool $isHtml = true,
		?int $draftId = null): JSONResponse {
		if ($draftId === null) {
			$this->logger->info("Saving a new draft in account <$id>");
		} else {
			$this->logger->info("Updating draft <$draftId> in account <$id>");
		}

		$effectiveUserId = $this->delegationService->resolveAccountUserId($id, $this->currentUserId);
		$account = $this->accountService->find($effectiveUserId, $id);
		$previousDraft = null;
		if ($draftId !== null) {
			try {
				$previousDraft = $this->mailManager->getMessage($effectiveUserId, $draftId);
			} catch (ClientException $e) {
				$this->logger->info("Draft {$draftId} could not be loaded: {$e->getMessage()}");
			}
		}
		$messageData = NewMessageData::fromRequest($account, $subject, $body, $to, $cc, $bcc, [], $isHtml);

		try {
			/** @var Mailbox $draftsMailbox */
			[, $draftsMailbox, $newUID] = $this->mailTransmission->saveDraft($messageData, $previousDraft);
			$this->syncService->syncMailbox(
				$account,
				$draftsMailbox,
				Horde_Imap_Client::SYNC_NEWMSGSUIDS,
				false,
				null,
				[]
			);
			return new JSONResponse([
				'id' => $this->mailManager->getMessageIdForUid($draftsMailbox, $newUID)
			]);
		} catch (ClientException|ServiceException $ex) {
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
		$effectiveUserId = $this->delegationService->resolveAccountUserId($id, $this->currentUserId);
		$account = $this->accountService->find($effectiveUserId, $id);

		$quota = $this->mailManager->getQuota($account);
		if ($quota === null) {
			return MailJsonResponse::fail([], Http::STATUS_NOT_IMPLEMENTED);
		}
		return MailJsonResponse::success($quota)->cacheFor(5 * 60, false, true);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id Account id
	 * @param ?int $smimeCertificateId
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 */
	public function updateSmimeCertificate(int $id, ?int $smimeCertificateId = null) {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($id, $this->currentUserId);
		$account = $this->accountService->find($effectiveUserId, $id)->getMailAccount();
		$account->setSmimeCertificateId($smimeCertificateId);
		$this->accountService->update($account);
		return MailJsonResponse::success();
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id Account id
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 */
	public function testAccountConnection(int $id) {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($id, $this->currentUserId);
		return new JSONResponse([
			'data' => $this->accountService->testAccountConnection($effectiveUserId, $id),
		]);
	}

}
