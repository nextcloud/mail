<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\Attributes\ModifyColumn;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * @psalm-api
 */
#[ModifyColumn(
	table: 'mail_attachments',
	description: 'Remove default value for created_at')
]
class Version5008Date20260320000001 extends SimpleMigrationStep {
	/**
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('mail_attachments')) {
			return $schema;
		}

		$attachments = $schema->getTable('mail_attachments');

		// Drop default value for created_at column
		if ($attachments->hasColumn('created_at')) {
			$attachments->modifyColumn('created_at', [
				'default' => null,
			]);
		}

		return $schema;
	}
}
