<?php

declare(strict_types=1);

/**
 * Mail App
 *
 * @copyright 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
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
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\DraftsService;
use OCA\Mail\Service\OutboxService;
use OCA\Mail\Service\SmimeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\IRequest;

class OutboxController extends Controller {
	private OutboxService $service;
	private string $userId;
	private AccountService $accountService;
	private SmimeService $smimeService;

	public function __construct(string $appName,
		$UserId,
		IRequest $request,
		OutboxService $service,
		AccountService $accountService,
		SmimeService $smimeService) {
		parent::__construct($appName, $request);
		$this->userId = $UserId;
		$this->service = $service;
		$this->accountService = $accountService;
		$this->smimeService = $smimeService;
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
		$message = $this->service->getMessage($id, $this->userId);
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
		string $body,
		string $editorBody,
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
		?int $sendAt = null
	): JsonResponse {
		$account = $this->accountService->find($this->userId, $accountId);

		if ($draftId !== null) {
			$this->service->handleDraft($account, $draftId);
		}

		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($accountId);
		$message->setAliasId($aliasId);
		$message->setSubject($subject);
		$message->setBody($body);
		$message->setEditorBody($editorBody);
		$message->setHtml($isHtml);
		$message->setInReplyToMessageId($inReplyToMessageId);
		$message->setSendAt($sendAt);
		$message->setSmimeSign($smimeSign);
		$message->setSmimeEncrypt($smimeEncrypt);

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
	 * @return JsonResponse
	 */
	#[TrapError]
	public function createFromDraft(DraftsService $draftsService, int $id, int $sendAt = null): JsonResponse {
		$draftMessage = $draftsService->getMessage($id, $this->userId);
		// Locate the account to check authorization
		$this->accountService->find($this->userId, $draftMessage->getAccountId());

		$outboxMessage = $this->service->convertDraft($draftMessage, $sendAt);

		return  JsonResponse::success(
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
		string $body,
		string $editorBody,
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
		?int $sendAt = null
	): JsonResponse {
		$message = $this->service->getMessage($id, $this->userId);
		$account = $this->accountService->find($this->userId, $accountId);

		$message->setAccountId($accountId);
		$message->setAliasId($aliasId);
		$message->setSubject($subject);
		$message->setBody($body);
		$message->setEditorBody($editorBody);
		$message->setHtml($isHtml);
		$message->setFailed($failed);
		$message->setInReplyToMessageId($inReplyToMessageId);
		$message->setSendAt($sendAt);
		$message->setSmimeSign($smimeSign);
		$message->setSmimeEncrypt($smimeEncrypt);

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
	public function send(int $id): JsonResponse {
		$message = $this->service->getMessage($id, $this->userId);
		$account = $this->accountService->find($this->userId, $message->getAccountId());

		$this->service->sendMessage($message, $account);
		return  JsonResponse::success(
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
		$message = $this->service->getMessage($id, $this->userId);
		$this->service->deleteMessage($this->userId, $message);
		return JsonResponse::success('Message deleted', Http::STATUS_ACCEPTED);
	}
}
