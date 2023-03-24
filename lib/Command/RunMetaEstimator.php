<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Command;

use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Classification\FeatureExtraction\NewCompositeExtractor;
use OCA\Mail\Service\Classification\ImportanceClassifier;
use OCA\Mail\Support\ConsoleLoggerDecorator;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use Psr\Container\ContainerInterface;
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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RunMetaEstimator extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';
	public const ARGUMENT_SHUFFLE = 'shuffle';
	public const ARGUMENT_LOAD_DATA = 'load-data';

	private AccountService $accountService;
	private LoggerInterface $logger;
	private ImportanceClassifier $classifier;
	private ContainerInterface $container;
	private IConfig $config;

	public function __construct(
		AccountService $accountService,
		LoggerInterface $logger,
		ImportanceClassifier $classifier,
		ContainerInterface $container,
		IConfig $config,
	) {
		parent::__construct();

		$this->accountService = $accountService;
		$this->logger = $logger;
		$this->classifier = $classifier;
		$this->container = $container;
		$this->config = $config;
	}

	protected function configure(): void {
		$this->setName('mail:account:run-meta-estimator');
		$this->setDescription('Run the meta estimator for an account');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
		$this->addOption(self::ARGUMENT_SHUFFLE, null, null, 'Shuffle data set before training');
		$this->addOption(
			self::ARGUMENT_LOAD_DATA,
			null,
			InputOption::VALUE_REQUIRED,
			'Load training data set from a JSON file'
		);
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

		/** @var NewCompositeExtractor $extractor */
		$extractor = $this->container->get(NewCompositeExtractor::class);
		$consoleLogger = new ConsoleLoggerDecorator(
			$this->logger,
			$output
		);

		if ($loadDataPath = $input->getOption(self::ARGUMENT_LOAD_DATA)) {
			$json = file_get_contents($loadDataPath);
			$dataSet = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		} else {
			$dataSet = $this->classifier->buildDataSet(
				$account,
				$extractor,
				$consoleLogger,
				null,
				$shuffle,
			);
		}

		$estimator = static function () use ($consoleLogger) {
			$params = [
				[5, 10, 15, 20, 25, 30], // Neighbors
				[true, false], // Weighted?
				[new Euclidean(), new Manhattan(), new Jaccard()], // Kernel
			];

			$estimator = new GridSearch(
				KNearestNeighbors::class,
				$params,
				new FBeta(),
				new KFold(3)
			);
			$estimator->setLogger($consoleLogger);
			$estimator->setBackend(new Amp());
			return $estimator;
		};

		if ($dataSet) {
			$this->classifier->trainWithCustomDataSet(
				$account,
				$consoleLogger,
				$dataSet,
				$extractor,
				$estimator,
				null,
				false,
			);
		} else {
			$this->classifier->train(
				$account,
				$consoleLogger,
				$extractor,
				$estimator,
				$shuffle,
				false,
			);
		}

		$mbs = (int)(memory_get_peak_usage() / 1024 / 1024);
		$output->writeln('<info>' . $mbs . 'MB of memory used</info>');
		return 0;
	}
}
