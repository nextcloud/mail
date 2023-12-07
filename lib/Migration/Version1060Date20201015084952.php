<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1060Date20201015084952 extends SimpleMigrationStep {
	/** @var IDBConnection */
	protected $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$accountsTable = $schema->getTable('mail_accounts');
		$accountsTable->addColumn('drafts_mailbox_id', Types::INTEGER, [
			'notnull' => false,
			'default' => null,
			'length' => 20,
		]);
		$accountsTable->addColumn('sent_mailbox_id', Types::INTEGER, [
			'notnull' => false,
			'default' => null,
			'length' => 20,
		]);
		$accountsTable->addColumn('trash_mailbox_id', Types::INTEGER, [
			'notnull' => false,
			'default' => null,
			'length' => 20,
		]);

		return $schema;
	}

	/**
	 * @return void
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
		// Force a re-sync, so the values are propagated ASAP
		$update = $this->connection->getQueryBuilder();
		$update->update('mail_accounts')
			->set('last_mailbox_sync', $update->createNamedParameter(0));
		$update->executeStatement();
	}
}
