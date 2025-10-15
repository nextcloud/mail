<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0161Date20190902103701 extends SimpleMigrationStep {
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
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$mailboxTable = $schema->createTable('mail_mailboxes');
		$mailboxTable->addColumn('id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 20,
		]);
		$mailboxTable->addColumn('name', Types::STRING, [
			'notnull' => true,
			'length' => 255,
		]);
		$mailboxTable->addColumn('account_id', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$mailboxTable->addColumn('sync_token', Types::STRING, [
			'notnull' => false,
			'length' => 255,
		]);
		$mailboxTable->addColumn('attributes', Types::STRING, [
			'length' => 255,
			'default' => '[]',
		]);
		$mailboxTable->addColumn('delimiter', Types::STRING, [
			'notnull' => true,
			'length' => 1,
		]);
		$mailboxTable->addColumn('messages', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$mailboxTable->addColumn('unseen', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$mailboxTable->addColumn('selectable', Types::BOOLEAN, [
			'notnull' => false,
			'default' => true,
		]);
		$mailboxTable->setPrimaryKey(['id']);
		/*
		 * We allow each mailbox name just once
		 * @see \OCA\Mail\Migration\Version3500Date20231115184458::changeSchema
		 */
		// $mailboxTable->addUniqueIndex([
		// 	'account_id',
		// 	'name',
		// ]);

		return $schema;
	}

	/**
	 * @return void
	 */
	#[\Override]
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		// Force a re-sync
		$update = $this->connection->getQueryBuilder();
		$update->update('mail_accounts')
			->set('last_mailbox_sync', $update->createNamedParameter(0));
		$update->executeStatement();
	}
}
