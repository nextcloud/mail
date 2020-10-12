<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use Doctrine\DBAL\Types\Type;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

class Version1040Date20200526162604 extends SimpleMigrationStep {
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

			$table->addColumn('sieve_enabled', Type::BOOLEAN, [
				'notnull' => true,
				'default' => false,
			]);

			$table->addColumn('sieve_host', Type::STRING, [
				'notnull' => false,
				'length' => 64,
			]);

			$table->addColumn('sieve_port', Type::INTEGER, [
				'notnull' => false,
			]);

			$table->addColumn('sieve_ssl_mode', Type::STRING, [
				'notnull' => false,
				'length' => 10,
			]);

			$table->addColumn('sieve_user', Type::STRING, [
				'notnull' => false,
				'length' => 64,
			]);

			$table->addColumn('sieve_password', Type::STRING, [
				'notnull' => false,
				'length' => 2048,
			]);
		}

		return $schema;
	}
}
