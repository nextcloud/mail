<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * @link https://github.com/nextcloud/mail/issues/4833
 */
class Version1100Date20210326103929 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if ($schema->hasTable('mail_message_tags')) {
			$table = $schema->getTable('mail_message_tags');
			if ($table->hasIndex('mail_msg_tag_id_idx')) {
				$table->dropIndex('mail_msg_tag_id_idx');
			}
			if (!$table->hasIndex('mail_msg_imap_id_idx')) {
				$table->addIndex(['imap_message_id'], 'mail_msg_imap_id_idx', [], ['lengths' => [128]]);
			}
		}
		return $schema;
	}
}
