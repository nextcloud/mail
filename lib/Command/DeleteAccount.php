<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Account;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class DeleteAccount extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';

	public function __construct(
		private readonly AccountService $accountService
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:account:delete');
		$this->setDescription('Delete an IMAP account');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException) {
			$output->writeLn('<error>This account does not exist</error>');
			return 1;
		}
		$output->writeLn('<info>Found account with email: ' . $account->getEmail() . '</info>');

		if (!is_null($account->getMailAccount()->getProvisioningId())) {
			$output->writeLn('<error>This is a provisioned account which can not be deleted from CLI. Use the Provisioning UI instead.</error>');
			return 2;
		}
		$output->writeLn('<info>Deleting ' . $account->getEmail() . '</info>');
		$this->delete($account, $output);

		return 0;
	}

	private function delete(Account $account, OutputInterface $output): void {
		$id = $account->getId();
		$this->accountService->deleteByAccountId($account->getId());
		$output->writeLn("<info>Deleted account $id </info>");
	}
}
