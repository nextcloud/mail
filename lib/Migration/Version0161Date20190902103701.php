<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
 *
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

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
		// We allow each mailbox name just once
		$mailboxTable->addUniqueIndex([
			'account_id',
			'name',
		]);

		return $schema;
	}

	/**
	 * @return void
	 */
	public function postSchemaChange(IOutput $output, \Closure $schemaClosure, array $options) {
		// Force a re-sync
		$update = $this->connection->getQueryBuilder();
		$update->update('mail_accounts')
			->set('last_mailbox_sync', $update->createNamedParameter(0));
		$update->execute();
	}
}
