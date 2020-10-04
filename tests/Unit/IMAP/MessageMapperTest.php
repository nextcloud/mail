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
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Model\IMAPMessage;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

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

	public function testGetByIds() {
		/** @var Horde_Imap_Client_Socket|MockObject $imapClient */
		$imapClient = $this->createMock(Horde_Imap_Client_Socket::class);
		$mailbox = 'inbox';
		$ids = [1, 3];

		$fetchResults = new Horde_Imap_Client_Fetch_Results();
		$fetchResult1 = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$fetchResult2 = $this->createMock(Horde_Imap_Client_Data_Fetch::class);
		$imapClient->expects($this->once())
			->method('fetch')
			->willReturn($fetchResults);
		$fetchResults[0] = $fetchResult1;
		$fetchResults[1] = $fetchResult2;
		$fetchResult1->expects($this->once())
			->method('getUid')
			->willReturn(1);
		$fetchResult2->expects($this->once())
			->method('getUid')
			->willReturn(3);
		$message1 = new IMAPMessage($imapClient, $mailbox, 1, $fetchResult1);
		$message2 = new IMAPMessage($imapClient, $mailbox, 3, $fetchResult2);
		$expected = [
			$message1,
			$message2,
		];

		$result = $this->mapper->findByIds($imapClient, $mailbox, $ids);

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
			0
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
				'min' => 123,
				'max' => 321,
				'count' => 50,
			]);
		$query = new Horde_Imap_Client_Fetch_Query();
		$query->uid();
		$uidResults = new Horde_Imap_Client_Fetch_Results();
		$client->expects($this->at(1))
			->method('fetch')
			->with(
				$mailbox,
				$query,
				[
					'ids' => new Horde_Imap_Client_Ids('123:321'),
				]
			)->willReturn($uidResults);
		$bodyResults = new Horde_Imap_Client_Fetch_Results();
		$client->expects($this->at(2))
			->method('fetch')
			->willReturn($bodyResults);

		$this->mapper->findAll(
			$client,
			$mailbox,
			5000,
			0
		);
	}

	public function testFindAllWithKnownUid(): void {
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
				'min' => 123,
				'max' => 321,
				'count' => 50,
			]);
		$query = new Horde_Imap_Client_Fetch_Query();
		$query->uid();
		$uidResults = new Horde_Imap_Client_Fetch_Results();
		$client->expects($this->at(1))
			->method('fetch')
			->with(
				$mailbox,
				$query,
				[
					'ids' => new Horde_Imap_Client_Ids('301:321'),
				]
			)->willReturn($uidResults);
		$bodyResults = new Horde_Imap_Client_Fetch_Results();
		$client->expects($this->at(2))
			->method('fetch')
			->willReturn($bodyResults);

		$this->mapper->findAll(
			$client,
			$mailbox,
			5000,
			300
		);
	}
}
