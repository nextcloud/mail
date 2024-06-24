<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Recipient;

class RecipientTest extends TestCase {
	protected function setUp(): void {
	}

	public function testGettersSetters(): void {
		$recipient = new Recipient();
		$recipient->setMessageId(1);
		$recipient->setLocalMessageId(100);
		$recipient->setType(Recipient::TYPE_TO);
		$recipient->setLabel('Penny');
		$recipient->setEmail('penny@stardew-library.edu');


		$this->assertEquals(1, $recipient->getMessageId());
		$this->assertEquals(100, $recipient->getLocalMessageId());
		$this->assertEquals(Recipient::TYPE_TO, $recipient->getType());
		$this->assertEquals('Penny', $recipient->getLabel());
		$this->assertEquals('penny@stardew-library.edu', $recipient->getEmail());
	}
}
