<?php

declare(strict_types=1);

/**
 * @author Anna Larch <anna.larch@nextcloud.com>
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
use Psr\Log\LoggerInterface;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Exception\ClientException;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DeleteAccount extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';

	private AccountService $accountService;
	private LoggerInterface $logger;

	public function __construct(AccountService $service,
								LoggerInterface $logger) {
		parent::__construct();

		$this->accountService = $service;
		$this->logger = $logger;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:account:delete');
		$this->setDescription('Delete an IMAP account');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeLn('<error>This account does not exist</error>');
			return 1;
		}
		$output->writeLn("<info>Found account with email: " . $account->getEmail() . "</info>");

		if (!is_null($account->getMailAccount()->getProvisioningId())) {
			$output->writeLn('<error>This is a provisioned account which can not be deleted from CLI. Use the Provisioning UI instead.</error>');
			return 2;
		}
		$output->writeLn("<info>Deleting " . $account->getEmail() . "</info>");
		$this->delete($account, $output);

		return 0;
	}

	private function delete(Account $account, OutputInterface $output): void {
		$id = $account->getId();
		try {
			$this->accountService->deleteByAccountId($account->getId());
		} catch (ClientException $e) {
			throw $e;
		}
		$output->writeLn("<info>Deleted account $id </info>");
	}
}
