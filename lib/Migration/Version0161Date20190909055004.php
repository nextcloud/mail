<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\QueryBuilder\IQueryBuilder;
use OCP\IDBConnection;
use OCP\Migration\SimpleMigrationStep;
use OCP\Migration\IOutput;

/**
 * Auto-generated migration step: Please modify to your needs!
 */
class Version0161Date20190909055004 extends SimpleMigrationStep {

	/** @var IDBConnection */
	protected $connection;

	public function __construct(IDBConnection $connection) {
		$this->connection = $connection;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$mailboxTable = $schema->getTable('mail_accounts');
		$mailboxTable->addColumn('managesieve_host', 'string', [
			'length' => 255,
		]);
		$mailboxTable->addColumn('managesieve_port', 'integer', [
			'default' => 4190,
		]);
		$mailboxTable->addColumn('managesieve_starttls', 'boolean', [
			'default' => true,
		]);

		return $schema;

	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options) {
     		$query = $this->connection->getQueryBuilder();
		$query->update('mail_accounts')
			->set('managesieve_host', 'inbound_host');

		$query->execute();

	}
}
