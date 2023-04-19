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

namespace OCA\Mail\Tests\Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Fetch_Results;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Socket;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Support\PerformanceLoggerTask;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use function range;

class MessageMapperTest extends TestCase {
	/** @var LoggerInterface|MockObject */
	private $logger;

	/** @var MessageMapper */
	private $mapper;

	protected function setUp(): void {
		parent::setUp();

		$this->logger = $this->createMock(LoggerInterface::class);

		$this->mapper = new MessageMapper(
			$this->logger
		);
	}

	public function testGetByIds(): void {
		/** @var Horde_Imap_Client_Socket|MockObject $imapClient */
		$imapClient = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'inbox';
		$ids = [1, 3];

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

		$message1 = new IMAPMessage($imapClient, $mailbox, 1, $fetchResult1);
		$message2 = new IMAPMessage($imapClient, $mailbox, 3, $fetchResult2);
		$expected = [
			$message1,
			$message2,
		];

		$result = $this->mapper->findByIds($imapClient, $mailbox, new Horde_Imap_Client_Ids($ids));

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
			$loadBody
		);
	}

	public function testGetByIdsWithEmpty(): void {
		/** @var Horde_Imap_Client_Socket|MockObject $imapClient */
		$imapClient = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'inbox';
		$ids = [1, 3];

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

		$message1 = new IMAPMessage($imapClient, $mailbox, 1, $fetchResult1);
		$expected = [
			$message1
		];

		$result = $this->mapper->findByIds($imapClient, $mailbox, new Horde_Imap_Client_Ids($ids));

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
			$this->createMock(PerformanceLoggerTask::class)
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
				'min' => 123,
				'max' => 321,
				'count' => 50,
			]);
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
			$this->createMock(PerformanceLoggerTask::class)
		);

		self::assertTrue($result['all']);
	}

	public function testFindAllWithKnownUid(): void {
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
				'min' => 123,
				'max' => 321,
				'count' => 50,
			]);
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
			$this->createMock(PerformanceLoggerTask::class)
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
				'min' => 10000,
				'max' => 99999,
				'count' => 50000,
			]);
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
			$this->createMock(PerformanceLoggerTask::class)
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
			$this->createMock(PerformanceLoggerTask::class)
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
}
