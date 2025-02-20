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
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

class AccountApiControllerTest extends TestCase {
	private const USER_ID = 'user';

	private AccountApiController $controller;

	private IRequest&MockObject $request;
	private AccountService&MockObject $accountService;
	private AliasesService&MockObject $aliasesService;

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->aliasesService = $this->createMock(AliasesService::class);

		$this->controller = new AccountApiController(
			'mail',
			$this->request,
			self::USER_ID,
			$this->accountService,
			$this->aliasesService,
		);
	}

	public function testListWithoutUser() {
		$controller = new AccountApiController(
			'mail',
			$this->request,
			null,
			$this->accountService,
			$this->aliasesService,
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

		$account = new Account($mailAccount);
		$this->accountService->expects(self::once())
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([$account]);

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

		$account = new Account($mailAccount);
		$this->accountService->expects(self::once())
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([$account]);

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

	public function testListWithoutAliases() {
		$mailAccount = new MailAccount();
		$mailAccount->setId(42);
		$mailAccount->setEmail('foo@bar.com');

		$account = new Account($mailAccount);
		$this->accountService->expects(self::once())
			->method('findByUserId')
			->with(self::USER_ID)
			->willReturn([$account]);

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
				'aliases' => [],
			]
		], $actual->getData());
	}

}
