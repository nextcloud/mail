<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version2020Date20221103140538 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @psalm-param Closure $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$localMessagesTable = $schema->getTable('mail_local_messages');
		if (!$localMessagesTable->hasColumn('updated_at')) {
			$localMessagesTable->addColumn('updated_at', Types::INTEGER, [
				'notnull' => false,
				'length' => 4,
			]);
		}
		$localMessagesTable->changeColumn('send_at', [
			'notnull' => false
		]);
		return $schema;
	}
}
