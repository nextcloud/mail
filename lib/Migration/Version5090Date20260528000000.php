<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version5090Date20260528000000 extends SimpleMigrationStep {

	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();
		$table = $schema->getTable('mail_messages');

		if (!$table->hasColumn('flag_ai_generated')) {
			$table->addColumn('flag_ai_generated', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false,
			]);
		}

		return $schema;
	}
}

