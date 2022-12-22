<?php

declare(strict_types=1);

/**
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OCA\Mail\AddressList;
use OCA\Mail\Db\Message;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Classification\ImportanceClassifier;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function memory_get_peak_usage;

class PredictImportance extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';
	public const ARGUMENT_SENDER = 'sender';

	private AccountService $accountService;
	private ImportanceClassifier $classifier;
	private IConfig $config;
	private LoggerInterface $logger;

	public function __construct(AccountService $service,
								ImportanceClassifier $classifier,
								IConfig $config,
								LoggerInterface $logger) {
		parent::__construct();

		$this->accountService = $service;
		$this->classifier = $classifier;
		$this->logger = $logger;
		$this->config = $config;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:predict-importance');
		$this->setDescription('Predict importance of an incoming message');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
		$this->addArgument(self::ARGUMENT_SENDER, InputArgument::REQUIRED);
	}

	public function isEnabled() {
		return $this->config->getSystemValueBool('debug');
	}

	/**
	 * @return int
	 */
	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);
		$sender = $input->getArgument(self::ARGUMENT_SENDER);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>account $accountId does not exist</error>");
			return 1;
		}
		$fakeMessage = new Message();
		$fakeMessage->setUid(0);
		$fakeMessage->setFrom(AddressList::parse("Name <$sender>"));
		[$prediction] = $this->classifier->classifyImportance(
			$account,
			[$fakeMessage]
		);
		if ($prediction) {
			$output->writeln('Message is important');
		} else {
			$output->writeln('Message is not important');
		}

		$mbs = (int)(memory_get_peak_usage() / 1024 / 1024);
		$output->writeln('<info>' . $mbs . 'MB of memory used</info>');
		return 0;
	}
}
