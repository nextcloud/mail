<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\MailFilterService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\Route;
use OCP\AppFramework\Http\JSONResponse;
use OCP\AppFramework\OCSController;
use OCP\IRequest;

class MailfilterController extends OCSController {
	private string $currentUserId;

	public function __construct(IRequest $request,
		string $userId,
		private MailFilterService $mailFilterService,
		private AccountService $accountService,
	) {
		parent::__construct(Application::APP_ID, $request);
		$this->currentUserId = $userId;
	}

	#[Route(Route::TYPE_FRONTPAGE, verb: 'GET', url: '/api/mailfilter/{accountId}', requirements: ['accountId' => '[\d]+'])]
	public function getFilters(int $accountId) {
		$account = $this->accountService->findById($accountId);

		if ($account->getUserId() !== $this->currentUserId) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		$result = $this->mailFilterService->parse($account->getMailAccount());

		return new JSONResponse($result->getFilters());
	}

	#[Route(Route::TYPE_FRONTPAGE, verb: 'PUT', url: '/api/mailfilter/{accountId}', requirements: ['accountId' => '[\d]+'])]
	public function updateFilters(int $accountId, array $filters) {
		$account = $this->accountService->findById($accountId);

		if ($account->getUserId() !== $this->currentUserId) {
			return new JSONResponse([], Http::STATUS_NOT_FOUND);
		}

		$this->mailFilterService->update($account->getMailAccount(), $filters);

		return new JSONResponse([]);
	}
}
