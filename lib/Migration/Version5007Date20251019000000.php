<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version5006Date20251019000000 extends SimpleMigrationStep {
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

		// Ensure created_at is NOT NULL and has no default, so app must set it.
		if ($attachments->hasColumn('created_at')) {
			$attachments->modifyColumn('created_at', [
				'default' => null,
			]);
		}

		return $schema;
	}
}
