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
	private readonly ITimeFactory $timeFactory;


	public function __construct(
		string $appName,
		private readonly string $userId,
		IRequest $request,
		private readonly DraftsService $service,
		private readonly AccountService $accountService,
		ITimeFactory $timeFactory,
		private readonly SmimeService $smimeService
	) {
		parent::__construct($appName, $request);
		$this->timeFactory = $timeFactory;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $body
	 * @param string $editorBody
	 * @param bool $smimeSign
	 * @param bool $smimeEncrypt
	 * @param array<int, string[]> $to i. e. [['label' => 'Linus', 'email' => 'tent@stardewvalley.com'], ['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']]
	 * @param array<int, string[]> $cc
	 * @param array<int, string[]> $bcc
	 *
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
	 * @param string $body
	 * @param string $editorBody
	 * @param array<int, string[]> $to i. e. [['label' => 'Linus', 'email' => 'tent@stardewvalley.com'], ['label' => 'Pierre', 'email' => 'generalstore@stardewvalley.com']]
	 * @param array<int, string[]> $cc
	 * @param array<int, string[]> $bcc
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
