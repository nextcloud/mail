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
class Version5200Date20260714000000 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$localMessagesTable = $schema->getTable('mail_local_messages');
		if (!$localMessagesTable->hasColumn('governance_label_id')) {
			$localMessagesTable->addColumn('governance_label_id', Types::STRING, [
				'notnull' => false,
				'length' => 64,
				'default' => null,
			]);
		}

		$messagesTable = $schema->getTable('mail_messages');
		if (!$messagesTable->hasColumn('governance_label_id')) {
			$messagesTable->addColumn('governance_label_id', Types::STRING, [
				'notnull' => false,
				'length' => 64,
				'default' => null,
			]);
		}

		return $schema;
	}
}
