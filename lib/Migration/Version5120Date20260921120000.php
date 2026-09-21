<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use Doctrine\DBAL\Platforms\PostgreSQLPlatform;
use OCP\DB\ISchemaWrapper;
use OCP\IDBConnection;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * @psalm-api
 */
class Version5120Date20260921120000 extends SimpleMigrationStep {
	public function __construct(
		private IDBConnection $connection,
	) {
	}

	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		if ($this->connection->getDatabasePlatform() instanceof PostgreSQLPlatform) {
			return null;
		}

		$schema = $schemaClosure();
		$changed = false;

		if ($schema->hasTable('mail_messages')) {
			$table = $schema->getTable('mail_messages');
			if ($table->hasIndex('mail_messages_msgid_idx')) {
				$table->dropIndex('mail_messages_msgid_idx');
			}
			$table->addIndex(
				['message_id'],
				'mail_messages_msgid_idx',
				[],
				['lengths' => [64]],
			);
			$changed = true;
		}

		if ($schema->hasTable('mail_message_tags')) {
			$table = $schema->getTable('mail_message_tags');
			if ($table->hasIndex('mail_msg_imap_id_idx')) {
				$table->dropIndex('mail_msg_imap_id_idx');
			}
			$table->addIndex(
				['imap_message_id'],
				'mail_msg_imap_id_idx',
				[],
				['lengths' => [64]],
			);
			$changed = true;
		}

		return $changed ? $schema : null;
	}
}
