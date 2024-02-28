<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Listener;

use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;
use OCP\IConfig;

/**
 * @template-implements IEventListener<Event|OptionalIndicesListener>
 */
class OptionalIndicesListener implements IEventListener {

	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function handle(Event $event): void {
		if (!($event instanceof AddMissingIndicesEvent)) {
			return;
		}

		if (version_compare($this->config->getSystemValue('version', '0.0.0'), '28.0.0', '>=')) {
			$event->addMissingIndex(
				'mail_messages',
				'mail_messages_msgid_idx',
				['message_id'],
				[
					'lengths' => [128],
				],
			);
		}

		$event->addMissingIndex(
			'mail_messages',
			'mail_messages_strucanalyz_idx',
			['structure_analyzed']
		);

		$event->addMissingIndex(
			'mail_classifiers',
			'mail_class_creat_idx',
			['created_at']
		);

		$event->addMissingIndex(
			'mail_accounts',
			'mail_acc_prov_idx',
			['provisioning_id']
		);

		$event->addMissingIndex(
			'mail_aliases',
			'mail_alias_accid_idx',
			['account_id']
		);

		if (method_exists($event, 'replaceIndex')) {
			$event->replaceIndex(
				'mail_messages',
				['mail_messages_mb_id_uid'],
				'mail_messages_mb_id_uid_uidx',
				['mailbox_id', 'uid'],
				true
			);

			$event->replaceIndex(
				'mail_smime_certificates',
				['mail_smime_certs_uid_idx'],
				'mail_smime_certs_uid_email_idx',
				['user_id', 'email_address'],
				false
			);

			$event->replaceIndex(
				'mail_trusted_senders',
				['mail_trusted_senders_type'],
				'mail_trusted_senders_idx',
				['user_id', 'email', 'type'],
				false
			);

			$event->replaceIndex(
				'mail_coll_addresses',
				['mail_coll_addr_userid_index', 'mail_coll_addr_email_index'],
				'mail_coll_idx',
				['user_id', 'email', 'display_name'],
				false
			);
		}
	}

}
