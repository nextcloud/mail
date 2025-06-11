<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OC\BackgroundJob\JobList;
use OCA\Mail\BackgroundJob\MigrateImportantJob;
use OCA\Mail\Db\MailboxMapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CreateTagMigrationJobEntry extends Command {
	private JobList $jobList;
	private MailboxMapper $mailboxMapper;

	public function __construct(JobList $jobList,
		MailboxMapper $mailboxMapper) {
		parent::__construct();
		$this->jobList = $jobList;
		$this->mailboxMapper = $mailboxMapper;
	}

	protected function configure(): void {
		$this->setName('mail:tags:migration-jobs');
		$this->setDescription('Creates a background job entry in the cron table for every user to migrate important labels to IMAP');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$count = 0;
		foreach ($this->mailboxMapper->findAllIds() as $mailboxId) {
			$this->jobList->add(MigrateImportantJob::class, ['mailboxId' => $mailboxId]);
			$count++;
		}

		$output->writeln("Created entries for $count mailboxes in Cron Jobs table.");

		return 0;
	}
}
