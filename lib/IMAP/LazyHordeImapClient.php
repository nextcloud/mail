<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use Horde_Imap_Client_Socket;
use OCA\Mail\Account;

class LazyHordeImapClient {
	private ?Horde_Imap_Client_Socket $client = null;

	public function __construct(
		private readonly IMAPClientFactory $clientFactory,
		private readonly Account $account,
		private readonly bool $useHordeCache,
	) {
	}

	public function getClient(): Horde_Imap_Client_Socket {
		if ($this->client === null) {
			$this->client = $this->clientFactory->getClient($this->account, $this->useHordeCache);
		}

		return $this->client;
	}

	public function logout(): void {
		if ($this->client === null) {
			return;
		}

		$this->client->logout();
		$this->client = null;
	}
}
