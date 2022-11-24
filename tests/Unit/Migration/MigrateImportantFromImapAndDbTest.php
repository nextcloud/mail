<?php

/**
 * @copyright 2021 Anna Larch <anna.larch@nextcloud.com>
 *
 * @author 2021 Anna Larch <anna.larch@nextcloud.com>
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

namespace OCA\Mail\Tests\Unit\Migration;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Db\Tag;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Migration\MigrateImportantFromImapAndDb;
use Psr\Log\LoggerInterface;

class MigrateImportantFromImapAndDbTest extends TestCase {
	/** @var MockObject */
	private $clientFactory;

	/** @var MockObject */
	private $client;

	/** @var MockObject */
	private $messageMapper;

	/** @var MockObject */
	private $mailboxMapper;

	/** @var MockObject */
	private $logger;

	/** @var MigrateImportantFromImapAndDb */
	private $migration;

	protected function setUp(): void {
		$this->client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->migration = new MigrateImportantFromImapAndDb(
			$this->messageMapper,
			$this->mailboxMapper,
			$this->logger
		);
		parent::setUp();
	}

	public function testMigrateImportantOnImap() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$uids = [1,2,3];

		$this->messageMapper->expects($this->once())
			->method('getFlagged')
			->with($this->client, $mailbox, '$important')
			->willReturn($uids);
		$this->messageMapper->expects($this->once())
			->method('addFlag')
			->with($this->client, $mailbox, $uids, Tag::LABEL_IMPORTANT);
		$this->logger->expects($this->never())
			->method('debug');

		$this->migration->migrateImportantOnImap($this->client, $account, $mailbox);
	}

	public function testMigrateImportantOnImapNoUids() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$uids = [];

		$this->messageMapper->expects($this->once())
			->method('getFlagged')
			->with($this->client, $mailbox, '$important')
			->willReturn($uids);
		$this->messageMapper->expects($this->never())
			->method('addFlag');
		$this->logger->expects($this->never())
			->method('debug');

		$this->migration->migrateImportantOnImap($this->client, $account, $mailbox);
	}

	public function testMigrateImportantOnImapExceptionGetFlagged() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$e = new Horde_Imap_Client_Exception('', 0);

		$this->messageMapper->expects($this->once())
			->method('getFlagged')
			->with($this->client, $mailbox, '$important')
			->willThrowException($e);
		$this->messageMapper->expects($this->never())
			->method('addFlag');
		$this->logger->expects($this->never())
			->method('debug');
		$this->expectException(ServiceException::class);

		$this->migration->migrateImportantOnImap($this->client, $account, $mailbox);
	}

	public function testMigrateImportantOnImapExceptionOnFlag() {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');
		$e = new Horde_Imap_Client_Exception('', 0);
		$uids = [1,2,3];

		$this->messageMapper->expects($this->once())
			->method('getFlagged')
			->with($this->client, $mailbox, '$important')
			->willReturn($uids);
		$this->messageMapper->expects($this->once())
			->method('addFlag')
			->with($this->client, $mailbox, $uids, Tag::LABEL_IMPORTANT)
			->willThrowException($e);
		$this->logger->expects($this->once())
			->method('debug')
			->with('Could not flag messages in mailbox <' . $mailbox->getId() . '>');
		$this->expectException(ServiceException::class);

		$this->migration->migrateImportantOnImap($this->client, $account, $mailbox);
	}

	public function migrateImportantFromDb() {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$uids = [1,2,3];

		$this->mailboxMapper->expects($this->once())
			->method('findFlaggedImportantUids')
			->with($mailbox->getId())
			->willReturn($uids);
		$this->messageMapper->expects($this->once())
			->method('addFlag')
			->with($this->client, $mailbox, $uids, Tag::LABEL_IMPORTANT);
		$this->logger->expects($this->never())
			->method('debug');

		$this->migration->migrateImportantFromDb($this->client, $account, $mailbox);
	}

	public function testMigrateImportantFromDbNoUids() {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$uids = [];

		$this->mailboxMapper->expects($this->once())
			->method('findFlaggedImportantUids')
			->with($mailbox->getId())
			->willReturn($uids);
		$this->messageMapper->expects($this->never())
			->method('addFlag');
		$this->logger->expects($this->never())
			->method('debug');

		$this->migration->migrateImportantFromDb($this->client, $account, $mailbox);
	}

	public function testMigrateImportantFromDbExceptionOnFlag() {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$mailbox->setName('INBOX');
		$e = new Horde_Imap_Client_Exception('', 0);
		$uids = [1,2,3];

		$this->mailboxMapper->expects($this->once())
			->method('findFlaggedImportantUids')
			->with($mailbox->getId())
			->willReturn($uids);
		$this->messageMapper->expects($this->once())
			->method('addFlag')
			->with($this->client, $mailbox, $uids, Tag::LABEL_IMPORTANT)
			->willThrowException($e);
		$this->logger->expects($this->once())
			->method('debug')
			->with('Could not flag messages in mailbox <' . $mailbox->getId() . '>');
		$this->expectException(ServiceException::class);

		$this->migration->migrateImportantFromDb($this->client, $account, $mailbox);
	}
}
