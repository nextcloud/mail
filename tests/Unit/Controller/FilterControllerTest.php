<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Controller;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Controller\FilterController;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\DelegationService;
use OCA\Mail\Service\FilterService;
use OCP\AppFramework\Http;
use OCP\IRequest;
use PHPUnit\Framework\MockObject\MockObject;

class FilterControllerTest extends TestCase {
	private IRequest&MockObject $request;
	private FilterService&MockObject $filterService;
	private AccountService&MockObject $accountService;
	private DelegationService&MockObject $delegationService;
	private FilterController $controller;
	private string $userId = 'jane';

	protected function setUp(): void {
		parent::setUp();

		$this->request = $this->createMock(IRequest::class);
		$this->filterService = $this->createMock(FilterService::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->delegationService = $this->createMock(DelegationService::class);
		$this->delegationService->method('resolveAccountUserId')
			->willReturn($this->userId);

		$this->controller = new FilterController(
			$this->request,
			$this->userId,
			$this->filterService,
			$this->accountService,
			$this->delegationService,
		);
	}

	public function testUpdateFiltersLogsDelegatedAction(): void {
		$accountId = 42;
		$mailAccount = new MailAccount();
		$mailAccount->setUserId($this->userId);
		$account = new Account($mailAccount);
		$this->accountService->expects(self::once())
			->method('findById')
			->with($accountId)
			->willReturn($account);
		$this->filterService->expects(self::once())
			->method('update')
			->with($mailAccount, ['filter1']);
		$this->delegationService->expects(self::once())
			->method('logDelegatedAction')
			->with($this->userId, $this->userId, "$this->userId updated account: $accountId 's filters  on behalf of $this->userId");

		$response = $this->controller->updateFilters($accountId, ['filter1']);

		self::assertEquals(Http::STATUS_OK, $response->getStatus());
	}

	public function testUpdateFiltersNotFoundDoesNotLog(): void {
		$accountId = 42;
		$mailAccount = new MailAccount();
		$mailAccount->setUserId('someone-else');
		$account = new Account($mailAccount);
		$this->accountService->expects(self::once())
			->method('findById')
			->with($accountId)
			->willReturn($account);
		$this->filterService->expects(self::never())
			->method('update');
		$this->delegationService->expects(self::never())
			->method('logDelegatedAction');

		$response = $this->controller->updateFilters($accountId, []);

		self::assertEquals(Http::STATUS_NOT_FOUND, $response->getStatus());
	}
}
