<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Events\BeforeImapClientCreated;
use OCA\Mail\Events\BeforeSmtpClientCreated;
use OCA\Mail\Integration\GoogleIntegration;
use OCA\Mail\Integration\MicrosoftIntegration;
use OCA\Mail\Integration\OidcIntegration;
use OCA\Mail\Service\AccountService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<Event|BeforeImapClientCreated|BeforeSmtpClientCreated>
 */
class OauthTokenRefreshListener implements IEventListener {
	public function __construct(
		private GoogleIntegration $googleIntegration,
		private MicrosoftIntegration $microsoftIntegration,
		private OidcIntegration $oidcIntegration,
		private AccountService $accountService,
	) {
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof BeforeImapClientCreated) && !($event instanceof BeforeSmtpClientCreated)) {
			return;
		}
		if ($this->googleIntegration->isGoogleOauthAccount($event->getAccount())) {
			$updated = $this->googleIntegration->refresh($event->getAccount());
		} elseif ($this->microsoftIntegration->isMicrosoftOauthAccount($event->getAccount())) {
			$updated = $this->microsoftIntegration->refresh($event->getAccount());
		} elseif ($this->oidcIntegration->isOidcAccount($event->getAccount())) {
			$updated = $this->oidcIntegration->refresh($event->getAccount());
		} else {
			return;
		}

		$this->accountService->update($updated->getMailAccount());
	}
}
