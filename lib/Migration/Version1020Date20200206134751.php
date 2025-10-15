<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1020Date20200206134751 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$messagesTable = $schema->getTable('mail_messages');
		$messagesTable->addColumn('structure_analyzed', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_attachments', Types::BOOLEAN, [
			'notnull' => false,
		]);
		$messagesTable->addColumn('preview_text', Types::STRING, [
			'notnull' => false,
			'length' => 255,
		]);

		return $schema;
	}
}
