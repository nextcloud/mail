<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Job;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\TrashRetentionJob;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Db\MessageRetentionMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\Sync\SyncService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class TrashRetentionJobTest extends TestCase {

	private const ARGUMENT = null;

	/** @var ITimeFactory|MockObject */
	private $timeFactory;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IMAPClientFactory|MockObject */
	private $clientFactory;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var MessageRetentionMapper|MockObject */
	private $messageRetentionMapper;

	/** @var MailAccountMapper|MockObject */
	private $accountMapper;

	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var IMailManager|MockObject */
	private $mailManager;

	/** @var SyncService|MockObject */
	private $syncService;

	private TrashRetentionJob $job;

	protected function setUp(): void {
		parent::setUp();

		$this->timeFactory = $this->createMock(ITimeFactory::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->clientFactory = $this->createMock(IMAPClientFactory::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->messageRetentionMapper = $this->createMock(MessageRetentionMapper::class);
		$this->accountMapper = $this->createMock(MailAccountMapper::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->mailManager = $this->createMock(IMailManager::class);
		$this->syncService = $this->createMock(SyncService::class);

		$this->job = new TrashRetentionJob(
			$this->timeFactory,
			$this->logger,
			$this->clientFactory,
			$this->messageMapper,
			$this->messageRetentionMapper,
			$this->accountMapper,
			$this->mailboxMapper,
			$this->mailManager,
			$this->syncService,
		);
	}

	public function testRun() {
		$dbAccount = new MailAccount();
		$dbAccount->setTrashRetentionDays(60);
		$dbAccount->setTrashMailboxId(42);
		$dbAccount->setUserId('user');
		$account = new Account($dbAccount);
		$trash = new Mailbox();
		$message = new Message();
		$message->setMailboxId(123);
		$message->setUid(420);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);

		$this->accountMapper->expects($this->once())
			->method('getAllAccounts')
			->willReturn([$dbAccount]);
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(42)
			->willReturn($trash);
		$this->syncService->expects($this->never())
			->method('syncMailbox');
		$this->timeFactory->expects($this->once())
			->method('getTime')
			->willReturn(1000000);
		$this->messageMapper->expects($this->once())
			->method('findMessagesKnownSinceBefore')
			->with(42, 1000000 - 24 * 60 * 3600)
			->willReturn([$message]);
		$this->clientFactory->expects($this->once())
			->method('getClient')
			->willReturn($client);
		$this->mailManager->expects($this->once())
			->method('deleteMessageWithClient')
			->with($account, $trash, 420, $client);
		$client->expects($this->once())
			->method('logout');

		$this->job->run(self::ARGUMENT);
	}

	public function testRunWithoutRetention() {
		$dbAccount = new MailAccount();
		$dbAccount->setTrashRetentionDays(null);

		$this->accountMapper->expects($this->once())
			->method('getAllAccounts')
			->willReturn([$dbAccount]);
		$this->syncService->expects($this->never())
			->method('syncMailbox');
		$this->mailManager->expects($this->never())
			->method('deleteMessageWithClient');

		$this->job->run(self::ARGUMENT);
	}

	public function testRunWith0DaysRetention() {
		$dbAccount = new MailAccount();
		$dbAccount->setTrashRetentionDays(0);

		$this->accountMapper->expects($this->once())
			->method('getAllAccounts')
			->willReturn([$dbAccount]);
		$this->syncService->expects($this->never())
			->method('syncMailbox');
		$this->mailManager->expects($this->never())
			->method('deleteMessageWithClient');

		$this->job->run(self::ARGUMENT);
	}

	public function testRunWithNegativeRetention() {
		$dbAccount = new MailAccount();
		$dbAccount->setTrashRetentionDays(-1);

		$this->accountMapper->expects($this->once())
			->method('getAllAccounts')
			->willReturn([$dbAccount]);
		$this->syncService->expects($this->never())
			->method('syncMailbox');
		$this->mailManager->expects($this->never())
			->method('deleteMessageWithClient');

		$this->job->run(self::ARGUMENT);
	}

	public function testRunWithoutTrash() {
		$dbAccount = new MailAccount();
		$dbAccount->setTrashRetentionDays(60);
		$dbAccount->setTrashMailboxId(null);

		$this->accountMapper->expects($this->once())
			->method('getAllAccounts')
			->willReturn([$dbAccount]);
		$this->mailboxMapper->expects($this->never())
			->method('findById');
		$this->syncService->expects($this->never())
			->method('syncMailbox');
		$this->mailManager->expects($this->never())
			->method('deleteMessageWithClient');

		$this->job->run(self::ARGUMENT);
	}

	public function testRunWithNonExistingTrash() {
		$dbAccount = new MailAccount();
		$dbAccount->setTrashRetentionDays(60);
		$dbAccount->setTrashMailboxId(42);

		$this->accountMapper->expects($this->once())
			->method('getAllAccounts')
			->willReturn([$dbAccount]);
		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(42)
			->willThrowException(new DoesNotExistException('Mailbox 42 does not exist'));
		$this->syncService->expects($this->never())
			->method('syncMailbox');
		$this->mailManager->expects($this->never())
			->method('deleteMessageWithClient');

		$this->job->run(self::ARGUMENT);
	}
}
