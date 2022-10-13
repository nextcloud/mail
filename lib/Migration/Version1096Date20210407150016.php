<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1096Date20210407150016 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		if ($schema->hasTable('mail_accounts')) {
			$table = $schema->getTable('mail_accounts');
			$table->changeColumn('provisioned', [
				'notnull' => false,
				'default' => false
			]);
			$table->changeColumn('show_subscribed_only', [
				'notnull' => false,
				'default' => false
			]);
			$table = $schema->getTable('mail_accounts');
			$table->changeColumn('sieve_enabled', [
				'notnull' => false,
				'default' => false
			]);
		}

		if ($schema->hasTable('mail_classifiers')) {
			$table = $schema->getTable('mail_classifiers');
			$table->changeColumn('active', [
				'notnull' => false,
				'default' => false
			]);
		}

		if ($schema->hasTable('mail_mailboxes')) {
			$table = $schema->getTable('mail_mailboxes');
			$table->changeColumn('selectable', [
				'notnull' => false,
				'default' => false
			]);
			$table->changeColumn('sync_in_background', [
				'notnull' => false,
				'default' => false
			]);
		}

		if ($schema->hasTable('mail_messages')) {
			$table = $schema->getTable('mail_messages');
			$table->changeColumn('flag_answered', [
				'notnull' => false,
				'default' => false
			]);
			$table->changeColumn('flag_deleted', [
				'notnull' => false,
				'default' => false
			]);
			$table->changeColumn('flag_draft', [
				'notnull' => false,
				'default' => false
			]);
			$table->changeColumn('flag_flagged', [
				'notnull' => false,
				'default' => false
			]);
			$table->changeColumn('flag_seen', [
				'notnull' => false,
				'default' => false
			]);
			$table->changeColumn('flag_forwarded', [
				'notnull' => false,
				'default' => false
			]);
			$table->changeColumn('flag_junk', [
				'notnull' => false,
				'default' => false
			]);
			$table->changeColumn('flag_notjunk', [
				'notnull' => false,
				'default' => false
			]);
			$table->changeColumn('flag_important', [
				'notnull' => false,
				'default' => false
			]);
			$table->changeColumn('flag_mdnsent', [
				'notnull' => false,
				'default' => false
			]);
			$table->changeColumn('structure_analyzed', [
				'notnull' => false,
				'default' => false
			]);
		}

		return $schema;
	}
}
