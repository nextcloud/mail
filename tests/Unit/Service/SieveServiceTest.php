<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\SieveService;
use OCA\Mail\Sieve\SieveClientFactory;
use PHPUnit\Framework\MockObject\MockObject;

class SieveServiceTest extends TestCase {
	private SieveService $sieveService;

	/** @var SieveClientFactory|MockObject */
	private $sieveClientFactory;

	/** @var AccountService|MockObject */
	private $accountService;

	protected function setUp(): void {
		parent::setUp();

		$this->sieveClientFactory = $this->createMock(SieveClientFactory::class);
		$this->accountService = $this->createMock(AccountService::class);

		$this->sieveService = new SieveService(
			$this->sieveClientFactory,
			$this->accountService,
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
			["# foo bar", "# foo bar"],
			["# foo bar\r\n", "# foo bar"],
			["# foo bar\r\n\r\n", "# foo bar"],
			["\r\n# foo bar", "\r\n# foo bar"],
			["# foo bar  ", "# foo bar  "],
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
			->with('nextcloud', '# foo bar', true);

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
			->with('nextcloud', '# foo bar', true);

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

	public function testUpdateActiveScriptNoSieve(): void {
		$this->expectException(ClientException::class);
		$this->expectExceptionMessage('ManageSieve is disabled');

		$mailAccount = new MailAccount();
		$mailAccount->setSieveEnabled(false);

		$this->accountService->expects(self::once())
			->method('find')
			->with('1', 2)
			->willReturn(new Account($mailAccount));

		$this->sieveService->updateActiveScript('1', 2, '# foo bar');
	}
}
