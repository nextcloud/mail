<?php

declare(strict_types=1);

/*
 * @copyright 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2022 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
