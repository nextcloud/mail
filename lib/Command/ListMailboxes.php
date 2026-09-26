<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class ListMailboxes extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';

	public function __construct(
		private readonly AccountService $accountService,
		private readonly MailboxMapper $mailboxMapper,
		private readonly ITimeFactory $timeFactory,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:mailbox:list');
		$this->setDescription('List the mailboxes of an account');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED, 'Id of the account');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>Account $accountId does not exist</error>");

			return self::FAILURE;
		}

		$mailboxes = $this->mailboxMapper->findAll($account);
		usort($mailboxes, static fn (Mailbox $a, Mailbox $b) => [$a->getName(), $a->getId()] <=> [$b->getName(), $b->getId()]);
		$now = $this->timeFactory->getTime();

		$table = new Table($output);
		$table->setHeaders(['Id', 'Name', 'Special Use', 'Messages', 'Unseen', 'Shared', 'Lock']);
		foreach ($mailboxes as $mailbox) {
			$table->addRow([
				$mailbox->getId(),
				$mailbox->getName(),
				$mailbox->getSpecialUse(),
				$mailbox->getMessages(),
				$mailbox->getUnseen(),
				$mailbox->isShared() ? 'yes' : 'no',
				$mailbox->hasLocks($now) ? 'yes' : 'no',
			]);
		}
		$table->render();

		return self::SUCCESS;
	}
}
