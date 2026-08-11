<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Service\OutOfOfficeService;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class UpdateSystemAutoresponders extends Command {
	public function __construct(
		private MailAccountMapper $mailAccountMapper,
		private IUserManager $userManager,
		private OutOfOfficeService $outOfOfficeService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:repair:system-autoresponders');
		$this->setDescription('Update sieve scripts of all accounts that follow the system out-of-office period');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		foreach ($this->mailAccountMapper->findAllWhereOooFollowsSystem() as $mailAccount) {
			$accountId = $mailAccount->getId();
			$userId = $mailAccount->getUserId();
			$output->writeln("<info>Updating account $accountId of user $userId</info>");

			$userId = $mailAccount->getUserId();
			$user = $this->userManager->get($userId);
			if ($user === null) {
				$output->writeln("<comment>User $userId does not exist. Skipping ...</comment>");
				continue;
			}

			$state = $this->outOfOfficeService->updateFromSystem($mailAccount, $user);
			if ($state === null) {
				$output->writeln(
					"Disabled autoresponder of account $accountId",
					OutputInterface::VERBOSITY_VERBOSE,
				);
			} else {
				$output->writeln(
					"Updated autoresponder of account $accountId",
					OutputInterface::VERBOSITY_VERBOSE,
				);
			}
		}

		return 0;
	}
}
