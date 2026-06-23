<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use OCA\Mail\Contracts\IAttachmentService;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCA\Mail\Service\DelegationService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class LocalAttachmentsController extends Controller {
	private IAttachmentService $attachmentService;
	private DelegationService $delegationService;
	private string $userId;

	/**
	 * @param string $appName
	 * @param IRequest $request
	 * @param IAttachmentService $attachmentService
	 * @param DelegationService $delegationService
	 * @param string $UserId
	 */
	public function __construct(string $appName, IRequest $request,
		IAttachmentService $attachmentService, DelegationService $delegationService, $userId) {
		parent::__construct($appName, $request);
		$this->attachmentService = $attachmentService;
		$this->delegationService = $delegationService;
		$this->userId = $userId;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @return JSONResponse
	 */
	#[TrapError]
	public function create(?int $accountId = null): JSONResponse {
		$file = $this->request->getUploadedFile('attachment');

		if (is_null($file)) {
			throw new ClientException('no file attached');
		}

		// Store the attachment under the account owner so it can be linked and
		// sent when composing on behalf of a delegated account.
		$userId = $accountId !== null
			? $this->delegationService->resolveAccountUserId($accountId, $this->userId)
			: $this->userId;

		$uploadedFile = new UploadedFile($file);
		$attachment = $this->attachmentService->addFile($userId, $uploadedFile);

		return new JSONResponse($attachment, Http::STATUS_CREATED);
	}
}
