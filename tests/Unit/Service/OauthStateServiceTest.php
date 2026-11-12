<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Exception\InvalidOauthStateException;
use OCA\Mail\Service\OauthStateService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Security\ICrypto;
use PHPUnit\Framework\MockObject\MockObject;

class OauthStateServiceTest extends TestCase {
	private ICrypto&MockObject $crypto;
	private ITimeFactory&MockObject $time;
	private OauthStateService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->crypto = $this->createMock(ICrypto::class);
		$this->time = $this->createMock(ITimeFactory::class);

		$this->service = new OauthStateService(
			$this->crypto,
			$this->time,
		);
	}

	public function testCreateStateReturnsThreeParts(): void {
		$this->time->method('getTime')->willReturn(1000);
		$this->crypto->method('calculateHMAC')->willReturn('abc123');

		$state = $this->service->createState(42, 'alice');

		$parts = explode('.', $state);
		$this->assertCount(3, $parts);
		$this->assertEquals('42', $parts[0]);
		$this->assertEquals('1000', $parts[1]);
		$this->assertEquals(bin2hex('abc123'), $parts[2]);
	}

	public function testCreateStateBindsUserId(): void {
		$this->time->method('getTime')->willReturn(1000);

		$this->crypto->expects($this->exactly(2))
			->method('calculateHMAC')
			->willReturnOnConsecutiveCalls('hmac-alice', 'hmac-bob');

		$stateAlice = $this->service->createState(42, 'alice');
		$stateBob = $this->service->createState(42, 'bob');

		$this->assertNotEquals($stateAlice, $stateBob);
	}

	public function testValidateAndConsumeSuccess(): void {
		$this->time->method('getTime')->willReturn(1000);
		$this->crypto->method('calculateHMAC')
			->with('42.alice.900')
			->willReturn('validhmac');

		$accountId = $this->service->validateAndConsume('42.900.' . bin2hex('validhmac'), 'alice');

		$this->assertEquals(42, $accountId);
	}

	public function testValidateAndConsumeThrowsOnMalformedState(): void {
		$this->expectException(InvalidOauthStateException::class);

		$this->service->validateAndConsume('notvalid', 'alice');
	}

	public function testValidateAndConsumeThrowsOnTwoParts(): void {
		$this->expectException(InvalidOauthStateException::class);

		$this->service->validateAndConsume('42.900', 'alice');
	}

	public function testValidateAndConsumeThrowsOnHmacMismatch(): void {
		$this->time->method('getTime')->willReturn(1000);
		$this->crypto->method('calculateHMAC')
			->with('42.alice.900')
			->willReturn('expected-hmac');

		$this->expectException(InvalidOauthStateException::class);

		$this->service->validateAndConsume('42.900.tampered-hmac', 'alice');
	}

	public function testValidateAndConsumeThrowsOnExpiredToken(): void {
		$this->time->method('getTime')->willReturn(1700);
		$this->crypto->method('calculateHMAC')
			->with('42.alice.900')
			->willReturn('validhmac');

		$this->expectException(InvalidOauthStateException::class);

		$this->service->validateAndConsume('42.900.' . bin2hex('validhmac'), 'alice');
	}

	public function testValidateAndConsumeThrowsOnUserMismatch(): void {
		$this->time->method('getTime')->willReturn(1000);
		$this->crypto->method('calculateHMAC')
			->willReturnCallback(static fn (string $message): string => str_contains($message, 'alice') ? 'hmac-alice' : 'hmac-mallory');

		$this->expectException(InvalidOauthStateException::class);

		$this->service->validateAndConsume('42.900.' . bin2hex('hmac-alice'), 'mallory');
	}
}
