<?php

/**
 * @copyright 2021 Anna Larch <anna@nextcloud.com>
 *
 * @copyright 2021 Anna Larch <anna@nextcloud.com>
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
 *
 */

namespace OCA\Mail\Tests\Integration\Service;

use Horde_Imap_Client;
use OC;
use OCA\Mail\Account;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Service\AntiSpamService;
use OCA\Mail\Service\Sync\SyncService;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;

class AntiSpamServiceIntegrationTest extends TestCase {
	use ImapTest,
		ImapTestAccount;

	/** @var AntiSpamService */
	private $service;

	public function setUp():void {
		parent::setUp();
		$this->service = OC::$server->get(AntiSpamService::class);
		$this->service->setSpamEmail('spam@domain.tld');
		$this->service->setHamEmail('notspam@domain.tld');
	}

	public function tearDown(): void {
		$this->resetImapAccount();
		$this->disconnectImapAccount();
		$this->service->deleteConfig();
	}

	public function testFlagJunkWithSpamReportActive(): void {
		// First, set up account and retrieve sync token
		$this->resetImapAccount();
		$this->disconnectImapAccount();
		$account = $this->createTestAccount();

		/** @var SyncService $syncService */
		$syncService = OC::$server->get(SyncService::class);
		/** @var ImapMessageMapper $imapMessageMapper */
		$imapMessageMapper = OC::$server->get(ImapMessageMapper::class);
		/** @var MessageMapper $messageMapper */
		$messageMapper = OC::$server->get(MessageMapper::class);
		/** @var IMailManager $mailManager */
		$mailManager = OC::$server->get(IMailManager::class);
		$mailBoxes = $mailManager->getMailboxes(new Account($account));
		$inbox = null;
		foreach ($mailBoxes as $mailBox) {
			if ($mailBox->getName() === 'INBOX') {
				$inbox = $mailBox;
				break;
			}
		}

		// Second, put a new message into the mailbox
		$message = $this->getMessageBuilder()
			->from('buffington@domain.tld')
			->to('user@domain.tld')
			->finish();
		$newUid = $this->saveMessage($inbox->getName(), $message, $account);

		// sync in between creating and flagging otherwise it can't be found
		$syncService->syncMailbox(
			new Account($account),
			$inbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			null,
			false
		);

		// now we flag this message as junk
		$mailManager->flagMessage(new Account($account), $inbox->getName(), $newUid, 'junk', true);

		// if everything runs through, we can assert the run has been fine,
		// but we can't really test if Listener and Transmission have actually sent the message
		$this->addToAssertionCount(1);

		// now we flag this message as not junk
		$mailManager->flagMessage(new Account($account), $inbox->getName(), $newUid, 'notjunk', true);

		// same as before
		$this->addToAssertionCount(1);
	}
}
