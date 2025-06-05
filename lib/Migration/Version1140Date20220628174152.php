<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use Doctrine\DBAL\Types\Types;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1140Date20220628174152 extends SimpleMigrationStep {
	private IDBConnection $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	#[\Override]
	public function preSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		/*
		 * Increase size limit for signature column.
		 *
		 * Initially the signature column was created with length = 1024.
		 * On mysql/mariadb the column is able to store 65535 bytes.
		 *
		 * To create a column with type longtext length must be null (or an integer bigger than 16777215):
		 * https://github.com/nextcloud/3rdparty/blob/2ae1a1d6f688ae8394d6559ee673fecbee975db4/doctrine/dbal/src/Platforms/MySQLPlatform.php#L237-L265
		 *
		 * Length option is only relevant for MySQL/MariaDB. Postgre, Oracle and Sqlite don't have a
		 * concept like tinytext, mediumtext, text and longtext.
		 *
		 * Postgre: https://www.postgresql.org/docs/9.1/datatype-character.html
		 * Oracle: https://docs.oracle.com/en/database/oracle/oracle-database/19/sqlqr/Data-Types.html#GUID-219C338B-FE60-422A-B196-2F0A01CAD9A4
		 * Sqlite: https://www.sqlite.org/datatype3.html / https://www.sqlite.org/limits.html
		 *
		 * To make it worse our doctrine version (3.1.6 for Nextcloud 24) is missing the logic to detect
		 * that the column length changed: https://github.com/doctrine/dbal/issues/2566
		 */

		if ($this->connection->getDatabasePlatform() instanceof MySQLPlatform) {
			$alterQuery = 'ALTER TABLE `%s` MODIFY `%s` longtext null;';

			$accountsTable = $schema->getTable('mail_accounts');
			$accountsSignatureColumn = $accountsTable->getColumn('signature');

			$this->connection->executeStatement(
				sprintf($alterQuery, $accountsTable->getName(), $accountsSignatureColumn->getName())
			);

			$aliasesTable = $schema->getTable('mail_aliases');
			$aliasesSignatureColumn = $accountsTable->getColumn('signature');

			$this->connection->executeStatement(
				sprintf($alterQuery, $aliasesTable->getName(), $aliasesSignatureColumn->getName())
			);

			unset(
				$accountsTable,
				$accountsSignatureColumn,
				$aliasesTable,
				$aliasesSignatureColumn
			);
		}
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$accountsTable = $schema->getTable('mail_accounts');
		$aliasesTables = $schema->getTable('mail_aliases');

		if (!$accountsTable->hasColumn('signature_mode')) {
			$accountsTable->addColumn('signature_mode', Types::SMALLINT, [
				'default' => 0,
			]);
		}

		if (!$aliasesTables->hasColumn('signature_mode')) {
			$aliasesTables->addColumn('signature_mode', Types::SMALLINT, [
				'default' => 0,
			]);
		}

		return $schema;
	}
}
