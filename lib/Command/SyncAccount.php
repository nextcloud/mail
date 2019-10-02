<?php declare(strict_types=1);

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

use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\SyncService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class SyncAccount extends Command {

	const ARGUMENT_ACCOUNT_ID = 'account-id';
	const OPTION_FORCE = 'force';

	/** @var AccountService */
	private $accountService;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var SyncService */
	private $syncService;

	public function __construct(AccountService $service,
								MailboxMapper $mailboxMapper,
								SyncService $syncService) {
		parent::__construct();

		$this->accountService = $service;
		$this->mailboxMapper = $mailboxMapper;
		$this->syncService = $syncService;
	}

	protected function configure() {
		$this->setName('mail:account:sync');
		$this->setDescription('Synchronize an IMAP account');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
		$this->addOption(self::OPTION_FORCE, 'f', InputOption::VALUE_NONE);
	}

	protected function execute(InputInterface $input, OutputInterface $output) {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);
		$force = $input->getOption(self::OPTION_FORCE);

		$account = $this->accountService->findById($accountId);
		$this->syncService->syncAccount($account, $force);
	}
}
