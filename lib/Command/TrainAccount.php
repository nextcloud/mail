<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
	private IUserPreferences $preferences;
	private LoggerInterface $logger;

	public function __construct(AccountService $service,
								ImportanceClassifier $classifier,
								IUserPreferences $preferences,
								LoggerInterface $logger) {
		parent::__construct();

		$this->accountService = $service;
		$this->classifier = $classifier;
		$this->logger = $logger;
		$this->preferences = $preferences;
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
		if ($this->preferences->getPreference($account->getUserId(), 'tag-classified-messages') === 'false') {
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
