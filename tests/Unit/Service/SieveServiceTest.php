<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\SieveService;
use OCA\Mail\Sieve\SieveClientFactory;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\Log\Audit\CriticalActionPerformedEvent;
use PHPUnit\Framework\MockObject\MockObject;

class SieveServiceTest extends TestCase {
	private SieveService $sieveService;

	/** @var SieveClientFactory|MockObject */
	private $sieveClientFactory;

	/** @var AccountService|MockObject */
	private $accountService;

	private IEventDispatcher&MockObject $eventDispatcher;

	protected function setUp(): void {
		parent::setUp();

		$this->sieveClientFactory = $this->createMock(SieveClientFactory::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->eventDispatcher = $this->createMock(IEventDispatcher::class);

		$this->sieveService = new SieveService(
			$this->sieveClientFactory,
			$this->accountService,
			$this->eventDispatcher,
		);
	}

	public function testGetActiveScript(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSieveEnabled(true);
		$mailAccount->setSieveHost('localhost');
		$mailAccount->setSievePort(4190);
		$mailAccount->setSieveUser('user');
		$mailAccount->setSievePassword('password');
		$mailAccount->setSieveSslMode('');
		$account = new Account($mailAccount);

		$client = $this->createMock(\Horde\ManageSieve::class);
		$client->expects(self::once())
			->method('getActive')
			->willReturn('nextcloud');
		$client->expects(self::once())
			->method('getScript')
			->with('nextcloud')
			->willReturn('# foo bar');

		$this->accountService->expects(self::once())
			->method('find')
			->with('1', 2)
			->willReturn($account);
		$this->sieveClientFactory->expects(self::once())
			->method('getClient')
			->with($account)
			->willReturn($client);

		$actual = $this->sieveService->getActiveScript('1', 2);
		$this->assertEquals('nextcloud', $actual->getName());
		$this->assertEquals('# foo bar', $actual->getScript());
	}

	public function testGetActiveScriptNoName(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSieveEnabled(true);
		$mailAccount->setSieveHost('localhost');
		$mailAccount->setSievePort(4190);
		$mailAccount->setSieveUser('user');
		$mailAccount->setSievePassword('password');
		$mailAccount->setSieveSslMode('');
		$account = new Account($mailAccount);

		$client = $this->createMock(\Horde\ManageSieve::class);
		$client->expects(self::once())
			->method('getActive')
			->willReturn(null);
		$client->expects(self::never())
			->method('getScript');

		$this->accountService->expects(self::once())
			->method('find')
			->with('1', 2)
			->willReturn($account);
		$this->sieveClientFactory->expects(self::once())
			->method('getClient')
			->with($account)
			->willReturn($client);

		$actual = $this->sieveService->getActiveScript('1', 2);
		$this->assertEquals('', $actual->getName());
		$this->assertEquals('', $actual->getScript());
	}

	public function scriptTrimDataProvider(): array {
		return [
			['# foo bar', '# foo bar'],
			["# foo bar\r\n", '# foo bar'],
			["# foo bar\r\n\r\n", '# foo bar'],
			["\r\n# foo bar", "\r\n# foo bar"],
			['# foo bar  ', '# foo bar  '],
		];
	}

	/**
	 * @dataProvider scriptTrimDataProvider
	 */
	public function testGetActiveScriptTrimsTrailingLineFeeds(string $script, string $expected): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSieveEnabled(true);
		$mailAccount->setSieveHost('localhost');
		$mailAccount->setSievePort(4190);
		$mailAccount->setSieveUser('user');
		$mailAccount->setSievePassword('password');
		$mailAccount->setSieveSslMode('');
		$account = new Account($mailAccount);

		$client = $this->createMock(\Horde\ManageSieve::class);
		$client->expects(self::once())
			->method('getActive')
			->willReturn('nextcloud');
		$client->expects(self::once())
			->method('getScript')
			->with('nextcloud')
			->willReturn($script);

		$this->accountService->expects(self::once())
			->method('find')
			->with('1', 2)
			->willReturn($account);
		$this->sieveClientFactory->expects(self::once())
			->method('getClient')
			->with($account)
			->willReturn($client);

		$actual = $this->sieveService->getActiveScript('1', 2);
		$this->assertEquals('nextcloud', $actual->getName());
		$this->assertEquals($expected, $actual->getScript());
	}

