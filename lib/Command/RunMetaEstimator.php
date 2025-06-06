<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Classification\ImportanceClassifier;
use OCA\Mail\Support\ConsoleLoggerDecorator;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Rubix\ML\Backends\Amp;
use Rubix\ML\Classifiers\KNearestNeighbors;
use Rubix\ML\CrossValidation\KFold;
use Rubix\ML\CrossValidation\Metrics\FBeta;
use Rubix\ML\GridSearch;
use Rubix\ML\Kernels\Distance\Euclidean;
use Rubix\ML\Kernels\Distance\Jaccard;
use Rubix\ML\Kernels\Distance\Manhattan;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class RunMetaEstimator extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';
	public const ARGUMENT_SHUFFLE = 'shuffle';

	private AccountService $accountService;
	private LoggerInterface $logger;
	private ImportanceClassifier $classifier;
	private IConfig $config;

	public function __construct(
		AccountService $accountService,
		LoggerInterface $logger,
		ImportanceClassifier $classifier,
		IConfig $config,
	) {
		parent::__construct();

		$this->accountService = $accountService;
		$this->logger = $logger;
		$this->classifier = $classifier;
		$this->config = $config;
	}

	protected function configure(): void {
		$this->setName('mail:account:run-meta-estimator');
		$this->setDescription('Run the meta estimator for an account');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
		$this->addOption(self::ARGUMENT_SHUFFLE, null, null, 'Shuffle data set before training');
	}

	public function isEnabled(): bool {
		return $this->config->getSystemValueBool('debug');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);
		$shuffle = (bool)$input->getOption(self::ARGUMENT_SHUFFLE);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>Account $accountId does not exist</error>");
			return 1;
		}

		$consoleLogger = new ConsoleLoggerDecorator(
			$this->logger,
			$output
		);

		$estimator = static function () use ($consoleLogger) {
			$params = [
				[5, 10, 15, 20, 25, 30, 35, 40], // Neighbors
				[true, false], // Weighted?
				[new Euclidean(), new Manhattan(), new Jaccard()], // Kernel
			];

			$estimator = new GridSearch(
				KNearestNeighbors::class,
				$params,
				new FBeta(),
				new KFold(5)
			);
			$estimator->setLogger($consoleLogger);
			$estimator->setBackend(new Amp());
			return $estimator;
		};

		$pipeline = $this->classifier->train(
			$account,
			$consoleLogger,
			$estimator,
			$shuffle,
			false,
		);

		/** @var GridSearch $metaEstimator */
		$metaEstimator = $pipeline?->getEstimator();
		if ($metaEstimator !== null) {
			$output->writeln("<info>Best estimator: {$metaEstimator->base()}</info>");
		}

		$mbs = (int)(memory_get_peak_usage() / 1024 / 1024);
		$output->writeln('<info>' . $mbs . 'MB of memory used</info>');
		return 0;
	}
}
