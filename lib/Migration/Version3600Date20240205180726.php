<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Johannes Merkel <mail@johannesgge.de>
 *
 * @author Johannes Merkel <mail@johannesgge.de>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3600Date20240205180726 extends SimpleMigrationStep {

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		/** @var ISchemaWrapper $schema */
		$schema = $schemaClosure();

		$mailboxesTable = $schema->getTable('mail_messages');

		if ($mailboxesTable->hasIndex('mail_messages_mailbox_id')) {
			$mailboxesTable->dropIndex('mail_messages_mailbox_id');
		}

		$mailboxesTable = $schema->getTable('mail_smime_certificates');

		if ($mailboxesTable->hasIndex('mail_smime_certs_id_uid_idx')) {
			$mailboxesTable->dropIndex('mail_smime_certs_id_uid_idx');
		}

		$mailboxesTable = $schema->getTable('mail_tags');

		if ($mailboxesTable->hasIndex('mail_msg_tags_usr_id_index')) {
			$mailboxesTable->dropIndex('mail_msg_tags_usr_id_index');
		}

		return $schema;
	}
}
