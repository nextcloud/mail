<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\TrustedSender;

class TrustedSenderTest extends TestCase {
	private TrustedSender $trustedSender;

	protected function setUp(): void {
		parent::setUp();

		$this->trustedSender = new TrustedSender();
	}

	public function testSetAndGetEmail(): void {
		$email = 'john@example.com';

		$this->trustedSender->setEmail($email);
		$result = $this->trustedSender->getEmail();

		$this->assertSame($email, $result);
	}

	public function testSetAndGetUserId(): void {
		$userId = 'user123';

		$this->trustedSender->setUserId($userId);
		$result = $this->trustedSender->getUserId();

		$this->assertSame($userId, $result);
	}

	public function testSetAndGetType(): void {
		$type = 'individual';

		$this->trustedSender->setType($type);
		$result = $this->trustedSender->getType();

		$this->assertSame($type, $result);
	}

	public function testJsonSerialize(): void {
		$email = 'alice@example.com';
		$userId = 'alice';
		$type = 'trusted';

		$this->trustedSender->setEmail($email);
		$this->trustedSender->setUserId($userId);
		$this->trustedSender->setType($type);

		$result = $this->trustedSender->jsonSerialize();

		$this->assertIsArray($result);
		$this->assertArrayHasKey('id', $result);
		$this->assertArrayHasKey('email', $result);
		$this->assertArrayHasKey('uid', $result);
		$this->assertArrayHasKey('type', $result);
		$this->assertSame($email, $result['email']);
		$this->assertSame($userId, $result['uid']);
		$this->assertSame($type, $result['type']);
	}

	public function testJsonSerializeStructure(): void {
		$email = 'bob@example.com';
		$userId = 'bob123';
		$type = 'group';

		$this->trustedSender->setEmail($email);
		$this->trustedSender->setUserId($userId);
		$this->trustedSender->setType($type);

		$serialized = $this->trustedSender->jsonSerialize();

		$this->assertCount(4, $serialized);
		$this->assertArrayHasKey('id', $serialized);
		$this->assertArrayHasKey('email', $serialized);
		$this->assertArrayHasKey('uid', $serialized);
		$this->assertArrayHasKey('type', $serialized);
	}

	public function testSetAndGetEmailWithMultipleAddresses(): void {
		$email1 = 'first@example.com';
		$email2 = 'second@example.com';

		$this->trustedSender->setEmail($email1);
		$this->assertSame($email1, $this->trustedSender->getEmail());

		$this->trustedSender->setEmail($email2);
		$this->assertSame($email2, $this->trustedSender->getEmail());
	}

	public function testSetAndGetUserIdWithVariousIds(): void {
		$userId1 = 'user_001';
		$userId2 = 'admin@example.com';

		$this->trustedSender->setUserId($userId1);
		$this->assertSame($userId1, $this->trustedSender->getUserId());

		$this->trustedSender->setUserId($userId2);
		$this->assertSame($userId2, $this->trustedSender->getUserId());
	}

	public function testSetAndGetTypeVariations(): void {
		$types = ['individual', 'group', 'organization'];

		foreach ($types as $type) {
			$this->trustedSender->setType($type);
			$this->assertSame($type, $this->trustedSender->getType());
		}
	}

	public function testJsonSerializeWithEmptyEmail(): void {
		$email = '';
		$userId = 'testuser';
		$type = 'trusted';

		$this->trustedSender->setEmail($email);
		$this->trustedSender->setUserId($userId);
		$this->trustedSender->setType($type);

		$result = $this->trustedSender->jsonSerialize();

		$this->assertSame($email, $result['email']);
		$this->assertSame($userId, $result['uid']);
		$this->assertSame($type, $result['type']);
	}
}
