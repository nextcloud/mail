<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version4200Date20241210000000 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$outboxTable = $schema->getTable('mail_local_messages');
		if (!$outboxTable->hasColumn('body_plain')) {
			$outboxTable->addColumn('body_plain', Types::TEXT, [
				'notnull' => false,
			]);
		}
		if (!$outboxTable->hasColumn('body_html')) {
			$outboxTable->addColumn('body_html', Types::TEXT, [
				'notnull' => false,
			]);
		}
		return $schema;
	}
}
