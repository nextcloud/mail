<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Command;

use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Classification\FeatureExtraction\IExtractor;
use OCA\Mail\Service\Classification\FeatureExtraction\NewCompositeExtractor;
use OCA\Mail\Service\Classification\FeatureExtraction\VanillaCompositeExtractor;
use OCA\Mail\Service\Classification\ImportanceClassifier;
use OCA\Mail\Support\ConsoleLoggerDecorator;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Rubix\ML\Classifiers\GaussianNB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function memory_get_peak_usage;

class TrainAccount extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';
	public const ARGUMENT_OLD = 'old';
	public const ARGUMENT_OLD_ESTIMATOR = 'old-estimator';
	public const ARGUMENT_OLD_EXTRACTOR = 'old-extractor';
	public const ARGUMENT_SHUFFLE = 'shuffle';
	public const ARGUMENT_SAVE_DATA = 'save-data';
	public const ARGUMENT_LOAD_DATA = 'load-data';
	public const ARGUMENT_DRY_RUN = 'dry-run';
	public const ARGUMENT_FORCE = 'force';

	private AccountService $accountService;
	private ImportanceClassifier $classifier;
	private IUserPreferences $preferences;
	private LoggerInterface $logger;
	private ContainerInterface $container;

	public function __construct(AccountService $service,
								ImportanceClassifier $classifier,
								IUserPreferences $preferences,
								LoggerInterface $logger,
								ContainerInterface $container) {
		parent::__construct();

		$this->accountService = $service;
		$this->classifier = $classifier;
		$this->logger = $logger;
		$this->preferences = $preferences;
		$this->container = $container;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:account:train');
		$this->setDescription('Train the classifier of new messages');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
		$this->addOption(
			self::ARGUMENT_OLD,
			null,
			null,
			'Use old vanilla composite extractor and GaussianNB estimator (implies --old-extractor and --old-estimator)'
		);
		$this->addOption(
			self::ARGUMENT_OLD_EXTRACTOR,
			null,
			null,
			'Use old vanilla composite extractor without text based features'
		);
		$this->addOption(self::ARGUMENT_OLD_ESTIMATOR, null, null, 'Use old GaussianNB estimator');
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
		$this->addOption(
			self::ARGUMENT_SAVE_DATA,
			null,
			InputOption::VALUE_REQUIRED,
			'Save training data set to a JSON file'
		);
		$this->addOption(
			self::ARGUMENT_LOAD_DATA,
			null,
			InputOption::VALUE_REQUIRED,
			'Load training data set from a JSON file'
		);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);
		$shuffle = (bool)$input->getOption(self::ARGUMENT_SHUFFLE);
		$dryRun = (bool)$input->getOption(self::ARGUMENT_DRY_RUN);
		$force = (bool)$input->getOption(self::ARGUMENT_FORCE);
		$old = (bool)$input->getOption(self::ARGUMENT_OLD);
		$oldEstimator = $old || $input->getOption(self::ARGUMENT_OLD_ESTIMATOR);
		$oldExtractor = $old || $input->getOption(self::ARGUMENT_OLD_EXTRACTOR);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>account $accountId does not exist</error>");
			return 1;
		}

		if (!$force && $this->preferences->getPreference($account->getUserId(), 'tag-classified-messages') === 'false') {
			$output->writeln("<info>classification is turned off for account $accountId</info>");
			return 2;
		}

		/** @var IExtractor $extractor */
		if ($oldExtractor) {
			$extractor = $this->container->get(VanillaCompositeExtractor::class);
		} else {
			$extractor = $this->container->get(NewCompositeExtractor::class);
		}

		$estimator = null;
		if ($oldEstimator) {
			$estimator = static function () {
				return new GaussianNB();
			};
		}

		$consoleLogger = new ConsoleLoggerDecorator(
			$this->logger,
			$output
		);

		$dataSet = null;
		if ($saveDataPath = $input->getOption(self::ARGUMENT_SAVE_DATA)) {
			$dataSet = $this->classifier->buildDataSet(
				$account,
				$extractor,
				$consoleLogger,
				null,
				$shuffle,
			);
			$json = json_encode($dataSet, JSON_THROW_ON_ERROR);
			file_put_contents($saveDataPath, $json);
		} elseif ($loadDataPath = $input->getOption(self::ARGUMENT_LOAD_DATA)) {
			$json = file_get_contents($loadDataPath);
			$dataSet = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
		}

		if ($dataSet) {
			$this->classifier->trainWithCustomDataSet(
				$account,
				$consoleLogger,
				$dataSet,
				$extractor,
				$estimator,
				null,
				!$dryRun
			);
		} else {
			$this->classifier->train(
				$account,
				$consoleLogger,
				$extractor,
				$estimator,
				$shuffle,
				!$dryRun
			);
		}

		$mbs = (int)(memory_get_peak_usage() / 1024 / 1024);
		$output->writeln('<info>' . $mbs . 'MB of memory used</info>');
		return 0;
	}
}
