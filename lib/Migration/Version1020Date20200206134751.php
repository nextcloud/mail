<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
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
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$messagesTable = $schema->getTable('mail_messages');
		$messagesTable->addColumn('structure_analyzed', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_attachments', 'boolean', [
			'notnull' => false,
		]);
		$messagesTable->addColumn('preview_text', 'string', [
			'notnull' => false,
			'length' => 255,
		]);

		return $schema;
	}
}
