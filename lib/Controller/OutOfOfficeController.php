<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use OCP\IUserSession;
use OCP\User\IAvailabilityCoordinator;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;

class OutOfOfficeController extends Controller {
	private ?IAvailabilityCoordinator $availabilityCoordinator;

	public function __construct(
		IRequest $request,
		ContainerInterface $container,
		private IUserSession $userSession,
		private AccountService $accountService,
		private OutOfOfficeService $outOfOfficeService,
		private ITimeFactory $timeFactory,
	) {
		parent::__construct(Application::APP_ID, $request);

		try {
			$this->availabilityCoordinator = $container->get(IAvailabilityCoordinator::class);
		} catch (ContainerExceptionInterface) {
			$this->availabilityCoordinator = null;
		}
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
		if ($this->availabilityCoordinator === null) {
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

		$state = null;
		$now = $this->timeFactory->getTime();
		$currentOutOfOfficeData = $this->availabilityCoordinator->getCurrentOutOfOfficeData($user);
		if ($currentOutOfOfficeData !== null
		 && $currentOutOfOfficeData->getStartDate() <= $now
		 && $currentOutOfOfficeData->getEndDate() > $now) {
			// In the middle of a running absence => enable auto responder
			$state = new OutOfOfficeState(
				true,
				new DateTimeImmutable("@" . $currentOutOfOfficeData->getStartDate()),
				new DateTimeImmutable("@" . $currentOutOfOfficeData->getEndDate()),
				'Re: ${subject}',
				$currentOutOfOfficeData->getMessage(),
			);
			$this->outOfOfficeService->update($mailAccount, $state);
		} else {
			// Absence has not yet started or has already ended => disable auto responder
			$this->outOfOfficeService->disable($mailAccount);
		}

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
			throw new ServiceException("Missing start date");
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
