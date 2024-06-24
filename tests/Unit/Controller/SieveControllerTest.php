<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use Horde\ManageSieve\Exception;
use OCA\Mail\Controller\SieveController;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\CouldNotConnectException;
use OCA\Mail\Sieve\NamedSieveScript;
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
		$sieveService = $this->serviceMock->getParameter('sieveService');
		$sieveService->expects($this->once())
			->method('getActiveScript')
			->with('1', 2)
			->willReturn(new NamedSieveScript('nextcloud', '# foo bar'));

		$response = $this->sieveController->getActiveScript(2);

		$this->assertEquals(200, $response->getStatus());
		$this->assertEquals(['scriptName' => 'nextcloud', 'script' => '# foo bar'], $response->getData());
	}

	public function testUpdateActiveScript(): void {
		$sieveService = $this->serviceMock->getParameter('sieveService');
		$sieveService->expects($this->once())
			->method('updateActiveScript')
			->with('1', 2, 'sieve script');

		$response = $this->sieveController->updateActiveScript(2, 'sieve script');

		$this->assertEquals(200, $response->getStatus());
		$this->assertEquals([], $response->getData());
	}
}
