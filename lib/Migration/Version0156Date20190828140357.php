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
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0156Date20190828140357 extends SimpleMigrationStep {
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

		$accountsTable = $schema->getTable('mail_accounts');
		$accountsTable->addColumn('last_mailbox_sync', Types::INTEGER, [
			'default' => 0,
		]);

		$mailboxTable = $schema->createTable('mail_mailboxes');
		$mailboxTable->addColumn('id', Types::STRING, [
			'notnull' => true,
			'length' => 255,
		]);
		$mailboxTable->addColumn('account_id', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$mailboxTable->addColumn('sync_token', Types::STRING, [
			'notnull' => true,
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
		// We allow each mailbox name just once
		$mailboxTable->setPrimaryKey([
			'account_id',
			'id',
		]);

		return $schema;
	}
}
