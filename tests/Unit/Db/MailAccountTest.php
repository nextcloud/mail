<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\MailAccount;

class MailAccountTest extends TestCase {
	public function testDefaultsToImap(): void {
		$account = new MailAccount();

		$this->assertTrue($account->isImap());
		$this->assertFalse($account->isJmap());
	}

	public function testIsJmap(): void {
		$account = new MailAccount();

		$account->setProtocol(MailAccount::PROTOCOL_JMAP);

		$this->assertFalse($account->isImap());
		$this->assertTrue($account->isJmap());
	}
}
