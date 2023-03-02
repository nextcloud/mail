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
use OCA\Mail\Service\Classification\FeatureExtraction\NewCompositeExtractor;
use OCA\Mail\Service\Classification\FeatureExtraction\VanillaCompositeExtractor;
use OCA\Mail\Service\Classification\ImportanceClassifier;
use OCA\Mail\Support\ConsoleLoggerDecorator;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function memory_get_peak_usage;

class TrainAccount extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';
	public const ARGUMENT_NEW = 'new';
	public const ARGUMENT_SHUFFLE = 'shuffle';
	public const ARGUMENT_SAVE_DATA = 'save-data';
	public const ARGUMENT_LOAD_DATA = 'load-data';
	public const ARGUMENT_DRY_RUN = 'dry-run';

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
		$this->addOption(self::ARGUMENT_NEW, null, null, 'Enable new composite extractor using text based features');
		$this->addOption(self::ARGUMENT_SHUFFLE, null, null, 'Shuffle data set before training');
		$this->addOption(
			self::ARGUMENT_DRY_RUN,
			null,
			null,
			'Don\'t persist classifier after training'
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

	/**
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);
		$shuffle = (bool)$input->getOption(self::ARGUMENT_SHUFFLE);
		$dryRun = (bool)$input->getOption(self::ARGUMENT_DRY_RUN);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>account $accountId does not exist</error>");
			return 1;
		}

		/*
		if ($this->preferences->getPreference($account->getUserId(), 'tag-classified-messages') === 'false') {
			$output->writeln("<info>classification is turned off for account $accountId</info>");
			return 2;
		}
		*/

		if ($input->getOption(self::ARGUMENT_NEW)) {
			$extractor = $this->container->get(NewCompositeExtractor::class);
		} else {
			$extractor = $this->container->get(VanillaCompositeExtractor::class);
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
			$json = json_encode($dataSet);
			file_put_contents($saveDataPath, $json);
		} else if ($loadDataPath = $input->getOption(self::ARGUMENT_LOAD_DATA)) {
			$json = file_get_contents($loadDataPath);
			$dataSet = json_decode($json, true);
		}

		if ($dataSet) {
			$this->classifier->trainWithCustomDataSet(
				$account,
				$consoleLogger,
				$dataSet,
				null,
				null,
				!$dryRun
			);
		} else {
			$this->classifier->train(
				$account,
				$consoleLogger,
				$extractor,
				null,
				$shuffle,
				!$dryRun
			);

		}

		$mbs = (int)(memory_get_peak_usage() / 1024 / 1024);
		$output->writeln('<info>' . $mbs . 'MB of memory used</info>');
		return 0;
	}
}
