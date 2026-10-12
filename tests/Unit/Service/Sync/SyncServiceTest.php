<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Sync;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailboxConnector;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Contracts\IMessageConnector;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\IMAP\MailboxStats;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Search\SearchQuery;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCA\Mail\Service\Sync\SyncService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

final class SyncServiceTest extends TestCase {

	private ProtocolFactory&MockObject $protocolFactory;

	private IMailboxConnector&MockObject $mailboxConnector;

	private IMessageConnector&MockObject $messageConnector;

	/** @var ImapToDbSynchronizer */
	private $synchronizer;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var MailboxSync */
	private $mailboxSync;

	/** @var SyncService */
	private $syncService;

	protected function setUp(): void {
		parent::setUp();

		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->mailboxConnector = $this->createMock(IMailboxConnector::class);
		$this->messageConnector = $this->createMock(IMessageConnector::class);
		$this->synchronizer = $this->createMock(ImapToDbSynchronizer::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->mailboxSync = $this->createMock(MailboxSync::class);

		$this->protocolFactory->method('mailboxConnector')
			->willReturn($this->mailboxConnector);
		$this->protocolFactory->method('messageConnector')
			->willReturn($this->messageConnector);

		$this->syncService = new SyncService(
			$this->protocolFactory,
			$this->synchronizer,
			$this->createStub(FilterStringParser::class),
			$this->messageMapper,
			$this->createStub(PreviewEnhancer::class),
			$this->createStub(\Psr\Log\LoggerInterface::class),
			$this->mailboxSync
		);
	}

	public function testPartialSyncOnUncachedMailbox(): void {
		$account = $this->createStub(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$mailbox->expects($this->once())
			->method('isCached')
			->willReturn(false);

		$this->expectException(MailboxNotCachedException::class);
		$this->syncService->syncMailbox(
			$account,
			$mailbox,
			42,
			true,
			null,
			[],
			'DESC'
		);
	}

	public function testSyncMailboxReturnsFolderStats(): void {
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn('user');
		$mailbox = new Mailbox();
		$mailbox->setMessages(42);
		$mailbox->setUnseen(10);
		$expectedResponse = new Response(
			[],
			[],
			[],
			new MailboxStats(42, 10, null)
		);
		$this->messageMapper
			->method('findUidsForIds')
			->with($mailbox, [])
			->willReturn([]);
		$this->mailboxConnector->expects($this->once())
			->method('syncOne')
			->with($account, $mailbox);
		$this->messageConnector->expects($this->once())
			->method('syncMailbox')
			->with(
				$account,
				$mailbox,
				$this->isInstanceOf(\Psr\Log\LoggerInterface::class),
				0,
				[],
				true
			)
			->willReturn(new \OCA\Mail\Protocol\SyncResult());

		$response = $this->syncService->syncMailbox(
			$account,
			$mailbox,
			0,
			false,
			null,
			[]
		);

		$this->assertEquals($expectedResponse, $response);
	}

	public function testSyncWithoutKnownIdsReturnsOnePageOfNewMessages(): void {
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn('user');
		$mailbox = new Mailbox();
		$mailbox->setMessages(33000);
		$mailbox->setUnseen(24000);
		$this->messageConnector->method('syncMailbox')
			->willReturn(new \OCA\Mail\Protocol\SyncResult());
		$this->messageMapper->expects($this->never())
			->method('findAllIds');
		$this->messageMapper->expects($this->never())
			->method('findNewIds');
		$this->messageMapper->expects($this->once())
			->method('findIdsByQuery')
			->with($mailbox, $this->isInstanceOf(SearchQuery::class), 'DESC', 20)
			->willReturn([3, 2, 1]);
		$this->messageMapper->expects($this->exactly(2))
			->method('findByMailboxAndIds')
			->willReturnCallback(fn (Mailbox $mb, string $userId, array $ids) => array_map(static function (int $id): Message {
				$message = new Message();
				$message->setId($id);
				return $message;
			}, $ids));
		$previewEnhancer = $this->createMock(PreviewEnhancer::class);
		$previewEnhancer->method('process')
			->willReturnArgument(2);
		$syncService = new SyncService(
			$this->protocolFactory,
			$this->synchronizer,
			$this->createStub(FilterStringParser::class),
			$this->messageMapper,
			$previewEnhancer,
			$this->createStub(\Psr\Log\LoggerInterface::class),
			$this->mailboxSync
		);

		$response = $syncService->syncMailbox(
			$account,
			$mailbox,
			0,
			false,
			null,
			[]
		);

		$this->assertSame([3, 2, 1], array_map(static fn (Message $message) => $message->getId(), $response->getNewMessages()));
		$this->assertSame([], $response->getChangedMessages());
	}

	public function testSyncWithoutKnownIdsReturnsTheFirstPageOldestFirst(): void {
		$mailbox = $this->mailbox();
		$this->messageMapper->expects($this->never())
			->method('findNewIds');
		$this->messageMapper->expects($this->once())
			->method('findIdsByQuery')
			->with($mailbox, $this->isInstanceOf(SearchQuery::class), IMailSearch::ORDER_OLDEST_FIRST, 20)
			->willReturn([1, 2, 3]);

		$response = $this->syncServiceWith($this->createStub(FilterStringParser::class))
			->syncMailbox($this->accountFor('user'), $mailbox, 0, false, null, [], IMailSearch::ORDER_OLDEST_FIRST);

		$this->assertSame([1, 2, 3], self::ids($response->getNewMessages()));
	}

	public function testSyncWithoutKnownIdsAndAQueryReturnsTheFirstPageOfMatches(): void {
		$mailbox = $this->mailbox();
		$query = new SearchQuery();
		$parser = $this->createMock(FilterStringParser::class);
		$parser->expects($this->once())
			->method('parse')
			->with('is:pi-important')
			->willReturn($query);
		$this->messageMapper->expects($this->never())
			->method('findNewIds');
		$calls = [];
		$this->messageMapper->method('findIdsByQuery')
			->willReturnCallback(function (Mailbox $mb, SearchQuery $q, string $order, ?int $limit, ?array $uids = null, ?array $ids = null) use (&$calls, $query): array {
				$this->assertSame($query, $q);
				$calls[] = [$order, $limit, $ids];
				return $ids === null ? [9, 8] : [];
			});

		$response = $this->syncServiceWith($parser)
			->syncMailbox($this->accountFor('user'), $mailbox, 0, false, null, [], IMailSearch::ORDER_NEWEST_FIRST, 'is:pi-important');

		$this->assertSame([
			[IMailSearch::ORDER_NEWEST_FIRST, 20, null], // the first page of matches
			[IMailSearch::ORDER_NEWEST_FIRST, null, []], // which known messages still match: none known
		], $calls);
		$this->assertSame([9, 8], self::ids($response->getNewMessages()));
		$this->assertSame([], $response->getChangedMessages());
	}

	public function testSyncWithKnownIdsAndAQueryFiltersTheNewIdsOldestFirst(): void {
		$mailbox = $this->mailbox();
		$query = new SearchQuery();
		$parser = $this->createStub(FilterStringParser::class);
		$parser->method('parse')
			->willReturn($query);
		$this->messageMapper->expects($this->once())
			->method('findNewIds')
			->with($mailbox, [4, 5], 1700000000, IMailSearch::ORDER_OLDEST_FIRST)
			->willReturn([6, 7]);
		$calls = [];
		$this->messageMapper->method('findIdsByQuery')
			->willReturnCallback(function (Mailbox $mb, SearchQuery $q, string $order, ?int $limit, ?array $uids = null, ?array $ids = null) use (&$calls): array {
				$calls[] = [$order, $limit, $ids];
				return $ids === [6, 7] ? [7] : [4];
			});

		$response = $this->syncServiceWith($parser)
			->syncMailbox($this->accountFor('user'), $mailbox, 0, false, 1700000000, [4, 5], IMailSearch::ORDER_OLDEST_FIRST, 'is:pi-other');

		$this->assertSame([
			[IMailSearch::ORDER_OLDEST_FIRST, null, [6, 7]], // new messages that match
			[IMailSearch::ORDER_OLDEST_FIRST, null, [4, 5]], // known messages that still match
		], $calls);
		$this->assertSame([7], self::ids($response->getNewMessages()));
		$this->assertSame([4], self::ids($response->getChangedMessages()));
		$this->assertSame([5], $response->getVanishedMessageUids());
	}

	private function mailbox(): Mailbox {
		$mailbox = new Mailbox();
		$mailbox->setMessages(100);
		$mailbox->setUnseen(10);
		return $mailbox;
	}

	/** @param list<Message> $messages */
	private static function ids(array $messages): array {
		return array_map(static fn (Message $message) => $message->getId(), $messages);
	}

	private function accountFor(string $userId): Account {
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn($userId);
		return $account;
	}

	/** A sync service whose database and IMAP side return the messages asked for, unchanged. */
	private function syncServiceWith(FilterStringParser $parser): SyncService {
		$this->messageConnector->method('syncMailbox')
			->willReturn(new \OCA\Mail\Protocol\SyncResult());
		$this->messageMapper->method('findByMailboxAndIds')
			->willReturnCallback(fn (Mailbox $mb, string $userId, array $ids) => array_map(static function (int $id): Message {
				$message = new Message();
				$message->setId($id);
				return $message;
			}, $ids));
		$previewEnhancer = $this->createMock(PreviewEnhancer::class);
		$previewEnhancer->method('process')
			->willReturnArgument(2);
		return new SyncService(
			$this->protocolFactory,
			$this->synchronizer,
			$parser,
			$this->messageMapper,
			$previewEnhancer,
			$this->createStub(\Psr\Log\LoggerInterface::class),
			$this->mailboxSync
		);
	}
}
