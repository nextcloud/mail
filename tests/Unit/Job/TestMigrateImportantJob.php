<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Job;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\MigrateImportantJob;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Migration\MigrateImportantFromImapAndDb;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class TestMigrateImportantJob extends TestCase {
	use ImapTest;

	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var MailAccountMapper|MockObject */
	private $mailAccountMapper;

	/** @var MailManager|MockObject */
	private $mailManager;

	/** @var MigrateImportantFromImapAndDb|MockObject */
	private $migration;

	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var IJobList|MockObject */
	private $jobList;

	/** @var MigrateImportantJob */
	private $job;

	/** @var [] */
	private static $argument;

	protected function setUp(): void {
		parent::setUp();
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->mailAccountMapper = $this->createMock(MailAccountMapper::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->migration = $this->createMock(MigrateImportantFromImapAndDb::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->job = new MigrateImportantJob(
			$this->mailboxMapper,
			$this->mailAccountMapper,
			$this->mailManager,
			$this->migration,
			$this->logger,
			$this->jobList,
			$this->createMock(ITimeFactory::class)
		);

		self::$argument = ['mailboxId' => 1];
	}

	public function testRun() {
		$mailbox = new Mailbox();
		$mailbox->setId(self::$argument['mailboxId']);
		$mailbox->setName('INBOX');
		$mailbox->setAccountId(1);
		$mailAccount = new MailAccount();
		$account = new Account($mailAccount);

		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(self::$argument['mailboxId'])
			->willReturn($mailbox);
		$this->mailAccountMapper->expects($this->once())
			->method('findById')
			->with($mailbox->getAccountId())
			->willReturn($mailAccount);
		$this->mailManager->expects($this->once())
			->method('isPermflagsEnabled')
			->with($account, $mailbox->getName())
			->willReturn(true);
		$this->migration->expects($this->once())
			->method('migrateImportantOnImap')
			->with($account, $mailbox);
		$this->migration->expects($this->once())
			->method('migrateImportantFromDb')
			->with($account, $mailbox);
		$this->jobList->expects($this->never())
			->method('remove');
		$this->logger->expects($this->never())
			->method('debug');

		$this->job->run(self::$argument);
	}

	public function testRunNoMailbox() {
		$e = new DoesNotExistException('does not exist');

		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(self::$argument['mailboxId'])
			->willThrowException($e);
		$this->logger->expects($this->once())
			->method('debug')
			->with('Could not find mailbox <' . self::$argument['mailboxId'] . '>, removing from jobs');
		$this->jobList->expects($this->once())
			->method('remove')
			->with(MigrateImportantJob::class, self::$argument);
		$this->mailAccountMapper->expects($this->never())
			->method('findById');
		$this->mailManager->expects($this->never())
			->method('isPermflagsEnabled');
		$this->migration->expects($this->never())
			->method('migrateImportantOnImap');
		$this->migration->expects($this->never())
			->method('migrateImportantFromDb');

		$this->job->run(self::$argument);
	}

	public function testRunNoAccount() {
		$mailbox = new Mailbox();
		$mailbox->setId(self::$argument['mailboxId']);
		$mailbox->setName('INBOX');
		$mailbox->setAccountId(1);
		$e = new DoesNotExistException('does not exist');

		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(self::$argument['mailboxId'])
			->willReturn($mailbox);
		$this->mailAccountMapper->expects($this->once())
			->method('findById')
			->with($mailbox->getAccountId())
			->willThrowException($e);
		$this->logger->expects($this->once())
			->method('debug')
			->with('Could not find account <' . $mailbox->getAccountId() . '>, removing from jobs');
		$this->jobList->expects($this->once())
			->method('remove')
			->with(MigrateImportantJob::class, self::$argument);
		$this->mailManager->expects($this->never())
			->method('isPermflagsEnabled');
		$this->migration->expects($this->never())
			->method('migrateImportantOnImap');
		$this->migration->expects($this->never())
			->method('migrateImportantFromDb');

		$this->job->run(self::$argument);
	}

	public function testNoPermflags() {
		$mailbox = new Mailbox();
		$mailbox->setId(self::$argument['mailboxId']);
		$mailbox->setName('INBOX');
		$mailbox->setAccountId(1);
		$mailAccount = new MailAccount();
		$account = new Account($mailAccount);

		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(self::$argument['mailboxId'])
			->willReturn($mailbox);
		$this->mailAccountMapper->expects($this->once())
			->method('findById')
			->with($mailbox->getAccountId())
			->willReturn($mailAccount);
		$this->mailManager->expects($this->once())
			->method('isPermflagsEnabled')
			->with($account, $mailbox->getName())
			->willReturn(false);
		$this->logger->expects($this->once())
			->method('debug')
			->with('Permflags not enabled for <' . $mailbox->getAccountId() . '>, removing from jobs');
		$this->jobList->expects($this->once())
			->method('remove')
			->with(MigrateImportantJob::class, self::$argument);

		$this->job->run(self::$argument);
	}

	public function testErrorOnMigrateOnImap() {
		$mailbox = new Mailbox();
		$mailbox->setId(self::$argument['mailboxId']);
		$mailbox->setName('INBOX');
		$mailbox->setAccountId(1);
		$mailAccount = new MailAccount();
		$account = new Account($mailAccount);
		$e = new ServiceException('');

		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(self::$argument['mailboxId'])
			->willReturn($mailbox);
		$this->mailAccountMapper->expects($this->once())
			->method('findById')
			->with($mailbox->getAccountId())
			->willReturn($mailAccount);
		$this->mailManager->expects($this->once())
			->method('isPermflagsEnabled')
			->with($account, $mailbox->getName())
			->willReturn(true);
		$this->migration->expects($this->once())
			->method('migrateImportantOnImap')
			->with($account, $mailbox)
			->willThrowException($e);
		$this->logger->expects($this->once())
			->method('debug')
			->with('Could not flag messages on IMAP for mailbox <' . $mailbox->getId() . '>.');
		$this->migration->expects($this->once())
			->method('migrateImportantFromDb')
			->with($account, $mailbox);
		$this->jobList->expects($this->never())
			->method('remove');

		$this->job->run(self::$argument);
	}

	public function testErrorOnMigrateDbToImap() {
		$mailbox = new Mailbox();
		$mailbox->setId(self::$argument['mailboxId']);
		$mailbox->setName('INBOX');
		$mailbox->setAccountId(1);
		$mailAccount = new MailAccount();
		$account = new Account($mailAccount);
		$e = new ServiceException('');

		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(self::$argument['mailboxId'])
			->willReturn($mailbox);
		$this->mailAccountMapper->expects($this->once())
			->method('findById')
			->with($mailbox->getAccountId())
			->willReturn($mailAccount);
		$this->mailManager->expects($this->once())
			->method('isPermflagsEnabled')
			->with($account, $mailbox->getName())
			->willReturn(true);
		$this->migration->expects($this->once())
			->method('migrateImportantOnImap')
			->with($account, $mailbox);
		$this->migration->expects($this->once())
			->method('migrateImportantFromDb')
			->with($account, $mailbox)
			->willThrowException($e);
		$this->logger->expects($this->once())
			->method('debug')
			->with('Could not flag messages from DB on IMAP for mailbox <' . $mailbox->getId() . '>.');
		$this->jobList->expects($this->never())
			->method('remove');

		$this->job->run(self::$argument);
	}

	public function testErrorOnOnBoth() {
		$mailbox = new Mailbox();
		$mailbox->setId(self::$argument['mailboxId']);
		$mailbox->setName('INBOX');
		$mailbox->setAccountId(1);
		$mailAccount = new MailAccount();
		$account = new Account($mailAccount);
		$e = new ServiceException('');

		$this->mailboxMapper->expects($this->once())
			->method('findById')
			->with(self::$argument['mailboxId'])
			->willReturn($mailbox);
		$this->mailAccountMapper->expects($this->once())
			->method('findById')
			->with($mailbox->getAccountId())
			->willReturn($mailAccount);
		$this->mailManager->expects($this->once())
			->method('isPermflagsEnabled')
			->with($account, $mailbox->getName())
			->willReturn(true);
		$this->migration->expects($this->once())
			->method('migrateImportantOnImap')
			->with($account, $mailbox)
			->willThrowException($e);
		$this->migration->expects($this->once())
			->method('migrateImportantFromDb')
			->with($account, $mailbox)
			->willThrowException($e);
		$this->logger->expects($this->exactly(2))
			->method('debug');
		$this->jobList->expects($this->never())
			->method('remove');

		$this->job->run(self::$argument);
	}
}
