<?php

declare(strict_types=1);

/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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

use OC\BackgroundJob\JobList;
use OCA\Mail\BackgroundJob\MigrateImportantJob;
use OCA\Mail\Db\MailboxMapper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CreateTagMigrationJobEntry extends Command {
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
