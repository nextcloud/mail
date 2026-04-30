<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Controller\DelegationController;
use OCA\Mail\Db\Delegation;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\DelegationExistsException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\DelegationService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;

class DelegationControllerTest extends TestCase {
	private string $appName = 'mail';
	private string $currentUserId = 'owner';

	private IRequest&MockObject $request;
	private DelegationService&MockObject $delegationService;
	private AccountService&MockObject $accountService;
	private IUserManager&MockObject $userManager;
	private DelegationController $controller;

	private Account $ownAccount;
	private Account $otherAccount;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->delegationService = $this->createMock(DelegationService::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->userManager = $this->createMock(IUserManager::class);

		$this->controller = new DelegationController(
			$this->appName,
			$this->request,
			$this->delegationService,
			$this->accountService,
			$this->userManager,
			$this->currentUserId,
		);

		$ownMailAccount = new MailAccount();
		$ownMailAccount->setId(1);
		$ownMailAccount->setUserId($this->currentUserId);
		$ownMailAccount->setEmail('owner@example.com');
		$this->ownAccount = new Account($ownMailAccount);

		$otherMailAccount = new MailAccount();
		$otherMailAccount->setId(2);
		$otherMailAccount->setUserId('other');
		$otherMailAccount->setEmail('other@example.com');
		$this->otherAccount = new Account($otherMailAccount);
	}

	public function testGetDelegatedUsersSuccess(): void {
		$delegation = new Delegation();
		$delegation->setId(10);
		$delegation->setAccountId(1);
		$delegation->setUserId('delegatee');

		$this->accountService->expects($this->once())
			->method('findById')
			->with(1)
			->willReturn($this->ownAccount);

		$this->delegationService->expects($this->once())
			->method('findDelegatedToUsersForAccount')
			->with(1)
			->willReturn([$delegation]);

		$response = $this->controller->getDelegatedUsers(1);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
		$this->assertEquals([$delegation], $response->getData());
	}

	public function testGetDelegatedUsersUnauthorized(): void {
		$this->accountService->expects($this->once())
			->method('findById')
			->with(2)
			->willReturn($this->otherAccount);

		$this->delegationService->expects($this->never())
			->method('findDelegatedToUsersForAccount');

		$response = $this->controller->getDelegatedUsers(2);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}

	public function testDelegateSuccess(): void {
		$delegation = new Delegation();
		$delegation->setId(10);
		$delegation->setAccountId(1);
		$delegation->setUserId('delegatee');

		$this->accountService->expects($this->once())
			->method('findById')
			->with(1)
			->willReturn($this->ownAccount);

		$this->userManager->expects($this->once())
			->method('userExists')
			->with('delegatee')
			->willReturn(true);

		$this->delegationService->expects($this->once())
			->method('delegate')
			->with(1, 'delegatee')
			->willReturn($delegation);

		$response = $this->controller->delegate(1, 'delegatee');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$this->assertEquals($delegation, $response->getData());
	}

	public function testDelegateUnauthorized(): void {
		$this->accountService->expects($this->once())
			->method('findById')
			->with(2)
			->willReturn($this->otherAccount);

		$this->delegationService->expects($this->never())
			->method('delegate');

		$response = $this->controller->delegate(2, 'delegatee');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}

	public function testDelegateProvisionedAccount(): void {
		$provisionedMailAccount = new MailAccount();
		$provisionedMailAccount->setId(3);
		$provisionedMailAccount->setUserId($this->currentUserId);
		$provisionedMailAccount->setEmail('provisioned@example.com');
		$provisionedMailAccount->setProvisioningId(42);
		$provisionedAccount = new Account($provisionedMailAccount);

		$this->accountService->expects($this->once())
			->method('findById')
			->with(3)
			->willReturn($provisionedAccount);

		$this->delegationService->expects($this->never())
			->method('delegate');

		$response = $this->controller->delegate(3, 'delegatee');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals(Http::STATUS_FORBIDDEN, $response->getStatus());
		$this->assertEquals(['message' => 'Cannot delegate provisioned accounts'], $response->getData());
	}

	public function testDelegateToSelf(): void {
		$this->accountService->expects($this->once())
			->method('findById')
			->with(1)
			->willReturn($this->ownAccount);

		$this->delegationService->expects($this->never())
			->method('delegate');

		$response = $this->controller->delegate(1, $this->currentUserId);

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals(Http::STATUS_BAD_REQUEST, $response->getStatus());
		$this->assertEquals(['message' => 'Cannot delegate to yourself'], $response->getData());
	}

	public function testDelegateUserNotFound(): void {
		$this->accountService->expects($this->once())
			->method('findById')
			->with(1)
			->willReturn($this->ownAccount);

		$this->userManager->expects($this->once())
			->method('userExists')
			->with('nonexistent')
			->willReturn(false);

		$this->delegationService->expects($this->never())
			->method('delegate');

		$response = $this->controller->delegate(1, 'nonexistent');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}

	public function testDelegateAlreadyExists(): void {
		$this->accountService->expects($this->once())
			->method('findById')
			->with(1)
			->willReturn($this->ownAccount);

		$this->userManager->expects($this->once())
			->method('userExists')
			->with('delegatee')
			->willReturn(true);

		$this->delegationService->expects($this->once())
			->method('delegate')
			->with(1, 'delegatee')
			->willThrowException(new DelegationExistsException('Delegation already exists'));

		$response = $this->controller->delegate(1, 'delegatee');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals(Http::STATUS_CONFLICT, $response->getStatus());
		$this->assertEquals(['message' => 'Delegation already exists'], $response->getData());
	}

	public function testUnDelegateSuccess(): void {
		$this->accountService->expects($this->once())
			->method('findById')
			->with(1)
			->willReturn($this->ownAccount);

		$this->delegationService->expects($this->once())
			->method('unDelegate')
			->with(1, 'delegatee');

		$response = $this->controller->unDelegate(1, 'delegatee');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals(Http::STATUS_OK, $response->getStatus());
	}

	public function testUnDelegateUnauthorized(): void {
		$this->accountService->expects($this->once())
			->method('findById')
			->with(2)
			->willReturn($this->otherAccount);

		$this->delegationService->expects($this->never())
			->method('unDelegate');

		$response = $this->controller->unDelegate(2, 'delegatee');

		$this->assertInstanceOf(JSONResponse::class, $response);
		$this->assertEquals(Http::STATUS_UNAUTHORIZED, $response->getStatus());
	}
}
