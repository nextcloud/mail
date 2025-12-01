<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Service\AccountService;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListAccounts extends Command {
	public const ARGUMENT_USER_ID = 'user';
	public const OPTION_FULL = 'full';
	public const OPTION_ALL = 'all';

	public function __construct(
		private IUserManager $userManager,
		private AccountService $accountService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('mail:account:list')
			->setDescription('List mail accounts')
			->addArgument(
				self::ARGUMENT_USER_ID,
				InputArgument::OPTIONAL,
				'User ID to list accounts for'
			)
			->addOption(
				self::OPTION_FULL,
				'f',
				InputOption::VALUE_NONE,
				'Show full account details including server configuration'
			)
			->addOption(
				self::OPTION_ALL,
				'a',
				InputOption::VALUE_NONE,
				'List accounts for all users'
			);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument(self::ARGUMENT_USER_ID);
		$showFull = $input->getOption(self::OPTION_FULL);
		$listAll = $input->getOption(self::OPTION_ALL);

		if ($listAll) {
			return $this->listAllAccounts($output, $showFull);
		}

		if ($userId === null) {
			$output->writeln('<error>Please provide a user-id or use --all to list all accounts</error>');
			return self::FAILURE;
		}

		if (!$this->userManager->userExists($userId)) {
			$output->writeln("<error>User <$userId> does not exist</error>");
			return self::FAILURE;
		}

		return $this->listUserAccounts($output, $userId, $showFull);
	}

	private function listUserAccounts(OutputInterface $output, string $userId, bool $showFull): int {
		$accounts = $this->accountService->findByUserId($userId);

		if (count($accounts) === 0) {
			$output->writeln("<info>User <$userId> has no mail accounts</info>");
			return self::SUCCESS;
		}

		$mailAccounts = array_map(fn ($account) => $account->getMailAccount(), $accounts);

		$output->writeln("<info>Mail accounts for user <$userId>:</info>");
		$this->renderAccountsTable($output, $mailAccounts, $showFull, false);

		return self::SUCCESS;
	}

	private function listAllAccounts(OutputInterface $output, bool $showFull): int {
		$mailAccounts = $this->accountService->getAllAcounts();

		if (count($mailAccounts) === 0) {
			$output->writeln('<info>No mail accounts found</info>');
			return self::SUCCESS;
		}

		$output->writeln('<info>All mail accounts:</info>');
		$this->renderAccountsTable($output, $mailAccounts, $showFull, true);

		return self::SUCCESS;
	}

	/**
	 * @param array<MailAccount> $mailAccounts
	 */
	private function renderAccountsTable(OutputInterface $output, array $mailAccounts, bool $showFull, bool $showUserId): void {
		$table = new Table($output);

		if ($showFull) {
			$headers = ['ID'];
			if ($showUserId) {
				$headers[] = 'User ID';
			}
			$headers = array_merge($headers, [
				'Email',
				'Name',
				'IMAP Host',
				'IMAP Port',
				'IMAP SSL',
				'SMTP Host',
				'SMTP Port',
				'SMTP SSL',
				'Provisioned',
				'Debug',
			]);
			$table->setHeaders($headers);

			foreach ($mailAccounts as $mailAccount) {
				$row = [$mailAccount->getId()];
				if ($showUserId) {
					$row[] = $mailAccount->getUserId();
				}
				$row = array_merge($row, [
					$mailAccount->getEmail(),
					$mailAccount->getName(),
					$mailAccount->getInboundHost(),
					$mailAccount->getInboundPort(),
					$mailAccount->getInboundSslMode(),
					$mailAccount->getOutboundHost(),
					$mailAccount->getOutboundPort(),
					$mailAccount->getOutboundSslMode(),
					$mailAccount->getProvisioningId() !== null ? 'Yes' : 'No',
					$mailAccount->getDebug() ? 'Yes' : 'No',
				]);
				$table->addRow($row);
			}
		} else {
			$headers = ['ID'];
			if ($showUserId) {
				$headers[] = 'User ID';
			}
			$headers = array_merge($headers, ['Email', 'Name', 'Provisioned', 'Debug']);
			$table->setHeaders($headers);

			foreach ($mailAccounts as $mailAccount) {
				$row = [$mailAccount->getId()];
				if ($showUserId) {
					$row[] = $mailAccount->getUserId();
				}
				$row = array_merge($row, [
					$mailAccount->getEmail(),
					$mailAccount->getName(),
					$mailAccount->getProvisioningId() !== null ? 'Yes' : 'No',
					$mailAccount->getDebug() ? 'Yes' : 'No',
				]);
				$table->addRow($row);
			}
		}

		$table->render();
	}
}
