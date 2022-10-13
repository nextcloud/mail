<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1040Date20200422130220 extends SimpleMigrationStep {
	/** @var IDBConnection */
	protected $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$schema->dropTable('mail_messages');

		return $schema;
	}

	/**
	 * @return void
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		// Reset locks and sync tokens
		$qb1 = $this->connection->getQueryBuilder();
		$updateMailboxes = $qb1->update('mail_mailboxes')
			->set('sync_new_lock', $qb1->createNamedParameter(null))
			->set('sync_new_token', $qb1->createNamedParameter(null))
			->set('sync_changed_lock', $qb1->createNamedParameter(null))
			->set('sync_changed_token', $qb1->createNamedParameter(null))
			->set('sync_vanished_lock', $qb1->createNamedParameter(null))
			->set('sync_vanished_token', $qb1->createNamedParameter(null));
		$updateMailboxes->execute();

		// Clean up some orphaned data
		$qb2 = $this->connection->getQueryBuilder();
		$deleteRecipients = $qb2->delete('mail_recipients');
		$deleteRecipients->execute();
	}
}
