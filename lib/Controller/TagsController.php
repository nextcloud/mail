<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class TagsController extends Controller {
	private string $currentUserId;
	private IMailManager $mailManager;

	private AccountService $accountService;


	public function __construct(IRequest $request,
		string $UserId,
		IMailManager $mailManager,
		AccountService $accountService,
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->currentUserId = $UserId;
		$this->mailManager = $mailManager;
		$this->accountService = $accountService;
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param string $displayName
	 * @param string $color
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	#[TrapError]
	public function create(string $displayName, string $color): JSONResponse {
		$this->validateDisplayName($displayName);
		$this->validateColor($color);

		$tag = $this->mailManager->createTag($displayName, $color, $this->currentUserId);
		return new JSONResponse($tag);
	}

	/**
	 * @NoAdminRequired
	 *
	 * @param int $id
	 * @param string $displayName
	 * @param string $color
	 *
	 * @return JSONResponse
	 * @throws ClientException
	 */
	#[TrapError]
	public function update(int $id, string $displayName, string $color): JSONResponse {
		$this->validateDisplayName($displayName);
		$this->validateColor($color);

		$tag = $this->mailManager->updateTag($id, $displayName, $color, $this->currentUserId);
		return new JSONResponse($tag);
	}
	/**
	 * @NoAdminRequired
	 *
	 * @throws ClientException
	 */
	#[TrapError]
	public function delete(int $id, int $accountId): JSONResponse {
		try {
			$accounts = $this->accountService->findByUserId($this->currentUserId);
		} catch (DoesNotExistException $e) {
			return new JSONResponse([], Http::STATUS_FORBIDDEN);
		}
		$this->mailManager->deleteTag($id, $this->currentUserId, $accounts);

		return new JSONResponse([$id]);
	}

	/**
	 * @throws ClientException
	 */
	private function validateDisplayName(string $displayName): void {
		if (mb_strlen($displayName) > 128) {
			throw new ClientException('The maximum length for displayName is 128');
		}
	}

	/**
	 * @throws ClientException
	 */
	private function validateColor(string $color): void {
		if (mb_strlen($color) > 9) {
			throw new ClientException('The maximum length for color is 9');
		}
	}
}
