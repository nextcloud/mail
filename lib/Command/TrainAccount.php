<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
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

final class TrainAccount extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';
	public const ARGUMENT_SHUFFLE = 'shuffle';
	public const ARGUMENT_DRY_RUN = 'dry-run';
	public const ARGUMENT_FORCE = 'force';

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

	protected function configure(): void {
		$this->setName('mail:account:train');
		$this->setDescription('Train the classifier of new messages');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
		$this->addOption(self::ARGUMENT_SHUFFLE, null, null, 'Shuffle data set before training');
		$this->addOption(
			self::ARGUMENT_DRY_RUN,
			null,
			null,
			'Don\'t persist classifier after training'
		);
		$this->addOption(
			self::ARGUMENT_FORCE,
			null,
			null,
			'Train an estimator even if the classification is disabled by the user'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);
		$shuffle = (bool)$input->getOption(self::ARGUMENT_SHUFFLE);
		$dryRun = (bool)$input->getOption(self::ARGUMENT_DRY_RUN);
		$force = (bool)$input->getOption(self::ARGUMENT_FORCE);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>account $accountId does not exist</error>");
			return 1;
		}

		if (!$force && !$this->classificationSettingsService->isClassificationEnabled($account->getUserId())) {
			$output->writeln("<info>classification is turned off for account $accountId</info>");
			return 2;
		}

		$consoleLogger = new ConsoleLoggerDecorator(
			$this->logger,
			$output
		);

		$this->classifier->train(
			$account,
			$consoleLogger,
			null,
			$shuffle,
			!$dryRun
		);

		$mbs = (int)(memory_get_peak_usage() / 1024 / 1024);
		$output->writeln('<info>' . $mbs . 'MB of memory used</info>');
		return 0;
	}
}
