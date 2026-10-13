<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Db\MailboxMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class UnlockMailbox extends Command {
	public const ARGUMENT_MAILBOX_ID = 'mailbox-id';
	public const OPTION_FORCE = 'force';

	public function __construct(
		private MailboxMapper $mailboxMapper,
		private ITimeFactory $timeFactory,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:mailbox:unlock');
		$this->setDescription('Release the sync locks of a mailbox');
		$this->addArgument(self::ARGUMENT_MAILBOX_ID, InputArgument::REQUIRED, 'Id of the mailbox to unlock');
		$this->addOption(self::OPTION_FORCE, 'f', InputOption::VALUE_NONE, 'Unlock even if the mailbox is still actively locked');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$mailboxId = (int)$input->getArgument(self::ARGUMENT_MAILBOX_ID);
		$force = (bool)$input->getOption(self::OPTION_FORCE);

		try {
			$mailbox = $this->mailboxMapper->findById($mailboxId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>Mailbox $mailboxId does not exist</error>");

			return self::FAILURE;
		}

		if ($mailbox->getSyncNewLock() === null && $mailbox->getSyncChangedLock() === null && $mailbox->getSyncVanishedLock() === null) {
			$output->writeln('<info>Mailbox not locked</info>');

			return self::SUCCESS;
		}

		if (!$force && $mailbox->hasLocks($this->timeFactory->getTime())) {
			$output->writeln("<error>Mailbox $mailboxId is still actively locked, a sync might be in progress. Use --force to unlock anyway</error>");

			return self::FAILURE;
		}

		$this->mailboxMapper->unlockFromNewSync($mailbox);
		$this->mailboxMapper->unlockFromChangedSync($mailbox);
		$this->mailboxMapper->unlockFromVanishedSync($mailbox);

		$output->writeln('<info>Mailbox unlocked</info>');

		return self::SUCCESS;
	}
}
