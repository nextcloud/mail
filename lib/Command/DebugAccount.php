<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Service\AccountService;
use OCA\Mail\Support\DebugLogPathFactory;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class DebugAccount extends Command {
	protected const ARGUMENT_ACCOUNT_ID = 'account-id';
	protected const OPTION_DEBUG_ON = 'on';
	protected const OPTION_DEBUG_OFF = 'off';

	public function __construct(
		private AccountService $accountService,
		private LoggerInterface $logger,
		private DebugLogPathFactory $debugLogPathFactory,
	) {
		parent::__construct();
	}

	/**
	 * @return void
	 */
	protected function configure(): void {
		$this->setName('mail:account:debug');
		$this->setDescription('Enable or Disable IMAP/SMTP debugging for an account');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
		$this->addOption(self::OPTION_DEBUG_ON, null, InputOption::VALUE_NONE);
		$this->addOption(self::OPTION_DEBUG_OFF, null, InputOption::VALUE_NONE);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);
		$debugOn = (bool)$input->getOption(self::OPTION_DEBUG_ON);
		$debugOff = (bool)$input->getOption(self::OPTION_DEBUG_OFF);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>Account $accountId does not exist</error>");
			return 1;
		}

		if ($debugOn && $debugOff) {
			$output->writeln('<error>Cannot use both --on and --off at the same time</error>');
			return 1;
		}

		$mailAccount = $account->getMailAccount();
		if ($debugOn || $debugOff) {
			$mailAccount->setDebug($debugOn);
			$this->accountService->save($mailAccount);
		}

		$active = $this->debugLogPathFactory->isActive($account);
		if ($mailAccount->getDebug()) {
			$output->writeln("<info>Debug mode enabled for account $accountId</info>");
		} else {
			$output->writeln("<info>Debug mode disabled for account $accountId</info>");
			if ($active) {
				$output->writeln('<comment>Note: the system-wide "app.mail.debug" config value is enabled, so debug logging for this account is still active.</comment>');
			}
		}

		$logFiles = [
			'IMAP' => $this->debugLogPathFactory->buildPath($account, DebugLogPathFactory::PROTOCOL_IMAP),
			'SMTP' => $this->debugLogPathFactory->buildPath($account, DebugLogPathFactory::PROTOCOL_SMTP),
			'Sieve' => $this->debugLogPathFactory->buildPath($account, DebugLogPathFactory::PROTOCOL_SIEVE),
		];
		if (!$active) {
			$logFiles = array_filter($logFiles, 'file_exists');
		}
		if ($logFiles === []) {
			return 0;
		}

		$output->writeln('Log files:');
		foreach ($logFiles as $protocol => $path) {
			$output->writeln("$protocol: $path");
		}
		if (!$active) {
			$output->writeln('Existing log files are not deleted automatically.');
		}

		return 0;
	}
}
