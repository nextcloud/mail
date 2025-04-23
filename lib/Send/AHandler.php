<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Send;

use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\LocalMessage;

abstract class AHandler {

	protected ?AHandler $next = null;
	public function setNext(AHandler $next): AHandler {
		$this->next = $next;
		return $next;
	}

	abstract public function process(
		Account $account,
		LocalMessage $localMessage,
		Horde_Imap_Client_Socket $client,
	): LocalMessage;

	protected function processNext(
		Account $account,
		LocalMessage $localMessage,
		Horde_Imap_Client_Socket $client,
	): LocalMessage {
		if ($this->next !== null) {
			return $this->next->process($account, $localMessage, $client);
		}
		return $localMessage;
	}
}
