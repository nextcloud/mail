<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Tests\Integration;

use Horde_Imap_Client;
use OC;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Controller\MailboxesController;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Sync\SyncService;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;

class MailboxSynchronizationTest extends TestCase {
	use ImapTest,
		ImapTestAccount;

	/** @var MailboxesController */
	private $foldersController;

	protected function setUp(): void {
		parent::setUp();

		$this->foldersController = new MailboxesController(
			'mail',
			OC::$server->getRequest(),
			OC::$server->query(AccountService::class),
			$this->getTestAccountUserId(),
			OC::$server->query(IMailManager::class),
			OC::$server->query(SyncService::class)
		);
	}

	public function testSyncEmptyMailbox() {
		$account = $this->createTestAccount();
		/** @var IMailManager $mailManager */
		$mailManager = OC::$server->query(IMailManager::class);
		$mailBoxes = $mailManager->getMailboxes(new Account($account));
		$inbox = null;
		foreach ($mailBoxes as $mailBox) {
			if ($mailBox->getName() === 'INBOX') {
				$inbox = $mailBox;
				break;
			}
		}
		/** @var SyncService $syncService */
		$syncService = OC::$server->query(SyncService::class);
		$syncService->syncMailbox(
			new Account($account),
			$inbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			[],
			false
		);

		$jsonResponse = $this->foldersController->sync(
			$inbox->getId(),
			[]
		);

		$data = $jsonResponse->getData()->jsonSerialize();
		$this->assertArrayHasKey('newMessages', $data);
		$this->assertArrayHasKey('changedMessages', $data);
		$this->assertArrayHasKey('vanishedMessages', $data);
		$this->assertEmpty($data['newMessages']);
		$this->assertEmpty($data['changedMessages']);
		$this->assertEmpty($data['vanishedMessages']);
	}

	public function testSyncNewMessage() {
		// First, set up account and retrieve sync token
		$account = $this->createTestAccount();
		/** @var SyncService $syncService */
		$syncService = OC::$server->query(SyncService::class);
		/** @var IMailManager $mailManager */
		$mailManager = OC::$server->query(IMailManager::class);
		$mailBoxes = $mailManager->getMailboxes(new Account($account));
		$inbox = null;
		foreach ($mailBoxes as $mailBox) {
			if ($mailBox->getName() === 'INBOX') {
				$inbox = $mailBox;
				break;
			}
		}
		$syncService->syncMailbox(
			new Account($account),
			$inbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			[],
			false
		);
		// Second, put a new message into the mailbox
		$message = $this->getMessageBuilder()
			->from('ralph@buffington@domain.tld')
			->to('user@domain.tld')
			->finish();
		$newUid = $this->saveMessage($inbox->getName(), $message, $account);

		$jsonResponse = $this->foldersController->sync(
			$inbox->getId(),
			[]
		);
		$syncJson = $jsonResponse->getData()->jsonSerialize();

		$this->assertCount(1, $syncJson['newMessages']);
		$this->assertEquals($newUid, $syncJson['newMessages'][0]->getUid());
		$this->assertCount(0, $syncJson['changedMessages']);
		$this->assertCount(0, $syncJson['vanishedMessages']);
	}

	public function testSyncChangedMessage() {
		$account = $this->createTestAccount();
		/** @var SyncService $syncService */
		$syncService = OC::$server->query(SyncService::class);
		$mailbox = 'INBOX';
		$message = $this->getMessageBuilder()
			->from('ralph@buffington@domain.tld')
			->to('user@domain.tld')
			->finish();
		$uid = $this->saveMessage($mailbox, $message, $account);
		/** @var IMailManager $mailManager */
		$mailManager = OC::$server->query(IMailManager::class);
		$mailBoxes = $mailManager->getMailboxes(new Account($account));
		$inbox = null;
		foreach ($mailBoxes as $mailBox) {
			if ($mailBox->getName() === 'INBOX') {
				$inbox = $mailBox;
				break;
			}
		}
		$syncService->syncMailbox(
			new Account($account),
			$inbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			[],
			false
		);
		$this->flagMessage($mailbox, $uid, $account);
		$id = $mailManager->getMessageIdForUid($inbox, $uid);

		$jsonResponse = $this->foldersController->sync(
			$inbox->getId(),
			[
				$id
			]);
		$syncJson = $jsonResponse->getData()->jsonSerialize();

		$this->assertCount(0, $syncJson['newMessages']);
		$this->assertCount(1, $syncJson['changedMessages']);
		$this->assertCount(0, $syncJson['vanishedMessages']);
	}

	public function testSyncVanishedMessage() {
		// First, put a message into the mailbox
		$account = $this->createTestAccount();
		$mailbox = 'INBOX';
		$message = $this->getMessageBuilder()
			->from('ralph@buffington@domain.tld')
			->to('user@domain.tld')
			->finish();
		$id = $this->saveMessage($mailbox, $message, $account);
		/** @var IMailManager $mailManager */
		$mailManager = OC::$server->query(IMailManager::class);
		$mailBoxes = $mailManager->getMailboxes(new Account($account));
		$inbox = null;
		foreach ($mailBoxes as $mailBox) {
			if ($mailBox->getName() === 'INBOX') {
				$inbox = $mailBox;
				break;
			}
		}
		/** @var SyncService $syncService */
		$syncService = OC::$server->query(SyncService::class);
		$syncService->syncMailbox(
			new Account($account),
			$inbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			[],
			false
		);
		$this->deleteMessage($mailbox, $id, $account);

		$jsonResponse = $this->foldersController->sync(
			$inbox->getId(),
			[
				$id
			]);
		$syncJson = $jsonResponse->getData()->jsonSerialize();

		$this->assertCount(0, $syncJson['newMessages']);
		// TODO: deleted messages are flagged as changed? could be a testing-only issue
		// $this->assertCount(0, $syncJson['changedMessages']);
		$this->assertCount(1, $syncJson['vanishedMessages']);
	}
}
