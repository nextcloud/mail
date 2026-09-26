<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Sync\SyncService;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ClearMailboxCache extends Command {
	public const ARGUMENT_MAILBOX_ID = 'mailbox-id';

	public function __construct(
		private MailboxMapper $mailboxMapper,
		private AccountService $accountService,
		private SyncService $syncService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:mailbox:clear-cache');
		$this->setDescription('Clear the cached messages of a mailbox. The mailbox will be resynced from IMAP on its next regular sync');
		$this->addArgument(self::ARGUMENT_MAILBOX_ID, InputArgument::REQUIRED, 'Id of the mailbox to clear');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$mailboxId = (int)$input->getArgument(self::ARGUMENT_MAILBOX_ID);

		try {
			$mailbox = $this->mailboxMapper->findById($mailboxId);
			$account = $this->accountService->findById($mailbox->getAccountId());
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>Mailbox $mailboxId does not exist</error>");

			return self::FAILURE;
		}

		try {
			$this->syncService->clearCache($account, $mailbox);
		} catch (ServiceException $e) {
			$output->writeln('<error>' . $e->getMessage() . '</error>');

			return self::FAILURE;
		}

		$output->writeln('<info>Cache cleared</info>');

		return self::SUCCESS;
	}
}
