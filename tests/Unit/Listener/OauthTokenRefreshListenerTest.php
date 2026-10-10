<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Events\BeforeImapClientCreated;
use OCA\Mail\Integration\GoogleIntegration;
use OCA\Mail\Integration\MicrosoftIntegration;
use OCA\Mail\Integration\OidcIntegration;
use OCA\Mail\Listener\OauthTokenRefreshListener;
use OCA\Mail\Service\AccountService;
use OCP\EventDispatcher\Event;
use PHPUnit\Framework\MockObject\MockObject;

class OauthTokenRefreshListenerTest extends TestCase {
	private GoogleIntegration&MockObject $googleIntegration;
	private MicrosoftIntegration&MockObject $microsoftIntegration;
	private OidcIntegration&MockObject $oidcIntegration;
	private AccountService&MockObject $accountService;
	private OauthTokenRefreshListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->googleIntegration = $this->createMock(GoogleIntegration::class);
		$this->microsoftIntegration = $this->createMock(MicrosoftIntegration::class);
		$this->oidcIntegration = $this->createMock(OidcIntegration::class);
		$this->accountService = $this->createMock(AccountService::class);

		$this->listener = new OauthTokenRefreshListener(
			$this->googleIntegration,
			$this->microsoftIntegration,
			$this->oidcIntegration,
			$this->accountService,
		);
	}

	private function account(): Account {
		return new Account(new MailAccount());
	}

	public function testIgnoresUnrelatedEvent(): void {
		$this->accountService->expects($this->never())->method('update');

		$this->listener->handle(new Event());
	}

	public function testRefreshesOidcAccount(): void {
		$account = $this->account();
		$this->googleIntegration->method('isGoogleOauthAccount')->willReturn(false);
		$this->microsoftIntegration->method('isMicrosoftOauthAccount')->willReturn(false);
		$this->oidcIntegration->method('isOidcAccount')->with($account)->willReturn(true);
		$this->oidcIntegration->expects($this->once())
			->method('refresh')
			->with($account)
			->willReturn($account);
		$this->accountService->expects($this->once())
			->method('update')
			->with($account->getMailAccount());

		$this->listener->handle(new BeforeImapClientCreated($account));
	}

	public function testOidcNotCheckedWhenGoogleMatches(): void {
		$account = $this->account();
		$this->googleIntegration->method('isGoogleOauthAccount')->willReturn(true);
		$this->googleIntegration->method('refresh')->willReturn($account);
		$this->oidcIntegration->expects($this->never())->method('isOidcAccount');
		$this->oidcIntegration->expects($this->never())->method('refresh');

		$this->listener->handle(new BeforeImapClientCreated($account));
	}

	public function testDoesNotUpdateWhenNoIntegrationMatches(): void {
		$account = $this->account();
		$this->googleIntegration->method('isGoogleOauthAccount')->willReturn(false);
		$this->microsoftIntegration->method('isMicrosoftOauthAccount')->willReturn(false);
		$this->oidcIntegration->method('isOidcAccount')->willReturn(false);
		$this->accountService->expects($this->never())->method('update');

		$this->listener->handle(new BeforeImapClientCreated($account));
	}
}
