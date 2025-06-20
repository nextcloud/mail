<?php

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Phishing;

use ChristophWurst\Nextcloud\Testing\TestCase;

use DateTime;
use OCA\Mail\Service\PhishingDetection\DateCheck;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IL10N;

use PHPUnit\Framework\MockObject\MockObject;

class DateCheckTest extends TestCase {

	private IL10N|MockObject $l10n;
	private DateCheck|MockObject $service;
	private ITimeFactory|MockObject $time;

	protected function setUp(): void {
		parent::setUp();
		$this->l10n = $this->createMock(IL10N::class);
		$this->time = $this->createMock(ITimeFactory::class);
		$this->service = new DateCheck($this->l10n, $this->time);
	}

	public function testInThePast(): void {

		$this->time->expects($this->exactly(2))
			->method('getDateTime')
			->withConsecutive(
				['now'],
				['26 June 2024 22:45:34 +0000']
			)
			->willReturnOnConsecutiveCalls(
				new DateTime('now'),
				new DateTime('26 June 2024 22:45:34 +0000')
			);

		$result = $this->service->run('26 June 2024 22:45:34 +0000');

		$this->assertFalse($result->isPhishing());
	}

	public function testInTheFuture(): void {


		$this->time->expects($this->exactly(2))
			->method('getDateTime')
			->withConsecutive(
				['now'],
				['17 June 3000 22:45:34 +0000']
			)
			->willReturnOnConsecutiveCalls(
				new DateTime('now'),
				new DateTime('17 June 3000 22:45:34 +0000')
			);

		$result = $this->service->run('17 June 3000 22:45:34 +0000');

		$this->assertTrue($result->isPhishing());
	}

	public function testInvalidDate(): void {

		$this->time->expects($this->exactly(2))
			->method('getDateTime')
			->willReturnCallback(function ($argument): DateTime {
				return match ($argument) {
					'now' => new \DateTime('now'),
					'invalid date' => throw new \DateException()
				};
			});

		$result = $this->service->run('invalid date');

		$this->assertFalse($result->isPhishing());
	}

}
