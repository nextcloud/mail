<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration\Service;

use OCA\Mail\Service\AccountService;
use OCP\IL10N;
use OCP\IUser;
use OCP\Security\ICrypto;
use Symfony\Component\Console\Output\OutputInterface;

class AccountMigrationService {
	public function __construct(
		private readonly IL10N $l10n,
		private readonly ICrypto $crypto,
		private readonly AccountService $accountService,
	) {
	}

	/**
	 * Schedule background jobs for the added accounts.
	 * Necessary to do after all data is being imported as we
	 * could run into race conditions when doing directly after
	 * saving each mail account into database.
	 *
	 * @param IUser $user
	 * @param OutputInterface $output
	 * @return void
	 */
	public function scheduleBackgroundJobs(IUser $user, OutputInterface $output): void {
		$accounts = $this->accountService->findByUserId($user->getUID());
		$accountCount = count($accounts);

		$output->writeln(
			$this->l10n->t('Scheduling background jobs for %d mail account(s)', [ $accountCount ]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		foreach ($accounts as $account) {
			$mailAccount = $account->getMailAccount();
			$mailAccountId = $mailAccount->getId();
			$this->accountService->scheduleBackgroundJobs($mailAccountId);
			$output->writeln(
				$this->l10n->t('Scheduled background jobs for mail account %d', [ $mailAccountId ]),
				OutputInterface::VERBOSITY_VERBOSE
			);
		}
	}
}
