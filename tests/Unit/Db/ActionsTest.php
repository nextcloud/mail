<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Db;

use OCA\Mail\Db\Actions;
use PHPUnit\Framework\TestCase;

final class ActionsTest extends TestCase {
	private Actions $entity;

	protected function setUp(): void {
		$this->entity = new Actions();
	}

	public function testConstructorInitializesDefaults(): void {
		$entity = new Actions();
		$json = $entity->jsonSerialize();

		$this->assertIsArray($json);
		$this->assertSame([], $json['actionSteps']);
		$this->assertSame('', $json['icon']);
	}

	public function testSetGetName(): void {
		$name = 'Move to Archive';

		$this->entity->setName($name);
		$result = $this->entity->getName();

		$this->assertSame($name, $result);
		$this->assertIsString($result);
	}

	public function testSetGetAccountId(): void {
		$accountId = 42;

		$this->entity->setAccountId($accountId);
		$result = $this->entity->getAccountId();

		$this->assertSame($accountId, $result);
		$this->assertIsInt($result);
	}

	public function testSetGetActionSteps(): void {
		$steps = [
			['type' => 'move', 'mailbox' => 'Archive'],
			['type' => 'mark', 'flag' => 'seen'],
		];

		$this->entity->setActionSteps($steps);
		$json = $this->entity->jsonSerialize();

		$this->assertSame($steps, $json['actionSteps']);
	}

	public function testSetGetIcon(): void {
		$icon = 'icon-archive';

		$this->entity->setIcon($icon);
		$json = $this->entity->jsonSerialize();

		$this->assertSame($icon, $json['icon']);
	}

	public function testSetEmptyName(): void {
		$this->entity->setName('');

		$this->assertSame('', $this->entity->getName());
	}

	public function testSetZeroAccountId(): void {
		$this->entity->setAccountId(0);

		$this->assertSame(0, $this->entity->getAccountId());
	}

	public function testSetNegativeAccountId(): void {
		$this->entity->setAccountId(-1);

		$this->assertSame(-1, $this->entity->getAccountId());
	}

	public function testSetLargeAccountId(): void {
		$largeId = 9223372036854775807; // Max int64

		$this->entity->setAccountId($largeId);

		$this->assertSame($largeId, $this->entity->getAccountId());
	}

	public function testSetEmptyActionSteps(): void {
		$this->entity->setActionSteps([]);
		$json = $this->entity->jsonSerialize();

		$this->assertSame([], $json['actionSteps']);
	}

	public function testSetComplexActionSteps(): void {
		$steps = [
			['type' => 'move', 'mailbox' => 'Archive', 'priority' => 1],
			['type' => 'mark', 'flag' => 'seen', 'value' => true],
			['type' => 'forward', 'to' => 'user@example.com', 'subject' => 'FWD: $subject'],
		];

		$this->entity->setActionSteps($steps);
		$json = $this->entity->jsonSerialize();

		$this->assertCount(3, $json['actionSteps']);
		$this->assertSame('Archive', $json['actionSteps'][0]['mailbox']);
		$this->assertSame('user@example.com', $json['actionSteps'][2]['to']);
	}

	public function testSetEmptyIcon(): void {
		$this->entity->setIcon('');

		$json = $this->entity->jsonSerialize();
		$this->assertSame('', $json['icon']);
	}

	public function testSetIconWithSpecialCharacters(): void {
		$icon = 'icon-archive-v2.0.svg#special-chars_äöü';

		$this->entity->setIcon($icon);
		$json = $this->entity->jsonSerialize();

		$this->assertSame($icon, $json['icon']);
	}

	public function testJsonSerializeAllFields(): void {
		$this->entity->setName('Test Action');
		$this->entity->setAccountId(99);
		$this->entity->setActionSteps([['type' => 'delete']]);
		$this->entity->setIcon('icon-delete');

		$json = $this->entity->jsonSerialize();

		$this->assertIsArray($json);
		$this->assertArrayHasKey('id', $json);
		$this->assertArrayHasKey('name', $json);
		$this->assertArrayHasKey('accountId', $json);
		$this->assertArrayHasKey('actionSteps', $json);
		$this->assertArrayHasKey('icon', $json);
		$this->assertSame('Test Action', $json['name']);
		$this->assertSame(99, $json['accountId']);
		$this->assertCount(1, $json['actionSteps']);
		$this->assertSame('icon-delete', $json['icon']);
	}

	public function testJsonSerializeMinimalFields(): void {
		$json = $this->entity->jsonSerialize();

		$this->assertIsArray($json);
		$this->assertArrayHasKey('id', $json);
		$this->assertArrayHasKey('name', $json);
		$this->assertArrayHasKey('accountId', $json);
		$this->assertArrayHasKey('actionSteps', $json);
		$this->assertArrayHasKey('icon', $json);
	}

	public function testMultipleSetActionStepsOverwriteValue(): void {
		$first = [['type' => 'move']];
		$second = [['type' => 'delete']];
		$final = [['type' => 'mark']];

		$this->entity->setActionSteps($first);
		$this->entity->setActionSteps($second);
		$this->entity->setActionSteps($final);

		$json = $this->entity->jsonSerialize();
		$this->assertSame($final, $json['actionSteps']);
	}

	public function testActionStepsWithNestedArrays(): void {
		$steps = [
			[
				'type' => 'move',
				'mailbox' => 'Archive',
				'conditions' => [
					['from' => 'sender@example.com'],
					['subject' => 'Invoice'],
				],
			],
		];

		$this->entity->setActionSteps($steps);
		$json = $this->entity->jsonSerialize();

		$this->assertSame('Archive', $json['actionSteps'][0]['mailbox']);
		$this->assertCount(2, $json['actionSteps'][0]['conditions']);
	}
}
