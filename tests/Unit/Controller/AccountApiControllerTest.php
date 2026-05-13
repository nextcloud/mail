<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Controller\AccountApiController;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\DelegationService;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

class AccountApiControllerTest extends TestCase {
	private const USER_ID = 'user';

	private AccountApiController $controller;

	private IRequest&MockObject $request;
	private AccountService&MockObject $accountService;
	private AliasesService&MockObject $aliasesService;
	private DelegationService&MockObject $delegationService;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->aliasesService = $this->createMock(AliasesService::class);
		$this->delegationService = $this->createMock(DelegationService::class);

		$this->controller = new AccountApiController(
			'mail',
			$this->request,
			self::USER_ID,
			$this->accountService,
			$this->aliasesService,
			$this->delegationService,
		);
	}

	public function testListWithoutUser() {
		$controller = new AccountApiController(
			'mail',
			$this->request,
			null,
			$this->accountService,
			$this->aliasesService,
			$this->delegationService,
		);

		$this->accountService->expects(self::never())
			->method('findByUserId');

		$this->aliasesService->expects(self::never())
			->method('findAll');

		$actual = $controller->list();
		$this->assertEquals(Http::STATUS_NOT_FOUND, $actual->getStatus());
	}

	public function testList() {
		$mailAccount = new MailAccount();
		$mailAccount->setId(42);
		$mailAccount->setEmail('foo@bar.com');
		$mailAccount->setUserId(self::USER_ID);

		$account = new Account($mailAccount);
		$this->accountService->expects(self::once())
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([$account]);
		$this->accountService->expects(self::once())
			->method('findDelegatedAccounts')
			->with(self::USER_ID)
			->willReturn([]);

		$alias = new Alias();
		$alias->setId(10);
		$alias->setName('Baz');
		$alias->setAlias('baz@bar.com');
		$this->aliasesService->expects(self::once())
			->method('findAll')
			->with(42, self::USER_ID)
			->willReturn([$alias]);

		$actual = $this->controller->list();
		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
		$this->assertEquals([
			[
				'id' => 42,
				'email' => 'foo@bar.com',
				'isDelegated' => false,
				'aliases' => [
					[
						'id' => 10,
						'email' => 'baz@bar.com',
						'name' => 'Baz',
					],
				],
			]
		], $actual->getData());
	}

	public function testListWithAliasWithoutName() {
		$mailAccount = new MailAccount();
		$mailAccount->setId(42);
		$mailAccount->setEmail('foo@bar.com');
		$mailAccount->setUserId(self::USER_ID);

		$account = new Account($mailAccount);
		$this->accountService->expects(self::once())
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([$account]);
		$this->accountService->expects(self::once())
			->method('findDelegatedAccounts')
			->with(self::USER_ID)
			->willReturn([]);

		$alias = new Alias();
		$alias->setId(10);
		$alias->setName(null);
		$alias->setAlias('baz@bar.com');
		$this->aliasesService->expects(self::once())
			->method('findAll')
			->with(42, self::USER_ID)
			->willReturn([$alias]);

		$actual = $this->controller->list();
		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
		$this->assertEquals([
			[
				'id' => 42,
				'email' => 'foo@bar.com',
				'isDelegated' => false,
				'aliases' => [
					[
						'id' => 10,
						'email' => 'baz@bar.com',
						'name' => null,
					],
				],
			]
		], $actual->getData());
	}

	public function testListWithDelegatedAccounts() {
		$ownMailAccount = new MailAccount();
		$ownMailAccount->setId(42);
		$ownMailAccount->setEmail('foo@bar.com');
		$ownMailAccount->setUserId(self::USER_ID);
		$ownAccount = new Account($ownMailAccount);


		$delegatedMailAccount = new MailAccount();
		$delegatedMailAccount->setId(99);
		$delegatedMailAccount->setEmail('shared@bar.com');
		$delegatedMailAccount->setUserId('owner');
		$delegatedAccount = new Account($delegatedMailAccount);

		$this->accountService->expects(self::once())
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([$ownAccount]);
		$this->accountService->expects(self::once())
			->method('findDelegatedAccounts')
			->with(self::USER_ID)
			->willReturn([$delegatedAccount]);

		$ownAlias = new Alias();
		$ownAlias->setId(10);
		$ownAlias->setName('Baz');
		$ownAlias->setAlias('baz@bar.com');

		$delegatedAlias = new Alias();
		$delegatedAlias->setId(20);
		$delegatedAlias->setName('Shared Alias');
		$delegatedAlias->setAlias('shared-alias@bar.com');

		$this->aliasesService->expects(self::exactly(2))
			->method('findAll')
			->willReturnMap([
				[42, self::USER_ID, [$ownAlias]],
				[99, 'owner', [$delegatedAlias]],
			]);

		$actual = $this->controller->list();
		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
		$this->assertEquals([
			[
				'id' => 42,
				'email' => 'foo@bar.com',
				'isDelegated' => false,
				'aliases' => [
					[
						'id' => 10,
						'email' => 'baz@bar.com',
						'name' => 'Baz',
					],
				],
			],
			[
				'id' => 99,
				'email' => 'shared@bar.com',
				'isDelegated' => true,
				'aliases' => [
					[
						'id' => 20,
						'email' => 'shared-alias@bar.com',
						'name' => 'Shared Alias',
					],
				],
			],
		], $actual->getData());
	}

	public function testListWithoutAliases() {
		$mailAccount = new MailAccount();
		$mailAccount->setId(42);
		$mailAccount->setEmail('foo@bar.com');
		$mailAccount->setUserId(self::USER_ID);

		$account = new Account($mailAccount);
		$this->accountService->expects(self::once())
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([$account]);
		$this->accountService->expects(self::once())
			->method('findDelegatedAccounts')
			->with(self::USER_ID)
			->willReturn([]);

		$this->aliasesService->expects(self::once())
			->method('findAll')
			->with(42, self::USER_ID)
			->willReturn([]);

		$actual = $this->controller->list();
		$this->assertEquals(Http::STATUS_OK, $actual->getStatus());
		$this->assertEquals([
			[
				'id' => 42,
				'email' => 'foo@bar.com',
				'isDelegated' => false,
				'aliases' => [],
			]
		], $actual->getData());
	}

}
