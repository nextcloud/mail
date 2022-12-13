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
use OCA\Mail\Service\AccountService;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventListener;

/**
 * @template-implements IEventListener<Event|BeforeImapClientCreated>
 */
class OauthTokenRefreshListener implements IEventListener {
	private GoogleIntegration $googleIntegration;
	private AccountService $accountService;

	public function __construct(GoogleIntegration $googleIntegration,
								AccountService $accountService) {
		$this->googleIntegration = $googleIntegration;
		$this->accountService = $accountService;
	}

	public function handle(Event $event): void {
		if (!($event instanceof BeforeImapClientCreated)) {
			return;
		}
		if (!$this->googleIntegration->isGoogleOauthAccount($event->getAccount())) {
			return;
		}

		$updated = $this->googleIntegration->refresh($event->getAccount());
		$this->accountService->update($updated->getMailAccount());
	}
}
