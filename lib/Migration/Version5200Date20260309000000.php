<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * Upgrade the (mailbox_id, uid) index on mail_messages from non-unique to unique.
 *
 * This enables INSERT IGNORE / ON CONFLICT DO NOTHING deduplication in
 * insertBulkIgnore(), eliminating the need to load all UIDs into PHP memory
 * via findAllUids().
 *
 * @psalm-api
 */
class Version5200Date20260309000000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('mail_messages');

		// Drop the old non-unique index if it exists
		if ($table->hasIndex('mail_messages_mb_id_uid')) {
			$table->dropIndex('mail_messages_mb_id_uid');
		}

		// Add as unique index
		$table->addUniqueIndex(['mailbox_id', 'uid'], 'mail_messages_mb_id_uid');

		return $schema;
	}
}
