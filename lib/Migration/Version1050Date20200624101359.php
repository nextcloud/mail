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

class Version1050Date20200624101359 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$messagesTable = $schema->getTable('mail_messages');
		$messagesTable->getColumn('message_id')->setLength(1023);
		$messagesTable->addColumn('references', Types::TEXT, [
			'notnull' => false,
		]);
		$messagesTable->addColumn('in_reply_to', Types::STRING, [
			'notnull' => false,
			'length' => 1023,
		]);
		$messagesTable->addColumn('thread_root_id', Types::STRING, [
			'notnull' => false,
			'length' => 1023,
		]);

		return $schema;
	}
}
