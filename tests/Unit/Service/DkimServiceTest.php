<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
 *
 * @license AGPL-3.0-or-later
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

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OC\Memcache\ArrayCache;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IDkimValidator;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper;
use OCA\Mail\Service\DkimService;
use OCP\ICache;
use OCP\ICacheFactory;

class DkimServiceTest extends TestCase {
	private IMAPClientFactory $imapClientFactory;
	private MessageMapper $messageMapper;
	private ICache $cache;
	private IDkimValidator $dkimValidator;
	private DkimService $dkimService;

	protected function setUp(): void {
		parent::setUp();

		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cache = new ArrayCache('dkim_test');
		$this->dkimValidator = $this->createMock(IDkimValidator::class);

		$cacheFactory->method('createLocal')
			->willReturn($this->cache);

		$this->dkimService = new DkimService(
			$this->imapClientFactory,
			$this->messageMapper,
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

		$this->dkimService->validate(
			$account,
			$mailbox,
			3
		);
	}

	public function testValidate(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setId(100);
		$mailAccount->setUserId('1');
		$account = new Account($mailAccount);

		$mailbox = new Mailbox();
		$mailbox->setName('FooBar');

		$this->messageMapper
			->method('getFullText')
			->willReturn('FooBar');

		$this->dkimValidator
			->method('validate')
			->willReturn(true);

		$result = $this->dkimService->validate(
			$account,
			$mailbox,
			4
		);

		$this->assertTrue($result);
	}
}
