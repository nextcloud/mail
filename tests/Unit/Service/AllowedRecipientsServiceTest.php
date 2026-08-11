<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\AllowedRecipientsService;
use PHPUnit\Framework\MockObject\MockObject;

class AllowedRecipientsServiceTest extends TestCase {

	private AliasesService&MockObject $aliasesService;
	private AllowedRecipientsService $allowedRecipientsService;

	protected function setUp(): void {
		parent::setUp();

		$this->aliasesService = $this->createMock(AliasesService::class);
		$this->allowedRecipientsService = new AllowedRecipientsService($this->aliasesService);
	}

	public function testGet(): void {
		$alias1 = new Alias();
		$alias1->setAlias('alias1@example.org');

		$alias2 = new Alias();
		$alias2->setAlias('alias2@example.org');

		$this->aliasesService->expects(self::once())
			->method('findAll')
			->willReturn([$alias1, $alias2]);

		$mailAccount = new MailAccount();
		$mailAccount->setId(1);
		$mailAccount->setUserId('user');
		$mailAccount->setEmail('user@example.org');

		$recipients = $this->allowedRecipientsService->get($mailAccount);

		$this->assertCount(3, $recipients);
		$this->assertEquals('user@example.org', $recipients[0]);
		$this->assertEquals('alias1@example.org', $recipients[1]);
		$this->assertEquals('alias2@example.org', $recipients[2]);
	}
}
