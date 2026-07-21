<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Nextcloud\KItinerary\Itinerary;
use OC\Memcache\ArrayCache;
use OCA\Mail\Account;
use OCA\Mail\Attachment;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Integration\KItinerary\ItineraryExtractor;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\ItineraryService;
use OCA\Mail\Service\MailManager;
use OCP\ICache;
use OCP\ICacheFactory;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\NullLogger;

class ItineraryServiceTest extends TestCase {
	/** @var MailManager|MockObject */
	private $mailManager;

	/** @var ItineraryExtractor|MockObject */
	private $itineraryExtractor;

	private ICache $cache;

	/** @var ItineraryService */
	private $service;

	protected function setUp(): void {
		parent::setUp();

		$this->mailManager = $this->createMock(MailManager::class);
		$this->itineraryExtractor = $this->createMock(ItineraryExtractor::class);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = new ArrayCache('itinerary_test');

		$cacheFactory->method('createLocal')
			->willReturn($this->cache);

		$this->service = new ItineraryService(
			$this->mailManager,
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

		$message = new Message();
		$message->setId(13);

		$imapMessage = $this->createMock(IMAPMessage::class);
		$imapMessage->htmlMessage = '';
		$this->mailManager->expects($this->once())
			->method('getImapMessage')
			->with($account, $mailbox, $message, true)
			->willReturn($imapMessage);
		$this->mailManager->expects($this->once())
			->method('getMailAttachments')
			->with($account, $mailbox, $message)
			->willReturn([]);
		$this->itineraryExtractor->expects($this->once())
			->method('extract')
			->willReturn(new Itinerary());

		$itinerary = $this->service->extract($account, $mailbox, $message);

		$this->assertEquals([], $itinerary->jsonSerialize());
	}

	public function testExtractFromBody() {
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('1');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');

		$message = new Message();
		$message->setId(13);

		$body = '<html><body>hello</body></html>';
		$imapMessage = $this->createMock(IMAPMessage::class);
		$imapMessage->htmlMessage = $body;
		$this->mailManager->expects($this->once())
			->method('getImapMessage')
			->with($account, $mailbox, $message, true)
			->willReturn($imapMessage);
		$this->mailManager->expects($this->once())
			->method('getMailAttachments')
			->with($account, $mailbox, $message)
			->willReturn([]);
		$this->itineraryExtractor->expects($this->exactly(2))
			->method('extract')
			->withConsecutive([$body], ['["datafrombody"]'])
			->willReturn(new Itinerary(['datafrombody']));

		$itinerary = $this->service->extract($account, $mailbox, $message);

		$this->assertEquals(['datafrombody'], $itinerary->jsonSerialize());
	}

	public function testExtractFromAttachments() {
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('1');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setName('INBOX');

		$message = new Message();
		$message->setId(13);

		$imapMessage = $this->createMock(IMAPMessage::class);
		$imapMessage->htmlMessage = '';
		$pdf = '%PDF-1.3.%';
		$attachment = $this->createMock(Attachment::class);
		$attachment->method('getContent')
			->willReturn($pdf);
		$this->mailManager->expects($this->once())
			->method('getImapMessage')
			->with($account, $mailbox, $message, true)
			->willReturn($imapMessage);
		$this->mailManager->expects($this->once())
			->method('getMailAttachments')
			->with($account, $mailbox, $message)
			->willReturn([$attachment]);
		$this->itineraryExtractor->expects($this->exactly(2))
			->method('extract')
			->withConsecutive([$pdf], ['["datafrompdf"]'])
			->willReturn(new Itinerary(['datafrompdf']));

		$itinerary = $this->service->extract($account, $mailbox, $message);

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
