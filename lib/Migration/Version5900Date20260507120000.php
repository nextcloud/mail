<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCA\Mail\Db\MessageTags;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\AddColumn;
use OCP\Migration\Attributes\ColumnType;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * Tracks whether a message tag was applied by the user or by the
 * automatic importance classifier. Existing rows are preserved as
 * user-applied so they remain valid ground truth for training.
 *
 * @psalm-api
 */
#[AddColumn(
	table: 'mail_message_tags',
	name: 'type',
	type: ColumnType::STRING,
	description: 'Source of the tag: user vs classifier',
)]
class Version5900Date20260507120000 extends SimpleMigrationStep {

	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('mail_message_tags')) {
			return null;
		}

		$table = $schema->getTable('mail_message_tags');
		if (!$table->hasColumn('type')) {
			$table->addColumn('type', Types::STRING, [
				'notnull' => true,
				'length' => 16,
				'default' => MessageTags::TYPE_USER,
			]);
		}

		return $schema;
	}
}
