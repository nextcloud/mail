<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use Doctrine\DBAL\Schema\SchemaException;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1101Date20210616141806 extends SimpleMigrationStep {
	/**
	 * @throws SchemaException
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		$provisioningTable = $schema->getTable('mail_provisionings');
		$provisioningTable->addColumn('ldap_aliases_provisioning', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false
		]);
		$provisioningTable->addColumn('ldap_aliases_attribute', Types::STRING, [
			'notnull' => false,
			'length' => 255,
			'default' => '',
		]);

		return $schema;
	}
}
