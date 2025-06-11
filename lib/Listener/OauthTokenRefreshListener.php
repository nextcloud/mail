<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Listener;

use OCA\Mail\Events\BeforeImapClientCreated;
use OCA\Mail\Integration\GoogleIntegration;
use OCA\Mail\Integration\MicrosoftIntegration;
use OCA\Mail\Service\AccountService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<Event|BeforeImapClientCreated>
 */
class OauthTokenRefreshListener implements IEventListener {
	private GoogleIntegration $googleIntegration;
	private MicrosoftIntegration $microsoftIntegration;
	private AccountService $accountService;
	public function __construct(GoogleIntegration $googleIntegration,
		MicrosoftIntegration $microsoftIntegration,
		AccountService $accountService) {
		$this->googleIntegration = $googleIntegration;
		$this->accountService = $accountService;
		$this->microsoftIntegration = $microsoftIntegration;
	}

	#[\Override]
	public function handle(Event $event): void {
		if (!($event instanceof BeforeImapClientCreated)) {
			return;
		}
		if ($this->googleIntegration->isGoogleOauthAccount($event->getAccount())) {
			$updated = $this->googleIntegration->refresh($event->getAccount());
		} elseif ($this->microsoftIntegration->isMicrosoftOauthAccount($event->getAccount())) {
			$updated = $this->microsoftIntegration->refresh($event->getAccount());
		} else {
			return;
		}

		$this->accountService->update($updated->getMailAccount());
	}
}
