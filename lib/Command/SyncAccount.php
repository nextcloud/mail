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

use OCA\Mail\Account;
use OCA\Mail\Exception\IncompleteSyncException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCA\Mail\Support\ConsoleLoggerDecorator;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function memory_get_peak_usage;
use function memory_get_usage;

class SyncAccount extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';
	public const OPTION_FORCE = 'force';

	private AccountService $accountService;
	private MailboxSync $mailboxSync;
	private ImapToDbSynchronizer $syncService;
	private LoggerInterface $logger;

	public function __construct(AccountService $service,
								MailboxSync $mailboxSync,
								ImapToDbSynchronizer $messageSync,
								LoggerInterface $logger) {
		parent::__construct();

		$this->accountService = $service;
		$this->mailboxSync = $mailboxSync;
		$this->syncService = $messageSync;
		$this->logger = $logger;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:account:sync');
		$this->setDescription('Synchronize an IMAP account');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
		$this->addOption(self::OPTION_FORCE, 'f', InputOption::VALUE_NONE);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);
		$force = $input->getOption(self::OPTION_FORCE);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>Account $accountId does not exist</error>");

			return 1;
		}

		$this->sync($account, $force, $output);

		$mbs = (int)(memory_get_peak_usage() / 1024 / 1024);
		$output->writeln('<info>' . $mbs . 'MB of memory used</info>');

		return 0;
	}

	private function sync(Account $account, bool $force, OutputInterface $output): void {
		$consoleLogger = new ConsoleLoggerDecorator(
			$this->logger,
			$output
		);

		try {
			$this->mailboxSync->sync($account, $consoleLogger, $force);
			$this->syncService->syncAccount($account, $consoleLogger, $force);
		} catch (ServiceException $e) {
			if (!($e instanceof IncompleteSyncException)) {
				throw $e;
			}

			$mbs = (int)(memory_get_usage() / 1024 / 1024);
			$output->writeln("<info>Batch of new messages sync'ed. " . $mbs . "MB of memory in use</info>");
			$this->sync($account, $force, $output);
		}
	}
}
