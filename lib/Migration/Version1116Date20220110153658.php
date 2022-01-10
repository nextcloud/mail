<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use Doctrine\DBAL\Exception;
use Doctrine\DBAL\Platforms\PostgreSQL94Platform;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1116Date20220110153658 extends SimpleMigrationStep {


	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 * @throws Exception
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$table = $schema->getTable('mail_messages');
		if (!$table->hasIndex('mail_messages_id_flags')) {
			$table->addIndex(['mailbox_id', 'flag_important', 'flag_deleted', 'flag_seen'], 'mail_messages_id_flags');
		}
		if (!$table->hasIndex('mail_messages_id_flags2')) {
			$table->addIndex(['mailbox_id', 'flag_deleted', 'flag_flagged'], 'mail_messages_id_flags2');
		}

		// Postgres doesn't allow for shortened indices, so let's skip the last index.
		if ($schema->getDatabasePlatform() instanceof PostgreSQL94Platform) {
			return $schema;
		}

		if (!$table->hasIndex('mail_msg_thrd_root_snt_idx')) {
			$table->addIndex(['mailbox_id', 'thread_root_id', 'sent_at'], 'mail_msg_thrd_root_snt_idx', [], ['lengths' => [null, 64, null]]);
		}
		return $schema;
	}
}
