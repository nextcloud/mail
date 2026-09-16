<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Service\OutOfOffice;

use ChristophWurst\Nextcloud\Testing\TestCase;
use DateTimeImmutable;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeState;

class OutOfOfficeStateTest extends TestCase {
	public function testJsonWithForwardTo(): void {
		$start = new DateTimeImmutable('2024-01-01');
		$state = new OutOfOfficeState(
			true,
			$start,
			null,
			'Test Subject',
			'Test Message',
			'forward@example.org',
		);

		$json = $state->jsonSerialize();

		self::assertArrayHasKey('forwardTo', $json);
		self::assertSame('forward@example.org', $json['forwardTo']);
	}

	public function testJsonWithoutForwardTo(): void {
		$start = new DateTimeImmutable('2024-01-01');
		$state = new OutOfOfficeState(
			true,
			$start,
			null,
			'Test Subject',
			'Test Message',
			null,
		);

		$json = $state->jsonSerialize();

		self::assertArrayNotHasKey('forwardTo', $json);
	}

	public function testFromJsonWithForwardTo(): void {
		$data = [
			'enabled' => true,
			'start' => '2024-01-01T00:00:00+00:00',
			'subject' => 'Test',
			'message' => 'Message',
			'forwardTo' => 'forward@example.org',
			'version' => 1,
		];

		$state = OutOfOfficeState::fromJson($data);

		self::assertSame('forward@example.org', $state->getForwardTo());
	}

	public function testFromJsonWithoutForwardTo(): void {
		$data = [
			'enabled' => true,
			'start' => '2024-01-01T00:00:00+00:00',
			'subject' => 'Test',
			'message' => 'Message',
			'version' => 1,
		];

		$state = OutOfOfficeState::fromJson($data);

		self::assertNull($state->getForwardTo());
	}

	public function testSetForwardTo(): void {
		$state = new OutOfOfficeState(
			false,
			null,
			null,
			'',
			'',
		);

		$state->setForwardTo('new@example.org');
		self::assertSame('new@example.org', $state->getForwardTo());

		$state->setForwardTo(null);
		self::assertNull($state->getForwardTo());
	}
}
