<?php

declare(strict_types=1);

/**
 * @author Daniel Kesselberg <mail@danielkesselberg.de>
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

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use Horde\ManageSieve\Exception;
use OCA\Mail\Account;
use OCA\Mail\Controller\SieveController;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\CouldNotConnectException;
use OCA\Mail\Tests\Integration\TestCase;
use OCP\Security\IRemoteHostValidator;
use PHPUnit\Framework\MockObject\MockObject;

class SieveControllerTest extends TestCase {
	/** @var IRemoteHostValidator|MockObject */
	private $remoteHostValidator;

	/** @var ServiceMockObject */
	private $serviceMock;

	/** @var SieveController */
	private $sieveController;


	protected function setUp(): void {
		parent::setUp();

		$this->remoteHostValidator = $this->createMock(IRemoteHostValidator::class);
		$this->remoteHostValidator->method('isValid')->willReturn(true);

		$this->serviceMock = $this->createServiceMock(
			SieveController::class,
			[
				'hostValidator' => $this->remoteHostValidator,
				'UserId' => '1',
			]
		);
		$this->sieveController = $this->serviceMock->getService();
	}

	public function testUpdateAccountDisable(): void {
		$mailAccountMapper = $this->serviceMock->getParameter('mailAccountMapper');
		$mailAccountMapper->expects($this->once())
			->method('find')
			->with('1', 2)
			->willReturn(new MailAccount());
		$mailAccountMapper->expects($this->once())
			->method('save');

		$response = $this->sieveController->updateAccount(2, false, '', 0, '', '', '');

		$this->assertEquals(200, $response->getStatus());
		$this->assertEquals(false, $response->getData()['sieveEnabled']);
	}

	public function testUpdateAccountEnable(): void {
		$mailAccountMapper = $this->serviceMock->getParameter('mailAccountMapper');
		$mailAccountMapper->expects($this->once())
			->method('find')
			->with('1', 2)
			->willReturn(new MailAccount());
		$mailAccountMapper->expects($this->once())
			->method('save');

		$response = $this->sieveController->updateAccount(2, true, 'localhost', 4190, 'user', 'password', '');

		$this->assertEquals(200, $response->getStatus());
		$this->assertEquals(true, $response->getData()['sieveEnabled']);
	}

	public function testUpdateAccountEnableImapCredentials(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setInboundUser('imap_user');
		$mailAccount->setInboundPassword('imap_password');

		$mailAccountMapper = $this->serviceMock->getParameter('mailAccountMapper');
		$mailAccountMapper->expects($this->once())
			->method('find')
			->with('1', 2)
			->willReturn($mailAccount);
		$mailAccountMapper->expects($this->once())
			->method('save');

		$response = $this->sieveController->updateAccount(2, true, 'localhost', 4190, '', '', '');

		$this->assertEquals(200, $response->getStatus());
		$this->assertEquals(true, $response->getData()['sieveEnabled']);
	}

	public function testUpdateAccountEnableNoConnection(): void {
		$this->expectException(CouldNotConnectException::class);
		$this->expectExceptionMessage('Connection to ManageSieve at localhost:4190 failed. Computer says no');

		$mailAccountMapper = $this->serviceMock->getParameter('mailAccountMapper');
		$mailAccountMapper->expects($this->once())
			->method('find')
			->with('1', 2)
			->willReturn(new MailAccount());

		$sieveClientFactory = $this->serviceMock->getParameter('sieveClientFactory');
		$sieveClientFactory->expects($this->once())
			->method('createClient')
			->willThrowException(new Exception('Computer says no'));

		$response = $this->sieveController->updateAccount(2, true, 'localhost', 4190, 'user', 'password', '');
	}

	public function testGetActiveScript(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSieveEnabled(true);
		$mailAccount->setSieveHost('localhost');
		$mailAccount->setSievePort(4190);
		$mailAccount->setSieveUser('user');
		$mailAccount->setSievePassword('password');
		$mailAccount->setSieveSslMode('');

		$accountService = $this->serviceMock->getParameter('accountService');
		$accountService->expects($this->once())
			->method('find')
			->with('1', 2)
			->willReturn(new Account($mailAccount));

		$response = $this->sieveController->getActiveScript(2);

		$this->assertEquals(200, $response->getStatus());
		$this->assertEquals(['scriptName' => '', 'script' => ''], $response->getData());
	}

	public function testGetActiveScriptNoSieve(): void {
		$this->expectException(ClientException::class);
		$this->expectExceptionMessage('ManageSieve is disabled');

		$mailAccount = new MailAccount();
		$mailAccount->setSieveEnabled(false);

		$accountService = $this->serviceMock->getParameter('accountService');
		$accountService->expects($this->once())
			->method('find')
			->with('1', 2)
			->willReturn(new Account($mailAccount));

		$this->sieveController->getActiveScript(2);
	}

	public function testUpdateActiveScript(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSieveEnabled(true);
		$mailAccount->setSieveHost('localhost');
		$mailAccount->setSievePort(4190);
		$mailAccount->setSieveUser('user');
		$mailAccount->setSievePassword('password');
		$mailAccount->setSieveSslMode('');

		$accountService = $this->serviceMock->getParameter('accountService');
		$accountService->expects($this->once())
			->method('find')
			->with('1', 2)
			->willReturn(new Account($mailAccount));

		$response = $this->sieveController->updateActiveScript(2, 'sieve script');

		$this->assertEquals(200, $response->getStatus());
		$this->assertEquals([], $response->getData());
	}
}
