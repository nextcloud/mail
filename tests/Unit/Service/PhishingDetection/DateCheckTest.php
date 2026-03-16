<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\PhishingDetection;

use ChristophWurst\Nextcloud\Testing\TestCase;
use DateTime;
use OCA\Mail\Service\PhishingDetection\DateCheck;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;

class DateCheckTest extends TestCase {
	private DateCheck $check;
	private IL10N $l10n;
	private ITimeFactory $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->l10n = $this->createMock(IL10N::class);
		$this->l10n->method('t')->willReturnArgument(0);

		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->check = new DateCheck($this->l10n, $this->timeFactory);
	}

	public function testFutureDateReturnsWarning(): void {
		$now = new DateTime('2026-03-16 12:00:00');
		$futureDate = '2026-03-16 13:00:00';

		$this->timeFactory->method('getDateTime')->willReturnCallback(function ($date) use ($now) {
			if ($date === 'now') {
				return $now;
			}
			return new DateTime($date);
		});

		$result = $this->check->run($futureDate);

		$this->assertTrue($result->isPhishing());
	}

	public function testPastDateReturnsSafe(): void {
		$now = new DateTime('2026-03-16 12:00:00');
		$pastDate = '2026-03-16 11:00:00';

		$this->timeFactory->method('getDateTime')->willReturnCallback(function ($date) use ($now) {
			if ($date === 'now') {
				return $now;
			}
			return new DateTime($date);
		});

		$result = $this->check->run($pastDate);

		$this->assertFalse($result->isPhishing());
	}

	public function testInvalidDateFormatReturnsSafe(): void {
		$now = new DateTime('2026-03-16 12:00:00');

		$this->timeFactory->method('getDateTime')->willReturnCallback(function ($date) use ($now) {
			if ($date === 'now') {
				return $now;
			}
			throw new \Exception('Invalid date format');
		});

		$result = $this->check->run('invalid-date-format');

		$this->assertFalse($result->isPhishing());
	}
}
