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
use Horde_Imap_Client;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\CouldNotConnectException;
use OCA\Mail\Exception\ManyRecipientsException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\JsonResponse as MailJsonResponse;
use OCA\Mail\Model\NewMessageData;
use OCA\Mail\Model\RepliedMessageData;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\GroupsIntegration;
use OCA\Mail\Service\SetupService;
use OCA\Mail\Service\Sync\SyncService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IL10N;
use OCP\IRequest;
use Psr\Log\LoggerInterface;

class AccountsController extends Controller {

	/** @var AccountService */
	private $accountService;

	/** @var GroupsIntegration */
	private $groupsIntegration;

	/** @var string */
	private $currentUserId;

	/** @var LoggerInterface */
	private $logger;

	/** @var IL10N */
	private $l10n;

	/** @var AliasesService */
	private $aliasesService;

	/** @var IMailTransmission */
	private $mailTransmission;

	/** @var SetupService */
	private $setup;

	/** @var IMailManager */
	private $mailManager;

	/** @var SyncService */
	private $syncService;

	/**
	 * AccountsController constructor.
	 *
	 * @param string $appName
	 * @param IRequest $request
	 * @param AccountService $accountService
	 * @param GroupsIntegration $groupsIntegration
	 * @param $UserId
	 * @param LoggerInterface $logger
	 * @param IL10N $l10n
	 * @param AliasesService $aliasesService
	 * @param IMailTransmission $mailTransmission
	 * @param SetupService $setup
	 * @param IMailManager $mailManager
	 * @param SyncService $syncService
	 */
	public function __construct(string $appName,
								   IRequest $request,
								   AccountService $accountService,
								   GroupsIntegration $groupsIntegration,
								   $UserId,
								   LoggerInterface $logger,
								   IL10N $l10n,
								   AliasesService $aliasesService,
								   IMailTransmission $mailTransmission,
								   SetupService $setup,
								   IMailManager $mailManager,
								   SyncService $syncService
	) {
		parent::__construct($appName, $request);
		$this->accountService = $accountService;
		$this->groupsIntegration = $groupsIntegration;
		$this->currentUserId = $UserId;
		$this->logger = $logger;
		$this->l10n = $l10n;
		$this->aliasesService = $aliasesService;
		$this->mailTransmission = $mailTransmission;
		$this->setup = $setup;
		$this->mailManager = $mailManager;
		$this->syncService = $syncService;
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
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	public function update(int $id,
						   bool $autoDetect,
						   string $accountName,
						   string $emailAddress,
						   string $password = null,
						   string $imapHost = null,
						   int $imapPort = null,
						   string $imapSslMode = null,
						   string $imapUser = null,
						   string $imapPassword = null,
						   string $smtpHost = null,
						   int $smtpPort = null,
						   string $smtpSslMode = null,
						   string $smtpUser = null,
						   string $smtpPassword = null): JSONResponse {
		try {
			// Make sure the account actually exists
			$this->accountService->find($this->currentUserId, $id);
		} catch (ClientException $e) {
			return new JSONResponse([], Http::STATUS_BAD_REQUEST);
		}

		$account = null;
		$errorMessage = null;
		try {
			if ($autoDetect) {
				$account = $this->setup->createNewAutoConfiguredAccount($accountName, $emailAddress, $password);
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

		return new JSONResponse($account);
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
			$this->accountService->save($dbAccount)->toJson()
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
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	public function create(string $accountName, string $emailAddress, string $password = null, string $imapHost = null, int $imapPort = null, string $imapSslMode = null, string $imapUser = null, string $imapPassword = null, string $smtpHost = null, int $smtpPort = null, string $smtpSslMode = null, string $smtpUser = null, string $smtpPassword = null, bool $autoDetect = true): JSONResponse {
		try {
			if ($autoDetect) {
				$account = $this->setup->createNewAutoConfiguredAccount($accountName, $emailAddress, $password);
			} else {
				$account = $this->setup->createNewAccount($accountName, $emailAddress, $imapHost, $imapPort, $imapSslMode, $imapUser, $imapPassword, $smtpHost, $smtpPort, $smtpSslMode, $smtpUser, $smtpPassword, $this->currentUserId);
			}
		} catch (CouldNotConnectException $e) {
			$this->logger->info('Creating account failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return \OCA\Mail\Http\JsonResponse::fail([
				'error' => $e->getReason(),
				'service' => $e->getService(),
				'host' => $e->getHost(),
				'port' => $e->getPort(),
			]);
		} catch (ServiceException $e) {
			$this->logger->error('Creating account failed: ' . $e->getMessage(), [
				'exception' => $e,
			]);
			return \OCA\Mail\Http\JsonResponse::error('Could not create account');
		}

		if (is_null($account)) {
			return \OCA\Mail\Http\JsonResponse::fail([
				'error' => 'AUTOCONFIG_FAILED',
				'message' => $this->l10n->t('Auto detect failed. Please use manual mode.'),
			]);
		}

		return \OCA\Mail\Http\JsonResponse::success($account, Http::STATUS_CREATED);
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
	 * @param bool $isHtml
	 * @param bool $requestMdn
	 * @param int|null $draftId
	 * @param int|null $messageId
	 * @param mixed $attachments
	 * @param int|null $aliasId
	 *
	 * @return JSONResponse
	 *
	 * @throws ClientException
	 * @throws ServiceException
	 */
	public function send(int $id,
						 string $subject,
						 string $body,
						 string $to,
						 string $cc,
						 string $bcc,
						 bool $isHtml = true,
						 bool $requestMdn = false,
						 int $draftId = null,
						 int $messageId = null,
						 array $attachments = [],
						 int $aliasId = null,
						 bool $force = false): JSONResponse {
		$account = $this->accountService->find($this->currentUserId, $id);
		$alias = $aliasId ? $this->aliasesService->find($aliasId, $this->currentUserId) : null;

		$expandedTo = $this->groupsIntegration->expand($to);
		$expandedCc = $this->groupsIntegration->expand($cc);
		$expandedBcc = $this->groupsIntegration->expand($bcc);

		$count = substr_count($expandedTo, ',') + substr_count($expandedCc, ',') + 1;
		if (!$force && $count >= 10) {
			throw new ManyRecipientsException();
		}

		$messageData = NewMessageData::fromRequest($account, $expandedTo, $expandedCc, $expandedBcc, $subject, $body, $attachments, $isHtml, $requestMdn);
		$repliedMessageData = null;
		if ($messageId !== null) {
			try {
				$repliedMessage = $this->mailManager->getMessage($this->currentUserId, $messageId);
			} catch (ClientException $e) {
				$this->logger->info("Message in reply " . $messageId . " could not be loaded: " . $e->getMessage());
			}
			$repliedMessageData = new RepliedMessageData($account, $repliedMessage);
		}

		$draft = null;
		if ($draftId !== null) {
			try {
				$draft = $this->mailManager->getMessage($this->currentUserId, $draftId);
			} catch (ClientException $e) {
				$this->logger->info("Draft " . $draftId . " could not be loaded: " . $e->getMessage());
			}
		}
		try {
			$this->mailTransmission->sendMessage($messageData, $repliedMessageData, $alias, $draft);
			return new JSONResponse();
		} catch (ServiceException $ex) {
			$this->logger->error('Sending mail failed: ' . $ex->getMessage());
			throw $ex;
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
		return MailJsonResponse::success($quota);
	}
}
