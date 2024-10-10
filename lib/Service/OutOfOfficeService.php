<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use DateTimeImmutable;
use Horde\ManageSieve\Exception as ManageSieveException;
use InvalidArgumentException;
use JsonException;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\CouldNotConnectException;
use OCA\Mail\Exception\OutOfOfficeParserException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeParser;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeParserResult;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeState;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IUser;
use OCP\User\IAvailabilityCoordinator;
use Psr\Log\LoggerInterface;

class OutOfOfficeService {

	public function __construct(
		private OutOfOfficeParser $outOfOfficeParser,
		private SieveService $sieveService,
		private LoggerInterface $logger,
		private ITimeFactory $timeFactory,
		private AllowedRecipientsService $allowedRecipientsService,
		private IAvailabilityCoordinator $availabilityCoordinator,
	) {
	}

	/**
	 * @throws ClientException
	 * @throws OutOfOfficeParserException
	 * @throws ManageSieveException
	 * @throws CouldNotConnectException
	 */
	public function parseState(MailAccount $account): OutOfOfficeParserResult {
		$script = $this->sieveService->getActiveScript($account->getUserId(), $account->getId());
		return $this->outOfOfficeParser->parseOutOfOfficeState($script->getScript());
	}

	/**
	 * @throws CouldNotConnectException
	 * @throws JsonException
	 * @throws ClientException
	 * @throws OutOfOfficeParserException
	 * @throws ManageSieveException
	 */
	public function update(MailAccount $account, OutOfOfficeState $state): void {
		$script = $this->sieveService->getActiveScript($account->getUserId(), $account->getId());
		$oldState = $this->outOfOfficeParser->parseOutOfOfficeState($script->getScript());
		$newScript = $this->outOfOfficeParser->buildSieveScript(
			$state,
			$oldState->getUntouchedSieveScript(),
			$this->allowedRecipientsService->get($account),
		);
		try {
			$this->sieveService->updateActiveScript($account->getUserId(), $account->getId(), $newScript);
		} catch (ManageSieveException $e) {
			$this->logger->error('Failed to save sieve script: ' . $e->getMessage(), [
				'exception' => $e,
				'script' => $newScript,
			]);
			throw $e;
		}
	}

	/**
	 * Update a mail account's autoresponder from the out-of-office system setting of the corresponding user.
	 * Note: This method throws if the given account doesn't follow system out-of-office settings.
	 *
	 * @param MailAccount $mailAccount The mail account to update
	 * @param IUser $user The user the mail account belongs to
	 *
	 * @return OutOfOfficeState|null The new out-of-office state which has been applied
	 * @throws ClientException
	 * @throws CouldNotConnectException
	 * @throws JsonException
	 * @throws ManageSieveException
	 * @throws OutOfOfficeParserException
	 * @throws ServiceException
	 * @throws InvalidArgumentException If the given mail account doesn't follow out-of-office settings
	 */
	public function updateFromSystem(MailAccount $mailAccount, IUser $user): ?OutOfOfficeState {
		if (!$mailAccount->getOutOfOfficeFollowsSystem()) {
			throw new InvalidArgumentException('The mail account does not follow system out-of-office settings');
		}

		$userId = $user->getUID();
		if ($mailAccount->getUserId() !== $userId) {
			$accountId = $mailAccount->getId();
			throw new ServiceException("Account $accountId doesn't belong to user $userId");
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
				new DateTimeImmutable('@' . $currentOutOfOfficeData->getStartDate()),
				new DateTimeImmutable('@' . $currentOutOfOfficeData->getEndDate()),
				'Re: ${subject}',
				$currentOutOfOfficeData->getMessage(),
			);
			$this->update($mailAccount, $state);
		} else {
			// Absence has not yet started or has already ended => disable auto responder
			$this->disable($mailAccount);
		}

		return $state;
	}

	/**
	 * @throws ClientException
	 * @throws OutOfOfficeParserException
	 * @throws ManageSieveException
	 * @throws CouldNotConnectException
	 * @throws JsonException
	 */
	public function disable(MailAccount $account): void {
		$state = $this->parseState($account)->getState();
		if ($state === null || !$state->isEnabled()) {
			return;
		}

		$state->setEnabled(false);
		$this->update($account, $state);
	}
}
