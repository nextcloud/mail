<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\DelegationService;
use OCA\Mail\Service\DraftsService;
use OCA\Mail\Service\OutboxService;
use OCA\Mail\Service\SmimeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class OutboxController extends Controller {
	private OutboxService $service;
	private string $userId;
	private AccountService $accountService;
	private SmimeService $smimeService;
	private DelegationService $delegationService;

	public function __construct(string $appName,
		$userId,
		IRequest $request,
		OutboxService $service,
		AccountService $accountService,
		SmimeService $smimeService,
		DelegationService $delegationService) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
		$this->service = $service;
		$this->accountService = $accountService;
		$this->smimeService = $smimeService;
		$this->delegationService = $delegationService;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function index(): JsonResponse {
		return JsonResponse::success(['messages' => $this->service->getMessages($this->userId)]);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return JsonResponse
	 */
	#[TrapError]
	public function show(int $id): JsonResponse {
		$effectiveUserId = $this->delegationService->resolveLocalMessageUserId($id, $this->userId);
		$message = $this->service->getMessage($id, $effectiveUserId);
		return JsonResponse::success($message);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $accountId
	 * @param string $subject
	 * @param string $body
	 * @param string $editorBody
	 * @param bool $isHtml
	 * @param array<int, string[]> $to i. e. [['label' => 'Linus', 'email' => 'tent@stardewvalley.com'], ['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']]
	 * @param array<int, string[]> $cc
	 * @param array<int, string[]> $bcc
	 * @param array $attachments
	 * @param int|null $draftId
	 * @param int|null $aliasId
	 * @param string|null $inReplyToMessageId
	 * @param int|null $sendAt
	 *
	 * @return JsonResponse
	 * @throws DoesNotExistException
	 * @throws ClientException
	 */
	#[TrapError]
	public function create(
		int $accountId,
		string $subject,
		?string $bodyPlain,
		?string $bodyHtml,
		?string $editorBody,
		bool $isHtml,
		bool $smimeSign,
		bool $smimeEncrypt,
		array $to = [],
		array $cc = [],
		array $bcc = [],
		array $attachments = [],
		?int $draftId = null,
		?int $aliasId = null,
		?string $inReplyToMessageId = null,
		?int $smimeCertificateId = null,
		?int $sendAt = null,
		bool $requestMdn = false,
		bool $isPgpMime = false,
	): JsonResponse {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($accountId, $this->userId);
		$account = $this->accountService->find($effectiveUserId, $accountId);

		if ($draftId !== null) {
			$this->service->handleDraft($account, $draftId);
		}

		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($accountId);
		$message->setAliasId($aliasId);
		$message->setSubject($subject);
		$message->setBodyPlain($bodyPlain);
		$message->setBodyHtml($bodyHtml);
		$message->setHtml($isHtml);
		$message->setEditorBody($editorBody);
		$message->setInReplyToMessageId($inReplyToMessageId);
		$message->setSendAt($sendAt);
		$message->setPgpMime($isPgpMime);
		$message->setSmimeSign($smimeSign);
		$message->setSmimeEncrypt($smimeEncrypt);
		$message->setRequestMdn($requestMdn);

		if (!empty($smimeCertificateId)) {
			$smimeCertificate = $this->smimeService->findCertificate($smimeCertificateId, $this->userId);
			$message->setSmimeCertificateId($smimeCertificate->getId());
		}

		$this->service->saveMessage($account, $message, $to, $cc, $bcc, $attachments);
		$this->delegationService->logDelegatedAction("$this->userId created an outbox message for account <$accountId> on behalf of $effectiveUserId");

		return JsonResponse::success($message, Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JsonResponse
	 */
	#[TrapError]
	public function createFromDraft(DraftsService $draftsService, int $id, int $sendAt): JsonResponse {
		$effectiveUserId = $this->delegationService->resolveLocalMessageUserId($id, $this->userId);
		$draftMessage = $draftsService->getMessage($id, $effectiveUserId);
		// Locate the account to check authorization
		$this->accountService->find($effectiveUserId, $draftMessage->getAccountId());

		$outboxMessage = $this->service->convertDraft($draftMessage, $sendAt);
		$this->delegationService->logDelegatedAction("$this->userId created an outbox message from draft <$id> on behalf of $effectiveUserId");

		return JsonResponse::success(
			$outboxMessage,
			Http::STATUS_CREATED,
		);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param int $accountId
	 * @param string $subject
	 * @param string $body
	 * @param string $editorBody
	 * @param bool $isHtml
	 * @param bool $failed
	 * @param array $to i. e. [['label' => 'Linus', 'email' => 'tent@stardewvalley.com'], ['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']]
	 * @param array $cc
	 * @param array $bcc
	 * @param array $attachments
	 * @param int|null $aliasId
	 * @param string|null $inReplyToMessageId
	 * @param int|null $sendAt
	 * @return JsonResponse
	 */
	#[TrapError]
	public function update(
		int $id,
		int $accountId,
		string $subject,
		?string $bodyPlain,
		?string $bodyHtml,
		?string $editorBody,
		bool $isHtml,
		bool $smimeSign,
		bool $smimeEncrypt,
		bool $failed = false,
		array $to = [],
		array $cc = [],
		array $bcc = [],
		array $attachments = [],
		?int $aliasId = null,
		?string $inReplyToMessageId = null,
		?int $smimeCertificateId = null,
		?int $sendAt = null,
		bool $requestMdn = false,
		bool $isPgpMime = false,
	): JsonResponse {
		$effectiveUserId = $this->delegationService->resolveAccountUserId($accountId, $this->userId);
		$message = $this->service->getMessage($id, $effectiveUserId);
		if ($message->getStatus() === LocalMessage::STATUS_PROCESSED) {
			return JsonResponse::error('Cannot modify already sent message', Http::STATUS_FORBIDDEN, [$message]);
		}
		$account = $this->accountService->find($effectiveUserId, $accountId);

		$message->setAccountId($accountId);
		$message->setAliasId($aliasId);
		$message->setSubject($subject);
		$message->setBodyPlain($bodyPlain);
		$message->setBodyHtml($bodyHtml);
		$message->setHtml($isHtml);
		$message->setEditorBody($editorBody);
		$message->setInReplyToMessageId($inReplyToMessageId);
		$message->setSendAt($sendAt);
		$message->setPgpMime($isPgpMime);
		$message->setSmimeSign($smimeSign);
		$message->setSmimeEncrypt($smimeEncrypt);
		$message->setRequestMdn($requestMdn);

		if (!empty($smimeCertificateId)) {
			$smimeCertificate = $this->smimeService->findCertificate($smimeCertificateId, $this->userId);
			$message->setSmimeCertificateId($smimeCertificate->getId());
		}

		$message = $this->service->updateMessage($account, $message, $to, $cc, $bcc, $attachments);
		$this->delegationService->logDelegatedAction("$this->userId updated outbox message <$id> for account <$accountId> on behalf of $effectiveUserId");

		return JsonResponse::success($message, Http::STATUS_ACCEPTED);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return JsonResponse
	 */
	#[TrapError]
	public function send(int $id): JsonResponse {
		$effectiveUserId = $this->delegationService->resolveLocalMessageUserId($id, $this->userId);
		$message = $this->service->getMessage($id, $effectiveUserId);
		$account = $this->accountService->find($effectiveUserId, $message->getAccountId());

		$message = $this->service->sendMessage($message, $account);
		$status = $message->getStatus();
		$this->delegationService->logDelegatedAction(match ($status) {
			LocalMessage::STATUS_PROCESSED => "$this->userId sent outbox message <$id> on behalf of $effectiveUserId",
			default => "$this->userId attempted sending outbox message <$id> on behalf of $effectiveUserId but sending failed",
		});

		if ($status !== LocalMessage::STATUS_PROCESSED) {
			return JsonResponse::error('Could not send message', Http::STATUS_INTERNAL_SERVER_ERROR, [$message]);
		}
		return JsonResponse::success(
			'Message sent', Http::STATUS_ACCEPTED
		);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return JsonResponse
	 */
	#[TrapError]
	public function destroy(int $id): JsonResponse {
		$effectiveUserId = $this->delegationService->resolveLocalMessageUserId($id, $this->userId);
		$message = $this->service->getMessage($id, $effectiveUserId);
		$this->service->deleteMessage($effectiveUserId, $message);
		$this->delegationService->logDelegatedAction("$this->userId deleted outbox message <$id> on behalf of $effectiveUserId");
		return JsonResponse::success('Message deleted', Http::STATUS_ACCEPTED);
	}
}
