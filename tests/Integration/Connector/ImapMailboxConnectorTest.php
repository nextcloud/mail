<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Connector;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailboxConnector;
use OCA\Mail\IMAP\ImapMailboxConnector;
use OCA\Mail\Tests\Integration\Framework\ImapTest;
use OCA\Mail\Tests\Integration\Framework\ImapTestAccount;
use OCP\Server;

class ImapMailboxConnectorTest extends AbstractMailboxConnectorTest {
	use ImapTest;
	use ImapTestAccount;

	protected function createConnector(): IMailboxConnector {
		return Server::get(ImapMailboxConnector::class);
	}

	protected function createAccount(): Account {
		return new Account($this->createTestAccount());
	}
}
