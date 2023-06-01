<?php

declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCA\Mail\Tests\Service;

use ChristophWurst\KItinerary\Itinerary;
use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Socket;
use OC\Memcache\ArrayCache;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Integration\KItinerary\ItineraryExtractor;
use OCA\Mail\Service\ItineraryService;
use OCP\ICache;
use OCP\ICacheFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class ItineraryServiceTest extends TestCase {
	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var ItineraryExtractor|MockObject */
	private $itineraryExtractor;

	private ICache $cache;

	/** @var ItineraryService */
	private $service;

	protected function setUp(): void {
		parent::setUp();

		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->itineraryExtractor = $this->createMock(ItineraryExtractor::class);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = new ArrayCache('itinerary_test');

		$cacheFactory->method('createLocal')
			->willReturn($this->cache);

		$this->service = new ItineraryService(
			$this->imapClientFactory,
			$this->messageMapper,
			$this->itineraryExtractor,
			$cacheFactory,
			new NullLogger(),
		);
	}

	public function testExtractNoBodyNoAttachments() {
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('1');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');

		$this->itineraryExtractor->expects($this->once())
			->method('extract')
			->willReturn(new Itinerary());

		$itinerary = $this->service->extract($account, $mailbox, 13);

		$this->assertEquals([], $itinerary->jsonSerialize());
	}

	public function testExtractFromBody() {
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('1');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');

		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$body = '<html><body>hello</body></html>';
		$this->messageMapper->expects($this->once())
			->method('getHtmlBody')
			->with($client, 'INBOX', 13)
			->willReturn($body);
		$this->itineraryExtractor->expects($this->exactly(2))
			->method('extract')
			->withConsecutive([$body], ['["datafrombody"]'])
			->willReturn(new Itinerary(['datafrombody']));

		$itinerary = $this->service->extract($account, $mailbox, 13);

		$this->assertEquals(['datafrombody'], $itinerary->jsonSerialize());
	}

	public function testExtractFromAttachments() {
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('1');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');

		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$pdf = '%PDF-1.3.%';
		$this->messageMapper->expects($this->once())
			->method('getRawAttachments')
			->with($client, 'INBOX', 13)
			->willReturn([$pdf]);
		$this->itineraryExtractor->expects($this->exactly(2))
			->method('extract')
			->withConsecutive([$pdf], ['["datafrompdf"]'])
			->willReturn(new Itinerary(['datafrompdf']));

		$itinerary = $this->service->extract($account, $mailbox, 13);

		$this->assertEquals(['datafrompdf'], $itinerary->jsonSerialize());
	}

	public function testGetCachedMiss(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('1');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setName('FooBar');

		$result = $this->service->getCached(
			$account,
			$mailbox,
			2
		);

		$this->assertNull($result);
	}

	public function testGetCached(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('1');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setName('FooBar');

		$this->cache->set('100_FooBar_1', json_encode(['cacheddatafrompdf']));

		$result = $this->service->getCached(
			$account,
			$mailbox,
			1
		);

		$this->assertEquals(1, $result->count());
	}
}
