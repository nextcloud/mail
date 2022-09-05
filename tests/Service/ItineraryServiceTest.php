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
use OCA\Mail\Account;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Integration\KItinerary\ItineraryExtractor;
use OCA\Mail\Service\ItineraryService;
use OCP\ICacheFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class ItineraryServiceTest extends TestCase {
	/** @var IMAPClientFactory|MockObject */
	private $imapClientFactory;

	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var ItineraryExtractor|MockObject */
	private $itineraryExtractor;

	/** @var ICacheFactory|MockObject */
	private $cacheFactor;

	/** @var ItineraryService */
	private $service;

	protected function setUp(): void {
		parent::setUp();

		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->itineraryExtractor = $this->createMock(ItineraryExtractor::class);
		$this->cacheFactor = $this->createMock(ICacheFactory::class);

		$this->service = new ItineraryService(
			$this->imapClientFactory,
			$this->mailboxMapper,
			$this->messageMapper,
			$this->itineraryExtractor,
			$this->cacheFactor,
			$this->createMock(LoggerInterface::class)
		);
	}

	public function testExtractNoBodyNoAttachments() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
		$this->itineraryExtractor->expects($this->once())
			->method('extract')
			->willReturn(new Itinerary());

		$itinerary = $this->service->extract($account, 'INBOX', 13);

		$this->assertEquals([], $itinerary->jsonSerialize());
	}

	public function testExtractFromBody() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
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
		$this->itineraryExtractor->expects($this->at(0))
			->method('extract')
			->with($body)
			->willReturn(new Itinerary(['datafrombody']));
		$this->itineraryExtractor->expects($this->at(1))
			->method('extract')
			->with('["datafrombody"]')
			->willReturn(new Itinerary(['datafrombody']));

		$itinerary = $this->service->extract($account, 'INBOX', 13);

		$this->assertEquals(['datafrombody'], $itinerary->jsonSerialize());
	}

	public function testExtractFromAttachments() {
		/** @var Account|MockObject $account */
		$account = $this->createMock(Account::class);
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
		$this->itineraryExtractor->expects($this->at(0))
			->method('extract')
			->with($pdf)
			->willReturn(new Itinerary(['datafrompdf']));
		$this->itineraryExtractor->expects($this->at(1))
			->method('extract')
			->with('["datafrompdf"]')
			->willReturn(new Itinerary(['datafrompdf']));

		$itinerary = $this->service->extract($account, 'INBOX', 13);

		$this->assertEquals(['datafrompdf'], $itinerary->jsonSerialize());
	}
}
