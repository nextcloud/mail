<?php

declare(strict_types=1);

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

/**
 * @link https://github.com/nextcloud/mail/issues/25
 */
class Version1100Date20210304143008 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('mail_tags')) {
			$tagsTable = $schema->createTable('mail_tags');
			$tagsTable->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$tagsTable->addColumn('user_id', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$tagsTable->addColumn('imap_label', 'string', [
				'notnull' => true,
				'length' => 64,
			]);
			$tagsTable->addColumn('display_name', 'string', [
				'notnull' => true,
				'length' => 128,
			]);
			$tagsTable->addColumn('color', 'string', [
				'notnull' => false,
				'length' => 9,
				'default' => "#fff"
			]);
			$tagsTable->addColumn('is_default_tag', 'boolean', [
				'notnull' => false,
				'default' => false
			]);
			$tagsTable->setPrimaryKey(['id']);
			$tagsTable->addIndex(['user_id'], 'mail_msg_tags_usr_id_index');
			$tagsTable->addUniqueIndex(
				[
					'user_id',
					'imap_label',
				],
				'mail_msg_tags_usr_lbl_idx'
			);
		}

		if (!$schema->hasTable('mail_message_tags')) {
			$tagsMessageTable = $schema->createTable('mail_message_tags');
			$tagsMessageTable->addColumn('id', 'integer', [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$tagsMessageTable->addColumn('imap_message_id', 'string', [
				'notnull' => true,
				'length' => 1023,
			]);
			$tagsMessageTable->addColumn('tag_id', 'integer', [
				'notnull' => true,
				'length' => 4,
			]);
			$tagsMessageTable->setPrimaryKey(['id']);
		}
		return $schema;
	}
}
