<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version0151Date20190622040212 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('mail_accounts')) {
			$table = $schema->getTable('mail_accounts');

			$table->addColumn('sieve_host', 'string', [
				'notnull' => false,
				'length' => 64,
			]);

			$table->addColumn('sieve_port', 'string', [
				'notnull' => false,
				'length' => 6,
			]);

			$table->addColumn('sieve_ssl_mode', 'string', [
				'notnull' => false,
				'length' => 10,
			]);

			$table->addColumn('sieve_user', 'string', [
				'notnull' => false,
				'length' => 64,
			]);

			$table->addColumn('sieve_password', 'string', [
				'notnull' => false,
				'length' => 2048,
			]);
		}

		return $schema;
	}
}
