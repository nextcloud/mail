<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Controller;

use DateTimeImmutable;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Http\JsonResponse;
use OCA\Mail\Http\TrapError;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeState;
use OCA\Mail\Service\OutOfOfficeService;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\User\IAvailabilityCoordinator;
use Psr\Container\ContainerInterface;

#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
class OutOfOfficeController extends Controller {
	public function __construct(
		IRequest $request,
		private ContainerInterface $container,
		private IUserSession $userSession,
		private AccountService $accountService,
		private OutOfOfficeService $outOfOfficeService,
	) {
		parent::__construct(Application::APP_ID, $request);
	}

	/**
	 * @NoAdminRequired
	 * @NoCSRFRequired
	 */
	#[TrapError]
	public function getState(int $accountId): JsonResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return JsonResponse::fail([], Http::STATUS_FORBIDDEN);
		}

		$account = $this->accountService->findById($accountId);
		if ($account->getUserId() !== $user->getUID()) {
			return JsonResponse::fail([], Http::STATUS_NOT_FOUND);
		}

		$state = $this->outOfOfficeService->parseState($account->getMailAccount());
		return JsonResponse::success($state);
	}

	/**
	 * @NoAdminRequired
	 */
	#[TrapError]
	public function followSystem(int $accountId) {
		if (!$this->container->has(IAvailabilityCoordinator::class)) {
			return JsonResponse::fail([], Http::STATUS_NOT_IMPLEMENTED);
		}

		$user = $this->userSession->getUser();
		if ($user === null) {
			return JsonResponse::fail([], Http::STATUS_FORBIDDEN);
		}

		$account = $this->accountService->findById($accountId);
		if ($account->getUserId() !== $user->getUID()) {
			return JsonResponse::fail([], Http::STATUS_NOT_FOUND);
		}

		$mailAccount = $account->getMailAccount();
		if (!$mailAccount->getOutOfOfficeFollowsSystem()) {
			$mailAccount->setOutOfOfficeFollowsSystem(true);
			$this->accountService->update($mailAccount);
		}

		$state = $this->outOfOfficeService->updateFromSystem($mailAccount, $user);
		return JsonResponse::success($state);
	}

	/**
	 * @NoAdminRequired
	 */
	#[TrapError]
	public function update(
		int $accountId,
		bool $enabled,
		?string $start,
		?string $end,
		string $subject,
		string $message,
	): JsonResponse {
		$user = $this->userSession->getUser();
		if ($user === null) {
			return JsonResponse::fail([], Http::STATUS_FORBIDDEN);
		}

		$account = $this->accountService->findById($accountId);
		if ($account->getUserId() !== $user->getUID()) {
			return JsonResponse::fail([], Http::STATUS_NOT_FOUND);
		}

		if ($enabled && $start === null) {
			throw new ServiceException('Missing start date');
		}

		$mailAccount = $account->getMailAccount();
		if ($mailAccount->getOutOfOfficeFollowsSystem()) {
			$mailAccount->setOutOfOfficeFollowsSystem(false);
			$this->accountService->update($mailAccount);
		}

		$state = new OutOfOfficeState(
			$enabled,
			$start ? new DateTimeImmutable($start) : null,
			$end ? new DateTimeImmutable($end) : null,
			$subject,
			$message,
		);
		$this->outOfOfficeService->update($mailAccount, $state);

		$newState = $this->outOfOfficeService->parseState($mailAccount);
		return JsonResponse::success($newState);
	}
}
