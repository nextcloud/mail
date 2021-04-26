<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1090Date20210127160127 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('mail_accounts');
		$table->addColumn('sieve_enabled', 'boolean', [
			'notnull' => false,
			'default' => false,
		]);
		$table->addColumn('sieve_host', 'string', [
			'notnull' => false,
			'length' => 64,
			'default' => null,
		]);
		$table->addColumn('sieve_port', 'string', [
			'notnull' => false,
			'length' => 6,
			'default' => null,
		]);
		$table->addColumn('sieve_ssl_mode', 'string', [
			'notnull' => false,
			'length' => 10,
			'default' => null,
		]);
		$table->addColumn('sieve_user', 'string', [
			'notnull' => false,
			'length' => 64,
			'default' => null,
		]);
		$table->addColumn('sieve_password', 'string', [
			'notnull' => false,
			'length' => 2048,
			'default' => null,
		]);

		return $schema;
	}
}
