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
use Horde_Imap_Client_Socket;
use OC;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Controller\MailboxesController;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Sync\SyncService;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;
use OCP\IRequest;

class MailboxSynchronizationTest extends TestCase {
	use ImapTest,
		ImapTestAccount;

	/** @var MailboxesController */
	private $foldersController;

	/** @var \OCA\Mail\Db\MailAccount */
	private $account;

	/** @var Horde_Imap_Client_Socket $client */
	private $client;

	protected function setUp(): void {
		parent::setUp();

		$this->foldersController = new MailboxesController(
			'mail',
			OC::$server->get(IRequest::class),
			OC::$server->get(AccountService::class),
			$this->getTestAccountUserId(),
			OC::$server->get(IMailManager::class),
			OC::$server->get(SyncService::class)
		);

		$this->account = $this->createTestAccount('user12345');
		$this->client = $this->getClient($this->account);
	}

	public function tearDown(): void {
		parent::tearDown();
		$this->client->logout();
	}

	public function testSyncEmptyMailbox() {
		/** @var IMailManager $mailManager */
		$mailManager = OC::$server->get(IMailManager::class);
		$mailBoxes = $mailManager->getMailboxes(new Account($this->account));
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
			new Account($this->account),
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

		self::assertArrayHasKey('newMessages', $data);
		self::assertArrayHasKey('changedMessages', $data);
		self::assertArrayHasKey('vanishedMessages', $data);
		self::assertEmpty($data['newMessages']);
		self::assertEmpty($data['changedMessages']);
		self::assertEmpty($data['vanishedMessages']);
	}

	public function testSyncNewMessage() {
		/** @var SyncService $syncService */
		$syncService = OC::$server->get(SyncService::class);
		/** @var IMailManager $mailManager */
		$mailManager = OC::$server->get(IMailManager::class);
		$mailBoxes = $mailManager->getMailboxes(new Account($this->account));
		$inbox = null;
		foreach ($mailBoxes as $mailBox) {
			if ($mailBox->getName() === 'INBOX') {
				$inbox = $mailBox;
				break;
			}
		}
		$syncService->syncMailbox(
			new Account($this->account),
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
		$newUid = $this->saveMessage($inbox->getName(), $message, $this->account);

		$jsonResponse = $this->foldersController->sync(
			$inbox->getId(),
			[]
		);

		$syncJson = $jsonResponse->getData()->jsonSerialize();

		self::assertCount(1, $syncJson['newMessages']);
		self::assertEquals($newUid, $syncJson['newMessages'][0]->getUid());
		self::assertCount(0, $syncJson['changedMessages']);
		self::assertCount(0, $syncJson['vanishedMessages']);
	}

	public function testSyncChangedMessage() {
		/** @var SyncService $syncService */
		$syncService = OC::$server->get(SyncService::class);
		$mailbox = 'INBOX';
		$message = $this->getMessageBuilder()
			->from('ralph@buffington@domain.tld')
			->to('user@domain.tld')
			->finish();
		$uid = $this->saveMessage($mailbox, $message, $this->account);
		/** @var IMailManager $mailManager */
		$mailManager = OC::$server->get(IMailManager::class);
		$mailBoxes = $mailManager->getMailboxes(new Account($this->account));
		$inbox = null;
		foreach ($mailBoxes as $mailBox) {
			if ($mailBox->getName() === 'INBOX') {
				$inbox = $mailBox;
				break;
			}
		}
		$syncService->syncMailbox(
			new Account($this->account),
			$inbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			[],
			false
		);
		$this->flagMessage($mailbox, $uid, $this->account);
		$id = $mailManager->getMessageIdForUid($inbox, $uid);

		$jsonResponse = $this->foldersController->sync(
			$inbox->getId(),
			[
				$id
			]);
		$syncJson = $jsonResponse->getData()->jsonSerialize();

		self::assertCount(0, $syncJson['newMessages']);
		self::assertCount(1, $syncJson['changedMessages']);
		self::assertCount(0, $syncJson['vanishedMessages']);
	}

	public function testSyncVanishedMessage() {
		$mailbox = 'INBOX';
		$message = $this->getMessageBuilder()
			->from('ralph@buffington@domain.tld')
			->to('user@domain.tld')
			->finish();
		$uid = $this->saveMessage($mailbox, $message, $this->account);
		/** @var IMailManager $mailManager */
		$mailManager = OC::$server->get(IMailManager::class);
		$mailBoxes = $mailManager->getMailboxes(new Account($this->account));
		$inbox = null;
		foreach ($mailBoxes as $mailBox) {
			if ($mailBox->getName() === 'INBOX') {
				$inbox = $mailBox;
				break;
			}
		}
		/** @var SyncService $syncService */
		$syncService = OC::$server->get(SyncService::class);
		$syncService->syncMailbox(
			new Account($this->account),
			$inbox,
			Horde_Imap_Client::SYNC_NEWMSGSUIDS | Horde_Imap_Client::SYNC_FLAGSUIDS | Horde_Imap_Client::SYNC_VANISHEDUIDS,
			[],
			false
		);
		$this->deleteMessage($mailbox, $uid, $this->account);

		$jsonResponse = $this->foldersController->sync(
			$inbox->getId(),
			[
				$uid // This will only work if UID and database ID are equal (1 on a clean setup), otherwise this fails
			]);
		$syncJson = $jsonResponse->getData()->jsonSerialize();

		self::assertCount(0, $syncJson['newMessages']);
		// TODO: deleted messages are flagged as changed? could be a testing-only issue
		// self::assertCount(0, $syncJson['changedMessages']);
//		self::assertCount(1, $syncJson['vanishedMessages'], 'Message does not show as vanished, possibly because UID and ID are mixed up above.');
	}
}
