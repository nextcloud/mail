<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration\Service;

use OCA\Mail\Exception\ClientException;
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
	 * Delete all mail accounts for the given user.
	 *
	 * @param IUser $user
	 * @param OutputInterface $output
	 * @return void
	 * @throws ClientException
	 */
	public function deleteAllAccounts(IUser $user, OutputInterface $output): void {
		$allAccounts = $this->accountService->findByUserId($user->getUID());
		$accountCount = count($allAccounts);
		$uid = $user->getUID();

		$output->writeln($this->l10n->t("Deleting {$accountCount} mail account(s) for user {$uid}"), OutputInterface::VERBOSITY_VERBOSE);

		foreach ($allAccounts as $account) {
			$accountId = $account->getId();

			if ($account->getMailAccount()->getProvisioningId() !== null) {
				$output->writeln($this->l10n->t("Skipping deletion of provisioned account {$account->getId()}"), OutputInterface::VERBOSITY_VERBOSE);
				continue;
			}

			$this->accountService->deleteByAccountId($accountId);
			$output->writeln($this->l10n->t("Deleted mail account {$accountId}"), OutputInterface::VERBOSITY_VERBOSE);
		}
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

		$output->writeln($this->l10n->t("Scheduling background jobs for {$accountCount} mail account(s)"), OutputInterface::VERBOSITY_VERBOSE);

		foreach ($accounts as $account) {
			$mailAccount = $account->getMailAccount();
			$mailAccountId = $mailAccount->getId();
			$this->accountService->scheduleBackgroundJobs($mailAccountId);
			$output->writeln($this->l10n->t("Scheduled background jobs for mail account {$mailAccountId}"), OutputInterface::VERBOSITY_VERY_VERBOSE);
		}
	}
}
