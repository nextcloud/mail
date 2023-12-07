<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Migration;

use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version0100Date20180825194217 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param \Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, \Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		/*
		 * Schema generated from database.xml but required changes for
		 * https://github.com/nextcloud/mail/issues/784 already applied.
		 */

		if (!$schema->hasTable('mail_accounts')) {
			$table = $schema->createTable('mail_accounts');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$table->addColumn('name', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('email', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('inbound_host', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('inbound_port', Types::STRING, [
				'notnull' => true,
				'length' => 6,
				'default' => '',
			]);
			$table->addColumn('inbound_ssl_mode', Types::STRING, [
				'notnull' => true,
				'length' => 10,
				'default' => '',
			]);
			$table->addColumn('inbound_user', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('inbound_password', Types::STRING, [
				'notnull' => true,
				'length' => 2048,
				'default' => '',
			]);
			$table->addColumn('outbound_host', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('outbound_port', Types::STRING, [
				'notnull' => false,
				'length' => 6,
			]);
			$table->addColumn('outbound_ssl_mode', Types::STRING, [
				'notnull' => false,
				'length' => 10,
			]);
			$table->addColumn('outbound_user', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('outbound_password', Types::STRING, [
				'notnull' => false,
				'length' => 2048,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['user_id'], 'mail_userid_index');
		}

		if (!$schema->hasTable('mail_coll_addresses')) {
			$table = $schema->createTable('mail_coll_addresses');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('email', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->addColumn('display_name', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['user_id'], 'mail_coll_addr_userid_index');
			$table->addIndex(['email'], 'mail_coll_addr_email_index');
		}

		if (!$schema->hasTable('mail_aliases')) {
			$table = $schema->createTable('mail_aliases');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('account_id', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
				'default' => 0,
			]);
			$table->addColumn('name', Types::STRING, [
				'notnull' => false,
				'length' => 64,
			]);
			$table->addColumn('alias', Types::STRING, [
				'notnull' => true,
				'length' => 255,
			]);
			$table->setPrimaryKey(['id']);
		}

		if (!$schema->hasTable('mail_attachments')) {
			$table = $schema->createTable('mail_attachments');
			$table->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$table->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
				'default' => '',
			]);
			$table->addColumn('file_name', Types::STRING, [
				'notnull' => true,
				'length' => 255,
				'default' => '',
			]);
			$table->addColumn('created_at', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
				'default' => 0,
			]);
			$table->setPrimaryKey(['id']);
			$table->addIndex(['user_id'], 'mail_attach_userid_index');
		}

		return $schema;
	}
}
