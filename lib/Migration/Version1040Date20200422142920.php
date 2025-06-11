<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\DB\Types;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version1040Date20200422142920 extends SimpleMigrationStep {
	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 *
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options) {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$messagesTable = $schema->createTable('mail_messages');
		$messagesTable->addColumn('id', Types::INTEGER, [
			'autoincrement' => true,
			'notnull' => true,
			'length' => 20,
		]);
		$messagesTable->addColumn('uid', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$messagesTable->addColumn('message_id', Types::STRING, [
			'notnull' => false,
			'length' => 255,
		]);
		$messagesTable->addColumn('mailbox_id', Types::INTEGER, [
			'notnull' => true,
			'length' => 20,
		]);
		$messagesTable->addColumn('subject', Types::STRING, [
			'notnull' => true,
			'length' => 255,
			'default' => '',
		]);
		$messagesTable->addColumn('sent_at', Types::INTEGER, [
			'notnull' => true,
			'length' => 4,
		]);
		$messagesTable->addColumn('flag_answered', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_deleted', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_draft', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_flagged', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_seen', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_forwarded', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_junk', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_notjunk', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_attachments', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('flag_important', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('structure_analyzed', Types::BOOLEAN, [
			'notnull' => false,
			'default' => false,
		]);
		$messagesTable->addColumn('preview_text', Types::STRING, [
			'notnull' => false,
			'length' => 255,
		]);
		$messagesTable->addColumn('updated_at', Types::INTEGER, [
			'notnull' => false,
			'length' => 4,
		]);
		$messagesTable->setPrimaryKey(['id']);
		// We allow each UID just once
		$messagesTable->addUniqueIndex(
			[
				'uid',
				'mailbox_id',
			],
			'mail_msg_mb_uid_idx'
		);
		$messagesTable->addIndex(['sent_at'], 'mail_msg_sent_idx');

		return $schema;
	}
}
