<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OC\Memcache\ArrayCache;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IDkimValidator;
use OCA\Mail\Contracts\IMessageConnector;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\DkimService;
use OCP\ICache;
use OCP\ICacheFactory;

class DkimServiceTest extends TestCase {
	private ProtocolFactory $protocolFactory;
	private IMessageConnector $messageConnector;
	private ICache $cache;
	private IDkimValidator $dkimValidator;
	private DkimService $dkimService;

	protected function setUp(): void {
		parent::setUp();

		$this->protocolFactory = $this->createMock(ProtocolFactory::class);
		$this->messageConnector = $this->createMock(IMessageConnector::class);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = new ArrayCache('dkim_test');
		$this->dkimValidator = $this->createMock(IDkimValidator::class);

		$cacheFactory->method('createLocal')
			->willReturn($this->cache);
		$this->protocolFactory->method('messageConnector')
			->willReturn($this->messageConnector);

		$this->dkimService = new DkimService(
			$this->protocolFactory,
			$cacheFactory,
			$this->dkimValidator,
		);
	}

	public function testGetCachedMiss(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('1');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setName('FooBar');

		$result = $this->dkimService->getCached(
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

		$this->cache->set('100_FooBar_1', true);

		$result = $this->dkimService->getCached(
			$account,
			$mailbox,
			1
		);

		$this->assertTrue($result);
	}

	public function testValidateFetchMessageFails(): void {
		$this->expectException(ServiceException::class);
		$this->expectExceptionMessage('Could not fetch message source for uid 3');

		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('1');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setName('FooBar');

		$message = new Message();
		$message->setId(3);
		$message->setUid(3);

		$this->dkimService->validate(
			$account,
			$mailbox,
			$message
		);
	}

	public function testValidate(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('1');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setName('FooBar');

		$message = new Message();
		$message->setId(4);
		$message->setUid(4);

		$this->messageConnector
			->method('fetchMessageRaw')
			->willReturn('FooBar');

		$this->dkimValidator
			->method('validate')
			->willReturn(true);

		$result = $this->dkimService->validate(
			$account,
			$mailbox,
			$message
		);

		$this->assertTrue($result);
	}
}
