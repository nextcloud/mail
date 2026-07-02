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

/**
 * @psalm-api
 */
class Version5201Date20260604000000 extends SimpleMigrationStep {
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$localMessages = $schema->getTable('mail_local_messages');
		if (!$localMessages->hasColumn('ai_generated')) {
			$localMessages->addColumn('ai_generated', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false,
			]);
		}

		$messages = $schema->getTable('mail_messages');
		if (!$messages->hasColumn('flag_ai_generated')) {
			$messages->addColumn('flag_ai_generated', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false,
			]);
		}

		return $schema;
	}
}
