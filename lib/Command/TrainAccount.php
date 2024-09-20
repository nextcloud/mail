<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Classification\ClassificationSettingsService;
use OCA\Mail\Service\Classification\ImportanceClassifier;
use OCA\Mail\Support\ConsoleLoggerDecorator;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function memory_get_peak_usage;

class TrainAccount extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';

	private AccountService $accountService;
	private ImportanceClassifier $classifier;
	private LoggerInterface $logger;
	private ClassificationSettingsService $classificationSettingsService;

	public function __construct(AccountService $service,
		ImportanceClassifier $classifier,
		ClassificationSettingsService $classificationSettingsService,
		LoggerInterface $logger) {
		parent::__construct();

		$this->accountService = $service;
		$this->classifier = $classifier;
		$this->logger = $logger;
		$this->classificationSettingsService = $classificationSettingsService;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:account:train');
		$this->setDescription('Train the classifier of new messages');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
	}

	/**
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>account $accountId does not exist</error>");
			return 1;
		}
		if (!$this->classificationSettingsService->isClassificationEnabled($account->getMailAccount()->getUserId())) {
			$output->writeln("<info>classification is turned off for account $accountId</info>");
			return 2;
		}

		$consoleLogger = new ConsoleLoggerDecorator(
			$this->logger,
			$output
		);
		$this->classifier->train(
			$account,
			$consoleLogger
		);

		$mbs = (int)(memory_get_peak_usage() / 1024 / 1024);
		$output->writeln('<info>' . $mbs . 'MB of memory used</info>');
		return 0;
	}
}
