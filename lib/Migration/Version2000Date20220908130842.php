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

class Version2000Date20220908130842 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$messagesTable = $schema->getTable('mail_messages');
		if (!$messagesTable->hasColumn('imip_message')) {
			$messagesTable->addColumn('imip_message', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false,
			]);
		}
		if (!$messagesTable->hasColumn('imip_processed')) {
			$messagesTable->addColumn('imip_processed', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false,
			]);
		}
		if (!$messagesTable->hasColumn('imip_error')) {
			$messagesTable->addColumn('imip_error', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false,
			]);
		}
		return $schema;
	}
}
