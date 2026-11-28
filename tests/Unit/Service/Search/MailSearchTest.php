<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Search;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\MailboxLockedException;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Search\Flag;
use OCA\Mail\Service\Search\MailSearch;
use OCA\Mail\Service\Search\SearchQuery;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;

final class MailSearchTest extends TestCase {
	/** @var FilterStringParser|MockObject */
	private $filterStringParser;

	/** @var MailSearch */
	private $search;

	/** @var MailManager|MockObject */
	private $mailManager;

	/** @var PreviewEnhancer|MockObject */
	private $previewEnhancer;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var ITimeFactory|MockObject */
	private $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->filterStringParser = $this->createMock(FilterStringParser::class);
		$this->mailManager = $this->createMock(MailManager::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->previewEnhancer = $this->createMock(PreviewEnhancer::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->search = new MailSearch(
			$this->filterStringParser,
			$this->mailManager,
			$this->messageMapper,
			$this->previewEnhancer,
			$this->timeFactory
		);
	}

	public function testFindMessagesNotCached(): void {
		$account = $this->createStub(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setSyncNewToken('abc');
		$mailbox->setSyncChangedToken('def');
		$this->expectException(MailboxNotCachedException::class);

		$this->search->findMessages(
			$account,
			$mailbox,
			'DESC',
			null,
			null,
			null,
			null,
			null
		);
	}

	public function testFindMessagesLocked(): void {
		$account = $this->createStub(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setSyncNewLock(123);
		$this->expectException(MailboxLockedException::class);

		$this->search->findMessages(
			$account,
			$mailbox,
			'DESC',
			null,
			null,
			null,
			null,
			null,
		);
	}

	public function testNoFindMessages(): void {
		$account = $this->createMock(Account::class);
		$account->expects($this->once())
			->method('getUserId')
			->willReturn('admin');
		$mailbox = new Mailbox();
		$mailbox->setSyncNewToken('abc');
		$mailbox->setSyncChangedToken('def');
		$mailbox->setSyncVanishedToken('ghi');

		$messages = $this->search->findMessages(
			$account,
			$mailbox,
			'DESC',
			null,
			null,
			null,
			null,
			null
		);

		$this->assertEmpty($messages);
	}

	public function testFindFlagsLocally(): void {
		$account = $this->createMock(Account::class);
		$account->expects($this->once())
			->method('getUserId')
			->willReturn('admin');
		$mailbox = new Mailbox();
		$mailbox->setSyncNewToken('abc');
		$mailbox->setSyncChangedToken('def');
		$mailbox->setSyncVanishedToken('ghi');
		$query = new SearchQuery();
		$query->addFlag(Flag::is(Flag::SEEN));
		$this->filterStringParser->expects($this->once())
			->method('parse')
			->with('my search')
			->willReturn($query);
		$this->messageMapper->expects($this->once())
			->method('findByIds')
			->willReturn([
				$this->createMock(Message::class),
				$this->createMock(Message::class),
			]);
		$this->messageMapper->expects($this->once())
			->method('findThreadUnreadState')
			->willReturnArgument(0);
		$this->mailManager->expects($this->never())
			->method('findMessages');
		$this->previewEnhancer->expects($this->once())
			->method('process')
			->willReturnArgument(2);

		$messages = $this->search->findMessages(
			$account,
			$mailbox,
			'DESC',
			'my search',
			null,
			null,
			null,
			null
		);

		$this->assertCount(2, $messages);
	}

	public function testFindText(): void {
		$account = $this->createMock(Account::class);
		$account->expects($this->once())
			->method('getUserId')
			->willReturn('admin');
		$mailbox = new Mailbox();
		$mailbox->setSyncNewToken('abc');
		$mailbox->setSyncChangedToken('def');
		$mailbox->setSyncVanishedToken('ghi');
		$query = new SearchQuery();
		$query->addBody('my');
		$query->addBody('search');
		$this->filterStringParser->expects($this->once())
			->method('parse')
			->with('my search')
			->willReturn($query);
		$this->mailManager->expects($this->once())
			->method('findMessages')
			->with($account, $mailbox, $query)
			->willReturn([2, 3]);
		$this->messageMapper->expects($this->once())
			->method('findByIds')
			->willReturn([
				$this->createMock(Message::class),
				$this->createMock(Message::class),
			]);
		$this->messageMapper->expects($this->once())
			->method('findThreadUnreadState')
			->willReturnArgument(0);
		$this->previewEnhancer->expects($this->once())
			->method('process')
			->willReturnArgument(2);

		$messages = $this->search->findMessages(
			$account,
			$mailbox,
			'DESC',
			'my search',
			null,
			null,
			null,
			null
		);

		$this->assertCount(2, $messages);
	}

	public function testFindMessagesFlagsThreadsWithUnreadMessagesInTheThreadedView(): void {
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn('admin');
		$mailbox = new Mailbox();
		$mailbox->setSyncNewToken('abc');
		$mailbox->setSyncChangedToken('def');
		$mailbox->setSyncVanishedToken('ghi');
		$this->filterStringParser->method('parse')->willReturn(new SearchQuery());
		$found = [$this->createMock(Message::class)];
		$flagged = [$this->createMock(Message::class), $this->createMock(Message::class)];
		$this->messageMapper->method('findByIds')->willReturn($found);
		$this->messageMapper->expects($this->once())
			->method('findThreadUnreadState')
			->with($found)
			->willReturn($flagged);
		$this->previewEnhancer->expects($this->once())
			->method('process')
			->with($account, $mailbox, $flagged)
			->willReturnArgument(2);

		$messages = $this->search->findMessages($account, $mailbox, 'DESC', null, null, null, null, IMailSearch::VIEW_THREADED);

		$this->assertSame($flagged, $messages);
	}

	public function testFindMessagesDoesNotLookUpThreadsInTheSingletonView(): void {
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn('admin');
		$mailbox = new Mailbox();
		$mailbox->setSyncNewToken('abc');
		$mailbox->setSyncChangedToken('def');
		$mailbox->setSyncVanishedToken('ghi');
		$this->filterStringParser->method('parse')->willReturn(new SearchQuery());
		$found = [$this->createMock(Message::class)];
		$this->messageMapper->method('findByIds')->willReturn($found);
		$this->messageMapper->expects($this->never())
			->method('findThreadUnreadState');
		$this->previewEnhancer->method('process')->willReturnArgument(2);

		$messages = $this->search->findMessages($account, $mailbox, 'DESC', null, null, null, null, IMailSearch::VIEW_SINGLETON);

		$this->assertSame($found, $messages);
	}
}