	public function testGetActiveScriptNoSieve(): void {
		$this->expectException(ClientException::class);
		$this->expectExceptionMessage('ManageSieve is disabled');

		$mailAccount = new MailAccount();
		$mailAccount->setSieveEnabled(false);

		$this->accountService->expects(self::once())
			->method('find')
			->with('1', 2)
			->willReturn(new Account($mailAccount));

		$this->sieveService->getActiveScript('1', 2);
	}

	public function testUpdateActiveScript(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSieveEnabled(true);
		$mailAccount->setSieveHost('localhost');
		$mailAccount->setSievePort(4190);
		$mailAccount->setSieveUser('user');
		$mailAccount->setSievePassword('password');
		$mailAccount->setSieveSslMode('');
		$account = new Account($mailAccount);

		$client = $this->createMock(\Horde\ManageSieve::class);
		$client->expects(self::once())
			->method('getActive')
			->willReturn('nextcloud');
		$client->expects(self::once())
			->method('installScript')
			->with('nextcloud', "# foo bar\r\n", true);

		$this->accountService->expects(self::once())
			->method('find')
			->with('1', 2)
			->willReturn($account);
		$this->sieveClientFactory->expects(self::once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$this->eventDispatcher->expects(self::once())
			->method('dispatchTyped')
			->with(self::callback(fn (CriticalActionPerformedEvent $event): bool => $event->getOperation() === 'mail.sieve_script.updated'
				&& $event->getLogMessage() === 'Active Sieve script of mail account %s of "%s" was updated'
				&& $event->getParameters() === ['accountId' => 2, 'owner' => '1']));

		$this->sieveService->updateActiveScript('1', 2, '# foo bar');
	}

	public function testUpdateActiveScriptWithNoName(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSieveEnabled(true);
		$mailAccount->setSieveHost('localhost');
		$mailAccount->setSievePort(4190);
		$mailAccount->setSieveUser('user');
		$mailAccount->setSievePassword('password');
		$mailAccount->setSieveSslMode('');
		$account = new Account($mailAccount);

		$client = $this->createMock(\Horde\ManageSieve::class);
		$client->expects(self::once())
			->method('getActive')
			->willReturn(null);
		$client->expects(self::once())
			->method('installScript')
			->with('nextcloud', "# foo bar\r\n", true);

		$this->accountService->expects(self::once())
			->method('find')
			->with('1', 2)
			->willReturn($account);
		$this->sieveClientFactory->expects(self::once())
			->method('getClient')
			->with($account)
			->willReturn($client);

		$this->sieveService->updateActiveScript('1', 2, '# foo bar');
	}

	public function testUpdateActiveScriptCollapsesTrailingNewlines(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSieveEnabled(true);
		$mailAccount->setSieveHost('localhost');
		$mailAccount->setSievePort(4190);
		$mailAccount->setSieveUser('user');
		$mailAccount->setSievePassword('password');
		$mailAccount->setSieveSslMode('');
		$account = new Account($mailAccount);

		$client = $this->createMock(\Horde\ManageSieve::class);
		$client->expects(self::once())
			->method('getActive')
			->willReturn('nextcloud');
		$client->expects(self::once())
			->method('installScript')
			->with('nextcloud', "# foo bar\r\n", true);

		$this->accountService->expects(self::once())
			->method('find')
			->with('1', 2)
			->willReturn($account);
		$this->sieveClientFactory->expects(self::once())
			->method('getClient')
			->with($account)
			->willReturn($client);

		$this->sieveService->updateActiveScript('1', 2, "# foo bar\r\n\r\n\r\n");
	}

	public function testUpdateActiveScriptNoSieve(): void {
		$this->expectException(ClientException::class);
		$this->expectExceptionMessage('ManageSieve is disabled');
		$this->eventDispatcher->expects(self::never())
			->method('dispatchTyped');

		$mailAccount = new MailAccount();
		$mailAccount->setSieveEnabled(false);

		$this->accountService->expects(self::once())
			->method('find')
			->with('1', 2)
			->willReturn(new Account($mailAccount));

		$this->sieveService->updateActiveScript('1', 2, '# foo bar');
	}
}
