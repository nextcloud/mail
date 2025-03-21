<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Fetch_Results;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Search_Query;
use Horde_Imap_Client_Socket;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\IMAP\Charset\Converter;
use OCA\Mail\IMAP\ImapMessageFetcher;
use OCA\Mail\IMAP\ImapMessageFetcherFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\SmimeService;
use OCA\Mail\Support\PerformanceLoggerTask;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use function range;

class MessageMapperTest extends TestCase {
	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var MessageMapper */
	private $mapper;

	/** @var SmimeService|MockObject */
	private $sMimeService;

	/** @var ImapMessageFetcherFactory|MockObject */
	private $imapMessageFactory;

	private Converter|MockObject $converter;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);
		$this->sMimeService = $this->createMock(SmimeService::class);
		$this->imapMessageFactory = $this->createMock(ImapMessageFetcherFactory::class);
		$this->converter = $this->createMock(Converter::class);

		$this->mapper = new MessageMapper(
			$this->logger,
			$this->sMimeService,
			$this->imapMessageFactory,
			$this->converter,
		);
	}

	public function testGetByIds(): void {
		/** @var Horde_Imap_Client_Socket|MockObject $imapClient */
		$imapClient = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'inbox';
		$ids = [1, 3];
		$userId = 'user';
		$loadBody = false;
		$runPhishingCheck = false;

		$imapMessageFetcher1 = $this->createMock(ImapMessageFetcher::class);
		$imapMessageFetcher2 = $this->createMock(ImapMessageFetcher::class);
		$message1 = $this->createMock(IMAPMessage::class);
		$message2 = $this->createMock(IMAPMessage::class);

		$fetchResults = new Horde_Imap_Client_Fetch_Results();
		$fetchResult1 = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$fetchResult2 = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$imapClient->expects(self::once())
			->method('fetch')
			->willReturn($fetchResults);
		$fetchResults[0] = $fetchResult1;
		$fetchResults[1] = $fetchResult2;
		$fetchResult1->expects(self::once())
			->method('exists')
			->with(Horde_Imap_Client::FETCH_ENVELOPE)
			->willReturn(true);
		$fetchResult2->expects(self::once())
			->method('exists')
			->with(Horde_Imap_Client::FETCH_ENVELOPE)
			->willReturn(true);
		$fetchResult1->method('getUid')
			->willReturn(1);
		$fetchResult2->method('getUid')
			->willReturn(3);
		$this->imapMessageFactory->expects(self::exactly(2))
			->method('build')
			->willReturnMap([
				[1, $mailbox, $imapClient, $userId, $imapMessageFetcher1],
				[3, $mailbox, $imapClient, $userId, $imapMessageFetcher2],
			]);

		$imapMessageFetcher1->expects(self::once())
			->method('withBody')
			->with($loadBody)
			->willReturnSelf();
		$imapMessageFetcher1->expects(self::once())
			->method('withPhishingCheck')
			->with($runPhishingCheck)
			->willReturnSelf();
		$imapMessageFetcher1->expects(self::once())
			->method('fetchMessage')
			->with($fetchResult1)
			->willReturn($message1);
		$imapMessageFetcher2->expects(self::once())
			->method('withBody')
			->with($loadBody)
			->willReturnSelf();
		$imapMessageFetcher2->expects(self::once())
			->method('withPhishingCheck')
			->with($runPhishingCheck)
			->willReturnSelf();
		$imapMessageFetcher2->expects(self::once())
			->method('fetchMessage')
			->with($fetchResult2)
			->willReturn($message2);

		$expected = [
			$message1,
			$message2,
		];

		$result = $this->mapper->findByIds(
			$imapClient,
			$mailbox,
			new Horde_Imap_Client_Ids($ids),
			$userId,
			$loadBody
		);

		$this->assertEquals($expected, $result);
	}

	public function testGetByIdsWithManyMessages(): void {
		/** @var Horde_Imap_Client_Socket|MockObject $imapClient */
		$imapClient = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'inbox';
		$ids = range(1, 10000, 2);
		$userId = 'user';
		$loadBody = false;
		$fetchResults = new Horde_Imap_Client_Fetch_Results();
		$fetchResult1 = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$fetchResult2 = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$imapClient->expects(self::exactly(3))
			->method('fetch')
			->willReturnOnConsecutiveCalls(
				$fetchResults,
				$fetchResults,
				$fetchResults
			);
		$fetchResults[0] = $fetchResult1;
		$fetchResults[1] = $fetchResult2;
		$fetchResult1->method('getUid')
			->willReturn(1);
		$fetchResult2->method('getUid')
			->willReturn(3);

		$this->mapper->findByIds(
			$imapClient,
			$mailbox,
			$ids,
			$userId,
			$loadBody
		);
	}

	public function testGetByIdsWithEmpty(): void {
		/** @var Horde_Imap_Client_Socket|MockObject $imapClient */
		$imapClient = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'inbox';
		$ids = [1, 3];
		$userId = 'user';
		$loadBody = false;
		$runPhishingCheck = false;

		$imapMessageFetcher1 = $this->createMock(ImapMessageFetcher::class);
		$message1 = $this->createMock(IMAPMessage::class);

		$fetchResults = new Horde_Imap_Client_Fetch_Results();
		$fetchResult1 = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$fetchResult2 = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$imapClient->expects(self::once())
			->method('fetch')
			->willReturn($fetchResults);
		$fetchResults[0] = $fetchResult1;
		$fetchResults[1] = $fetchResult2;
		$fetchResult1->expects(self::once())
			->method('exists')
			->with(Horde_Imap_Client::FETCH_ENVELOPE)
			->willReturn(true);
		$fetchResult2->expects(self::once())
			->method('exists')
			->with(Horde_Imap_Client::FETCH_ENVELOPE)
			->willReturn(false);
		$fetchResult1->method('getUid')
			->willReturn(1);
		$fetchResult2->expects(self::never())
			->method('getUid');
		$this->imapMessageFactory->expects(self::once())
			->method('build')
			->willReturnMap([
				[1, $mailbox, $imapClient, $userId, $imapMessageFetcher1],
			]);

		$imapMessageFetcher1->expects(self::once())
			->method('withBody')
			->with($loadBody)
			->willReturnSelf();
		$imapMessageFetcher1->expects(self::once())
			->method('withPhishingCheck')
			->with($runPhishingCheck)
			->willReturnSelf();
		$imapMessageFetcher1->expects(self::once())
			->method('fetchMessage')
			->with($fetchResult1)
			->willReturn($message1);

		$expected = [
			$message1
		];

		$result = $this->mapper->findByIds(
			$imapClient,
			$mailbox,
			new Horde_Imap_Client_Ids($ids),
			$userId,
			$loadBody
		);

		$this->assertEquals($expected, $result);
	}

	public function testFindAllEmptyMailbox(): void {
		/** @var Horde_Imap_Client_Socket|MockObject $client */
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'inbox';
		$client->expects($this->once())
			->method('search')
			->with(
				$mailbox,
				null,
				[
					'results' => [
						Horde_Imap_Client::SEARCH_RESULTS_MIN,
						Horde_Imap_Client::SEARCH_RESULTS_MAX,
						Horde_Imap_Client::SEARCH_RESULTS_COUNT,
					]
				]
			)
			->willReturn([
				'min' => 0,
				'max' => 0,
				'count' => 0,
			]);
		$client->expects($this->never())
			->method('fetch');

		$result = $this->mapper->findAll(
			$client,
			$mailbox,
			5000,
			0,
			$this->createMock(LoggerInterface::class),
			$this->createMock(PerformanceLoggerTask::class),
			'user'
		);

		$this->assertSame(
			[
				'messages' => [],
				'all' => true,
				'total' => 0,
			],
			$result
		);
	}

	public function testFindAllNoKnownUid(): void {
		/** @var Horde_Imap_Client_Socket|MockObject $client */
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'inbox';
		$rangeSearchQuery = new Horde_Imap_Client_Search_Query();
		$rangeSearchQuery->ids(new Horde_Imap_Client_Ids('123:321'));
		$client->expects(self::exactly(2))
			->method('search')
			->withConsecutive(
				[
					$mailbox,
					null,
					[
						'results' => [
							Horde_Imap_Client::SEARCH_RESULTS_MIN,
							Horde_Imap_Client::SEARCH_RESULTS_MAX,
							Horde_Imap_Client::SEARCH_RESULTS_COUNT,
						]
					]
				],
				[
					$mailbox,
					($rangeSearchQuery),
					[
						'results' => [
							Horde_Imap_Client::SEARCH_RESULTS_COUNT,
						]
					],
				],
			)
			->willReturnOnConsecutiveCalls(
				[
					'min' => 123,
					'max' => 321,
					'count' => 50,
				],
				[
					'count' => 50,
				],
			);
		$query = new Horde_Imap_Client_Fetch_Query();
		$query->uid();
		$uidResults = new Horde_Imap_Client_Fetch_Results();
		foreach (range(123, 321) as $i) {
			$uid = new Horde_Imap_Client_Data_Fetch();
			$uid->setUid($i);
			$uidResults[$i] = $uid;
		}
		$bodyResults = new Horde_Imap_Client_Fetch_Results();
		$client->expects(self::exactly(2))
			->method('fetch')
			->withConsecutive(
				[
					$mailbox,
					$query,
					[
						'ids' => new Horde_Imap_Client_Ids('123:321'),
					]
				],
				[
					$mailbox,
					self::anything(),
					self::anything()
				]
			)
			->willReturnOnConsecutiveCalls(
				$uidResults,
				$bodyResults
			);

		$result = $this->mapper->findAll(
			$client,
			$mailbox,
			5000,
			0,
			$this->createMock(LoggerInterface::class),
			$this->createMock(PerformanceLoggerTask::class),
			'user'
		);

		self::assertTrue($result['all']);
	}

	public function testFindAllWithKnownUid(): void {
		/** @var Horde_Imap_Client_Socket|MockObject $client */
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'inbox';
		$rangeSearchQuery = new Horde_Imap_Client_Search_Query();
		$rangeSearchQuery->ids(new Horde_Imap_Client_Ids('301:321'));
		$client->expects(self::exactly(2))
			->method('search')
			->withConsecutive(
				[
					$mailbox,
					null,
					[
						'results' => [
							Horde_Imap_Client::SEARCH_RESULTS_MIN,
							Horde_Imap_Client::SEARCH_RESULTS_MAX,
							Horde_Imap_Client::SEARCH_RESULTS_COUNT,
						],
					],
				],
				[
					$mailbox,
					$rangeSearchQuery,
					[
						'results' => [
							Horde_Imap_Client::SEARCH_RESULTS_COUNT,
						],
					],
				],
			)
			->willReturnOnConsecutiveCalls(
				[
					'min' => 123,
					'max' => 321,
					'count' => 50,
				],
				[
					'count' => 50,
				],
			);
		$query = new Horde_Imap_Client_Fetch_Query();
		$query->uid();
		$uidResults = new Horde_Imap_Client_Fetch_Results();
		foreach (range(123, 321) as $i) {
			$uid = new Horde_Imap_Client_Data_Fetch();
			$uid->setUid($i);
			$uidResults[$i] = $uid;
		}
		$bodyResults = new Horde_Imap_Client_Fetch_Results();
		$client->expects(self::exactly(2))
			->method('fetch')
			->withConsecutive(
				[
					$mailbox,
					$query,
					[
						'ids' => new Horde_Imap_Client_Ids('301:321'),
					]
				],
				[
					$mailbox,
					self::anything(),
					self::anything()
				]
			)
			->willReturnOnConsecutiveCalls(
				$uidResults,
				$bodyResults
			);

		$result = $this->mapper->findAll(
			$client,
			$mailbox,
			5000,
			300,
			$this->createMock(LoggerInterface::class),
			$this->createMock(PerformanceLoggerTask::class),
			'user'
		);

		self::assertTrue($result['all']);
	}

	/**
	 * Assume we have a large mailbox with many messages spread across a wide
	 * range of UIDs. This inbox is fetched in chunks and the chunks might be
	 * fragmented in many way. One edge case is that the last estimated chunk
	 * already reached the upper UID but there are too many messages to fetch,
	 * so the process will stop before that.
	 */
	public function testFindAllPackedLastChunk(): void {
		/** @var Horde_Imap_Client_Socket|MockObject $client */
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'inbox';
		$rangeSearchQuery = new Horde_Imap_Client_Search_Query();
		$rangeSearchQuery->ids(new Horde_Imap_Client_Ids('92001:99999'));
		$client->expects(self::exactly(2))
			->method('search')
			->withConsecutive(
				[
					$mailbox,
					null,
					[
						'results' => [
							Horde_Imap_Client::SEARCH_RESULTS_MIN,
							Horde_Imap_Client::SEARCH_RESULTS_MAX,
							Horde_Imap_Client::SEARCH_RESULTS_COUNT,
						],
					],
				],
				[
					$mailbox,
					$rangeSearchQuery,
					[
						'results' => [
							Horde_Imap_Client::SEARCH_RESULTS_COUNT,
						],
					],
				],
			)
			->willReturnOnConsecutiveCalls(
				[
					'min' => 10000,
					'max' => 99999,
					'count' => 50000,
				],
				[
					'count' => 50,
				],
			);
		$query = new Horde_Imap_Client_Fetch_Query();
		$query->uid();
		$uidResults = new Horde_Imap_Client_Fetch_Results();
		foreach (range(92000, 98000) as $i) {
			$uid = new Horde_Imap_Client_Data_Fetch();
			$uid->setUid($i);
			$uidResults[$i] = $uid;
		}
		$bodyResults = new Horde_Imap_Client_Fetch_Results();
		$client->expects(self::exactly(2))
			->method('fetch')
			->withConsecutive(
				[
					$mailbox,
					$query,
					[
						'ids' => new Horde_Imap_Client_Ids('92001:99999'),
					]
				],
				[
					$mailbox,
					self::anything(),
					self::anything()
				]
			)
			->willReturnOnConsecutiveCalls(
				$uidResults,
				$bodyResults
			);

		$result = $this->mapper->findAll(
			$client,
			$mailbox,
			5000,
			92000,
			$this->createMock(LoggerInterface::class),
			$this->createMock(PerformanceLoggerTask::class),
			'user'
		);

		// This chunk returns 8k messages, when we only expected 5k. So the process
		// isn't done and the client has to fetch again.
		self::assertFalse($result['all']);
	}

	public function testFindAllNoUidCandidates(): void {
		/** @var Horde_Imap_Client_Socket|MockObject $client */
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'inbox';
		$client->expects(self::once())
			->method('search')
			->with(
				$mailbox,
				null,
				[
					'results' => [
						Horde_Imap_Client::SEARCH_RESULTS_MIN,
						Horde_Imap_Client::SEARCH_RESULTS_MAX,
						Horde_Imap_Client::SEARCH_RESULTS_COUNT,
					]
				]
			)
			->willReturn([
				'min' => 1,
				'max' => 35791,
				'count' => 32122,
			]);
		$query = new Horde_Imap_Client_Fetch_Query();
		$query->uid();
		$client->expects(self::never())
			->method('fetch');

		$result = $this->mapper->findAll(
			$client,
			$mailbox,
			5000,
			99999,
			$this->createMock(LoggerInterface::class),
			$this->createMock(PerformanceLoggerTask::class),
			'user'
		);

		self::assertTrue($result['all']);
		self::assertEmpty($result['messages']);
	}

	public function testGetFlagged() {
		/** @var Horde_Imap_Client_Socket|MockObject $imapClient */
		$imapClient = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = new Mailbox();
		$mailbox->setName('inbox');
		$flag = '$label1';
		$idsObject = new Horde_Imap_Client_Ids(1);

		$searchResult = [
			'count' => 1,
			'match' => $idsObject,
			'max' => 1,
			'min' => 1,
			'relevancy' => []
		];

		$imapClient->expects($this->once())
			->method('search')
			->willReturn($searchResult);

		$result = $this->mapper->getFlagged($imapClient, $mailbox, $flag);
		$this->assertEquals($result, [1]);
	}

	public function testGetFlaggedNoMatches() {
		/** @var Horde_Imap_Client_Socket|MockObject $imapClient */
		$imapClient = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = new Mailbox();
		$mailbox->setName('inbox');
		$flag = '$label1';

		$searchResult = [
			'count' => 0,
			'match' => [],
			'max' => 0,
			'min' => 0,
			'relevancy' => []
		];

		$imapClient->expects($this->once())
			->method('search')
			->willReturn($searchResult);

		$result = $this->mapper->getFlagged($imapClient, $mailbox, $flag);
		$this->assertEquals($result, []);
	}

	public function testGetFlaggedSearchResultUnexpectedStructure() {
		/** @var Horde_Imap_Client_Socket|MockObject $imapClient */
		$imapClient = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = new Mailbox();
		$mailbox->setName('inbox');
		$flag = '$label1';

		$searchResult = [[]];

		$imapClient->expects($this->once())
			->method('search')
			->willReturn($searchResult);

		$result = $this->mapper->getFlagged($imapClient, $mailbox, $flag);
		$this->assertEquals($result, []);
	}

	/**
	 * This test ensures that we correctly identify iMIP messages from various
	 * sources as valid iMIP messages. The test cases are based on original
	 * iMIP messages from different vendors. The focus is on the MIME message
	 * structure and verifying that we traverse the MIME tree properly.
	 *
	 * @dataProvider isImipMessageProvider
	 */
	public function testGetBodyStructureIsImipMessage(string $filename, bool $expected): void {
		$text = file_get_contents(__DIR__ . '/../../data/imip/' . $filename . '.txt');
		$part = \Horde_Mime_Part::parseMessage($text);

		$fetchData = new Horde_Imap_Client_Data_Fetch();
		$fetchData->setStructure($part);
		$fetchData->setUid(100);

		$fetchResult = new Horde_Imap_Client_Fetch_Results();
		$fetchResult[0] = $fetchData;

		$imapClient = $this->createMock(Horde_Imap_Client_Socket::class);
		$imapClient->method('fetch')
			->willReturn($fetchResult);

		$data = $this->mapper->getBodyStructureData(
			$imapClient,
			'INBOX',
			[100],
			'alice@example.org'
		);

		$this->assertCount(1, $data);
		$this->assertEquals($expected, $data[0]->isImipMessage());
	}

	public function isImipMessageProvider(): array {
		return [
			'google request' => ['request_google', true],
			'outlook.com request' => ['request_outlook_com', true],
		];
	}
}
