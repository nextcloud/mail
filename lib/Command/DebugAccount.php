<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class DebugAccount extends Command {
	protected const ARGUMENT_ACCOUNT_ID = 'account-id';
	protected const OPTION_IMAP_DEFAULT = 'imap';
	protected const OPTION_IMAP_FULL = 'imap-full';
	protected const OPTION_SMTP_DEFAULT = 'smtp';

	public function __construct(
		private AccountService $accountService,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:account:debug');
		$this->setDescription('Enable or Disable IMAP/SMTP debugging on a account');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
		$this->addOption(self::OPTION_IMAP_DEFAULT, null, InputOption::VALUE_NONE);
		$this->addOption(self::OPTION_IMAP_FULL, null, InputOption::VALUE_NONE);
		$this->addOption(self::OPTION_SMTP_DEFAULT, null, InputOption::VALUE_NONE);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);
		$imapDefault = $input->getOption(self::OPTION_IMAP_DEFAULT);
		$imapFull = $input->getOption(self::OPTION_IMAP_FULL);
		$smtpDefault = $input->getOption(self::OPTION_SMTP_DEFAULT);
		$debug = [];

		try {
			$account = $this->accountService->findById($accountId)->getMailAccount();
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>Account $accountId does not exist</error>");
			return 1;
		}

		if ($imapDefault) {
			$debug[] = 'imap';
		} elseif ($imapFull) {
			$debug[] = 'imap-full';
		}

		if ($smtpDefault) {
			$debug[] = 'smtp';
		}

		$account->setDebug(implode('|', $debug));
		$this->accountService->save($account);

		return 0;
	}
}
