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
	protected const OPTION_DEBuG_ON = 'on';
	protected const OPTION_DEBuG_OFF = 'off';

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
		$this->addOption(self::OPTION_DEBuG_ON, null, InputOption::VALUE_NONE);
		$this->addOption(self::OPTION_DEBuG_OFF, null, InputOption::VALUE_NONE);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);
		$debugOn = $input->getOption(self::OPTION_DEBuG_ON);
		$debugOff = $input->getOption(self::OPTION_DEBuG_OFF);
		$debug = false;

		try {
			$account = $this->accountService->findById($accountId)->getMailAccount();
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>Account $accountId does not exist</error>");
			return 1;
		}

		if ($debugOn && $debugOff) {
			$output->writeln('<error>Cannot use both --on and --off at the same time</error>');
			return 1;
		}

		if ($debugOn) {
			$debug = true;
		}

		$account->setDebug($debug);
		$this->accountService->save($account);

		return 0;
	}
}
