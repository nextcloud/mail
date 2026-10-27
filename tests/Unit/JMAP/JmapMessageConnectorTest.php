<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Generator;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\JMAP\JmapMessageAdapter;
use OCA\Mail\JMAP\JmapMessageConnector;
use OCA\Mail\Service\JMAP\JmapOperationsService;
use OCA\Mail\Service\Search\SearchQuery;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class JmapMessageConnectorTest extends TestCase {
	private JmapOperationsService&MockObject $jmapOperationsService;
	private MessageMapper&MockObject $dbMessageMapper;
	private MailboxMapper&MockObject $mailboxMapper;
	private JmapMessageConnector $connector;
	private Account&MockObject $account;
	private Mailbox $mailbox;

	protected function setUp(): void {
		parent::setUp();

		$this->jmapOperationsService = $this->createMock(JmapOperationsService::class);
		$this->dbMessageMapper = $this->createMock(MessageMapper::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->account = $this->createMock(Account::class);
		$this->mailbox = new Mailbox();
		$this->mailbox->setId(7);
		$this->mailbox->setRemoteId('mailbox-1');
		$this->mailbox->setSelectable(true);

		$this->connector = new JmapMessageConnector(
			$this->jmapOperationsService,
			$this->createMock(JmapMessageAdapter::class),
			$this->dbMessageMapper,
			$this->mailboxMapper,
			$this->createMock(IEventDispatcher::class),
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testSyncMailboxStoresEveryPage(): void {
		$local = $this->message('m3');
		$local->setUid(5);
		$this->jmapOperationsService->method('entityDelta')
			->with('mailbox-1', '')
			->willReturn(['state' => 'query-state', 'additions' => ['m1', 'm2', 'm3'], 'modifications' => [], 'deletions' => []]);
		$this->jmapOperationsService->method('entityFetchMessagePages')
			->with('m1', 'm2', 'm3')
			->willReturn($this->pages(
				['m1' => $this->message('m1'), 'm2' => $this->message('m2')],
				['m3' => $this->message('m3')],
			));
		$this->dbMessageMapper->method('findHighestUid')->willReturn(10);
		$this->dbMessageMapper->method('findByRemoteIds')
			->willReturnCallback(fn (Mailbox $mailbox, array $ids): array => match ($ids) {
				['m1', 'm2'] => [],
				['m3'] => [$local],
			});
		$inserted = [];
		$this->dbMessageMapper->expects(self::once())
			->method('insertBulk')
			->willReturnCallback(function (Account $account, Message ...$messages) use (&$inserted): void {
				$inserted = $messages;
			});
		$this->dbMessageMapper->expects(self::once())
			->method('updateBulk')
			->with($this->account, true, $local)
			->willReturn([]);
		$this->mailboxMapper->expects(self::once())->method('update')->with($this->mailbox);

		$result = $this->connector->syncMailbox($this->account, $this->mailbox, $this->createMock(LoggerInterface::class), 0);

		$this->assertSame([11, 12], $result->new);
		$this->assertSame([5], $result->modified);
		$this->assertSame('query-state', $result->state);
		$this->assertSame([11, 12], array_map(static fn (Message $message): int => $message->getUid(), $inserted));
		$this->assertSame(7, $inserted[0]->getMailboxId());
	}

	public function testSyncMailboxStoresEachPageBeforeFetchingTheNext(): void {
		$events = [];
		$this->jmapOperationsService->method('entityDelta')
			->willReturn(['state' => 'query-state', 'additions' => ['m1', 'm2'], 'modifications' => [], 'deletions' => []]);
		$this->jmapOperationsService->method('entityFetchMessagePages')
			->willReturnCallback(function () use (&$events): Generator {
				$events[] = 'fetch m1';
				yield ['m1' => $this->message('m1')];
				$events[] = 'fetch m2';
				yield ['m2' => $this->message('m2')];
			});
		$this->dbMessageMapper->method('findByRemoteIds')->willReturn([]);
		$this->dbMessageMapper->expects(self::exactly(2))
			->method('insertBulk')
			->willReturnCallback(function (Account $account, Message ...$messages) use (&$events): void {
				$events[] = 'insert ' . $messages[0]->getRemoteId();
			});

		$this->connector->syncMailbox($this->account, $this->mailbox, $this->createMock(LoggerInterface::class), 0);

		$this->assertSame(['fetch m1', 'insert m1', 'fetch m2', 'insert m2'], $events);
	}

	public function testSyncMailboxWithoutChangesFetchesNothing(): void {
		$this->mailbox->setState('query-state');
		$this->jmapOperationsService->method('entityDelta')
			->willReturn(['state' => 'query-state', 'additions' => [], 'modifications' => [], 'deletions' => []]);
		$this->jmapOperationsService->expects(self::never())->method('entityFetchMessagePages');
		$this->mailboxMapper->expects(self::never())->method('update');

		$result = $this->connector->syncMailbox($this->account, $this->mailbox, $this->createMock(LoggerInterface::class), 0);

		$this->assertSame('query-state', $result->state);
	}

	public function testFindMessagesSearchesEveryIdentifier(): void {
		$found = $this->message('m2');
		$found->setUid(12);
		$this->jmapOperationsService->expects(self::once())
			->method('entityIdentifiers')
			->with('mailbox-1', [])
			->willReturn(['state' => 'query-state', 'list' => ['m1', 'm2']]);
		$this->dbMessageMapper->expects(self::once())
			->method('findByRemoteIds')
			->with($this->mailbox, ['m1', 'm2'])
			->willReturn([$found]);

		$uids = $this->connector->findMessages($this->account, $this->mailbox, new SearchQuery());

		$this->assertSame([12], $uids);
	}

	private function message(string $remoteId): Message {
		$message = new Message();
		$message->setRemoteId($remoteId);
		return $message;
	}

	/**
	 * @param array<string, Message> ...$pages
	 */
	private function pages(array ...$pages): Generator {
		foreach ($pages as $page) {
			yield $page;
		}
	}
}
