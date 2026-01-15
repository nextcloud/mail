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
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version5007Date20260114100548 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$provisioningTable = $schema->getTable('mail_provisionings');

		if (!$provisioningTable->hasColumn('name_templates')) {
			$provisioningTable->addColumn('name_templates', Types::TEXT, [
				'notnull' => false,
				'default' => '["%DISPLAYNAME%"]',
			]);
		}

		return $schema;
	}
}
