<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCA\Mail\BackgroundJob\MigrateImportantJob;
use OCP\BackgroundJob\IJobList;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1100Date20210512142306 extends SimpleMigrationStep {
	/** @var IJobList */
	private $jobList;

	public function __construct(
		private readonly \OCA\Mail\Db\MailboxMapper $mailboxMapper,
		IJobList $jobList
	) {
		$this->jobList = $jobList;
	}

	/**
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 */
	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		if (!method_exists($this->mailboxMapper, 'findAllIds')) {
			$output->warning('New Mail code hasn\'t been loaded yet, skipping tag migration. Please run `occ mail:tags:migration-jobs` after the upgrade.');
			return;
		}

		foreach ($this->mailboxMapper->findAllIds() as $mailboxId) {
			$this->jobList->add(MigrateImportantJob::class, ['mailboxId' => $mailboxId]);
		}
	}
}
