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
use OCA\Mail\Service\DraftsService;
use OCA\Mail\Service\SmimeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class DraftsController extends Controller {
	private DraftsService $service;
	private string $userId;
	private AccountService $accountService;
	private ITimeFactory $timeFactory;
	private SmimeService $smimeService;


	public function __construct(string $appName,
		$UserId,
		IRequest $request,
		DraftsService $service,
		AccountService $accountService,
		ITimeFactory $timeFactory,
		SmimeService $smimeService) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
		$this->service = $service;
		$this->accountService = $accountService;
		$this->timeFactory = $timeFactory;
		$this->smimeService = $smimeService;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $accountId
	 * @param string $subject
	 * @param string $body
	 * @param string $editorBody
	 * @param bool $isHtml
	 * @param bool $isPgpMime
	 * @param bool $smimeSign
	 * @param bool $smimeEncrypt
	 * @param array<int, string[]> $to i. e. [['label' => 'Linus', 'email' => 'tent@stardewvalley.com'], ['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']]
	 * @param array<int, string[]> $cc
	 * @param array<int, string[]> $bcc
	 * @param array $attachments
	 * @param int|null $aliasId
	 * @param string|null $inReplyToMessageId
	 * @param int|null $smimeCertificateId
	 * @param int|null $sendAt
	 * @param int|null $draftId
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
		?bool $smimeSign,
		?bool $smimeEncrypt,
		array $to = [],
		array $cc = [],
		array $bcc = [],
		array $attachments = [],
		?int $aliasId = null,
		?string $inReplyToMessageId = null,
		?int $smimeCertificateId = null,
		?int $sendAt = null,
		?int $draftId = null,
		bool $requestMdn = false,
		bool $isPgpMime = false) : JsonResponse {
		$account = $this->accountService->find($this->userId, $accountId);
		if ($draftId !== null) {
			$this->service->handleDraft($account, $draftId);
		}
		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setAccountId($accountId);
		$message->setAliasId($aliasId);
		$message->setSubject($subject);
		$message->setBodyPlain($bodyPlain);
		$message->setBodyHtml($bodyHtml);
		$message->setHtml($isHtml);
		$message->setEditorBody($editorBody);
		$message->setInReplyToMessageId($inReplyToMessageId);
		$message->setUpdatedAt($this->timeFactory->getTime());
		$message->setSendAt($sendAt);
		$message->setSmimeSign($smimeSign);
		$message->setSmimeEncrypt($smimeEncrypt);
		$message->setRequestMdn($requestMdn);
		$message->setPgpMime($isPgpMime);

		if (!empty($smimeCertificateId)) {
			$smimeCertificate = $this->smimeService->findCertificate($smimeCertificateId, $this->userId);
			$message->setSmimeCertificateId($smimeCertificate->getId());
		}

		$this->service->saveMessage($account, $message, $to, $cc, $bcc, $attachments);

		return JsonResponse::success($message, Http::STATUS_CREATED);
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
	 * @param bool $isPgpMime
	 * @param bool $failed
	 * @param array<int, string[]> $to i. e. [['label' => 'Linus', 'email' => 'tent@stardewvalley.com'], ['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']]
	 * @param array<int, string[]> $cc
	 * @param array<int, string[]> $bcc
	 * @param array $attachments
	 * @param int|null $aliasId
	 * @param string|null $inReplyToMessageId
	 * @param int|null $sendAt
	 * @return JsonResponse
	 */
	#[TrapError]
	public function update(int $id,
		int $accountId,
		string $subject,
		?string $bodyPlain,
		?string $bodyHtml,
		?string $editorBody,
		bool $isHtml,
		?bool $smimeSign,
		?bool $smimeEncrypt,
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
		bool $isPgpMime = false): JsonResponse {
		$message = $this->service->getMessage($id, $this->userId);
		$account = $this->accountService->find($this->userId, $accountId);

		$message->setType(LocalMessage::TYPE_DRAFT);
		$message->setAccountId($accountId);
		$message->setAliasId($aliasId);
		$message->setSubject($subject);
		$message->setBodyPlain($bodyPlain);
		$message->setBodyHtml($bodyHtml);
		$message->setHtml($isHtml);
		$message->setEditorBody($editorBody);
		$message->setFailed($failed);
		$message->setInReplyToMessageId($inReplyToMessageId);
		$message->setSendAt($sendAt);
		$message->setUpdatedAt($this->timeFactory->getTime());
		$message->setSmimeSign($smimeSign);
		$message->setSmimeEncrypt($smimeEncrypt);
		$message->setRequestMdn($requestMdn);
		$message->setPgpMime($isPgpMime);

		if (!empty($smimeCertificateId)) {
			$smimeCertificate = $this->smimeService->findCertificate($smimeCertificateId, $this->userId);
			$message->setSmimeCertificateId($smimeCertificate->getId());
		}

		$message = $this->service->updateMessage($account, $message, $to, $cc, $bcc, $attachments);
		return JsonResponse::success($message, Http::STATUS_ACCEPTED);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return JsonResponse
	 */
	#[TrapError]
	public function destroy(int $id): JsonResponse {
		$message = $this->service->getMessage($id, $this->userId);
		$this->accountService->find($this->userId, $message->getAccountId());

		$this->service->deleteMessage($this->userId, $message);
		return JsonResponse::success('Message deleted', Http::STATUS_ACCEPTED);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @return JsonResponse
	 */
	#[TrapError]
	public function move(int $id): JsonResponse {
		$message = $this->service->getMessage($id, $this->userId);
		$account = $this->accountService->find($this->userId, $message->getAccountId());

		$this->service->sendMessage($message, $account);
		return  JsonResponse::success(
			'Message moved to IMAP', Http::STATUS_ACCEPTED
		);
	}
}
