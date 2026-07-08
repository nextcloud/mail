<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Exception\ClientException;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\MessageTemplateService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class MessageTemplateController extends Controller {
	private ?string $currentUserId;

	public function __construct(
		string $appName,
		IRequest $request,
		private MessageTemplateService $messageTemplateService,
		?string $userId,
	) {
		parent::__construct($appName, $request);
		$this->currentUserId = $userId;
	}

	/**
	 * @NoAdminRequired
	 */
	#[TrapError]
	public function index(): JSONResponse {
		$this->checkUser();
		$messageTemplates = $this->messageTemplateService->findMessageTemplates($this->currentUserId);
		return new JSONResponse($messageTemplates, Http::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 * @throws ClientException
	 */
	#[TrapError]
	public function create(string $title, string $body): JSONResponse {
		$this->checkUser();
		$messageTemplate = $this->messageTemplateService->createMessageTemplate($this->currentUserId, $title, $body);
		return new JSONResponse($messageTemplate, Http::STATUS_CREATED);
	}

	/**
	 * @NoAdminRequired
	 * @throws ClientException
	 */
	#[TrapError]
	public function update(int $id, string $title, string $body): JSONResponse {
		$this->checkUser();

		try {
			$messageTemplate = $this->messageTemplateService->updateMessageTemplate($this->currentUserId, $id, $title, $body);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['message' => 'Message template not found'], Http::STATUS_NOT_FOUND);
		}

		return new JSONResponse($messageTemplate, Http::STATUS_OK);
	}

	/**
	 * @NoAdminRequired
	 */
	#[TrapError]
	public function destroy(int $id): JSONResponse {
		$this->checkUser();

		try {
			$this->messageTemplateService->deleteMessageTemplate($this->currentUserId, $id);
			return new JSONResponse([], Http::STATUS_OK);
		} catch (DoesNotExistException $e) {
			return new JSONResponse(['message' => 'Message template not found'], Http::STATUS_NOT_FOUND);
		}
	}

	private function checkUser(): void {
		if ($this->currentUserId === null) {
			throw new ClientException('No user specified');
		}
	}
}
