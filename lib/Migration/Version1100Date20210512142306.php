<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCA\Mail\BackgroundJob\MigrateImportantJob;
use OCA\Mail\Db\MailboxMapper;
use OCP\BackgroundJob\IJobList;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1100Date20210512142306 extends SimpleMigrationStep {
	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var IJobList */
	private $jobList;

	public function __construct(MailboxMapper $mailboxMapper, IJobList $jobList) {
		$this->mailboxMapper = $mailboxMapper;
		$this->jobList = $jobList;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
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
