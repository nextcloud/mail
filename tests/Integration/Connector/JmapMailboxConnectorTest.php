<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Connector;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailboxConnector;
use OCA\Mail\JMAP\JmapMailboxConnector;
use OCA\Mail\Tests\Integration\Framework\JmapTestAccount;
use OCP\Server;

class JmapMailboxConnectorTest extends AbstractMailboxConnectorTest {
	use JmapTestAccount;

	protected function createConnector(): IMailboxConnector {
		return Server::get(JmapMailboxConnector::class);
	}

	protected function createAccount(): Account {
		return new Account($this->createTestAccount());
	}
}
