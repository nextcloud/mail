<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Override;

/**
 * @psalm-api
 */
#[ModifyColumn(
	table: 'mail_attachments',
	description: 'Add column to store content-id and content-disposition', )
]
class Version5008Date20260320125737 extends SimpleMigrationStep {
	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	#[Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('mail_attachments')) {
			return null;
		}

		$attachmentsTable = $schema->getTable('mail_attachments');
		if (!$attachmentsTable->hasColumn('content_id')) {
			$attachmentsTable->addColumn('content_id', Types::STRING, [
				'notnull' => false,
				'length' => 255,
			]);
		}
		if (!$attachmentsTable->hasColumn('disposition')) {
			$attachmentsTable->addColumn('disposition', Types::STRING, [
				'notnull' => false,
				'length' => 10,
			]);
		}

		return $schema;
	}
}
