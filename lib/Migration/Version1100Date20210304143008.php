<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
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
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$schema = $schemaClosure();

		if (!$schema->hasTable('mail_tags')) {
			$tagsTable = $schema->createTable('mail_tags');
			$tagsTable->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$tagsTable->addColumn('user_id', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$tagsTable->addColumn('imap_label', Types::STRING, [
				'notnull' => true,
				'length' => 64,
			]);
			$tagsTable->addColumn('display_name', Types::STRING, [
				'notnull' => true,
				'length' => 128,
			]);
			$tagsTable->addColumn('color', Types::STRING, [
				'notnull' => false,
				'length' => 9,
				'default' => '#fff'
			]);
			$tagsTable->addColumn('is_default_tag', Types::BOOLEAN, [
				'notnull' => false,
				'default' => false
			]);
			$tagsTable->setPrimaryKey(['id']);
			// Dropped in Version3600Date20240205180726 because mail_msg_tags_usr_id_index is redundant with mail_msg_tags_usr_lbl_idx
			// $tagsTable->addIndex(['user_id'], 'mail_msg_tags_usr_id_index');
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
			$tagsMessageTable->addColumn('id', Types::INTEGER, [
				'autoincrement' => true,
				'notnull' => true,
				'length' => 4,
			]);
			$tagsMessageTable->addColumn('imap_message_id', Types::STRING, [
				'notnull' => true,
				'length' => 1023,
			]);
			$tagsMessageTable->addColumn('tag_id', Types::INTEGER, [
				'notnull' => true,
				'length' => 4,
			]);
			$tagsMessageTable->setPrimaryKey(['id']);
		}
		return $schema;
	}
}
