<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1050Date20200624101359 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$messagesTable = $schema->getTable('mail_messages');
		$messagesTable->getColumn('message_id')->setLength(1023);
		$messagesTable->addColumn('references', 'text', [
			'notnull' => false,
		]);
		$messagesTable->addColumn('in_reply_to', 'string', [
			'notnull' => false,
			'length' => 1023,
		]);
		$messagesTable->addColumn('thread_root_id', 'string', [
			'notnull' => false,
			'length' => 1023,
		]);

		return $schema;
	}
}
