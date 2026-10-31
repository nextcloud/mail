<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\TrustedSenderMapper;
use OCA\Mail\Exception\InvalidAddressException;
use OCA\Mail\Service\AddressOrDomainNormalizer;
use OCA\Mail\Service\TrustedSenderService;
use OCP\DB\Exception;
use PHPUnit\Framework\MockObject\MockObject;

class TrustedSenderServiceTest extends TestCase {
	/** @var TrustedSenderMapper|MockObject */
	private $mapper;

	private AddressOrDomainNormalizer&MockObject $normalizer;

	/** @var TrustedSenderService */
	private $service;

	protected function setUp(): void {
		$this->mapper = $this->createMock(TrustedSenderMapper::class);
		$this->normalizer = $this->createMock(AddressOrDomainNormalizer::class);

		$this->service = new TrustedSenderService(
			$this->mapper,
			$this->normalizer
		);
	}

	public function testIsTrusted(): void {
		$uid = 'greta';
		$email = 'christoph@next.cloud';
		$this->mapper->expects($this->once())
			->method('exists')
			->with($uid, $email)
			->willReturn(true);

		$trusted = $this->service->isTrusted(
			$uid,
			$email
		);

		$this->assertTrue($trusted);
	}

	public function testIsNotTrusted(): void {
		$uid = 'greta';
		$email = 'christoph@next.cloud';
		$this->mapper->expects($this->once())
			->method('exists')
			->with($uid, $email)
			->willReturn(false);

		$trusted = $this->service->isTrusted(
			$uid,
			$email
		);

		$this->assertFalse($trusted);
	}

	public function testTrustAlreadyTrusted(): void {
		$uid = 'greta';
		$email = 'christoph@next.cloud';
		$this->normalizer->method('normalize')->willReturnArgument(0);
		$this->mapper->expects($this->once())
			->method('exists')
			->with($uid, $email)
			->willReturn(true);
		$this->mapper->expects($this->never())
			->method('create');
		$this->mapper->expects($this->never())
			->method('remove');

		$this->service->trust(
			$uid,
			$email,
			'individual'
		);
	}

	public function testTrustNew(): void {
		$uid = 'greta';
		$email = 'christoph@next.cloud';
		$this->normalizer->method('normalize')->willReturnArgument(0);
		$this->mapper->expects($this->once())
			->method('exists')
			->with($uid, $email)
			->willReturn(false);
		$this->mapper->expects($this->once())
			->method('create')
			->with($uid, $email);

		$this->service->trust(
			$uid,
			$email,
			'individual'
		);
	}

	public function testTrustAlreadyStoredDomain(): void {
		$uid = 'alice';
		$domain = 'example.test';
		$this->normalizer->method('normalize')->willReturnArgument(0);
		$uniqueViolation = $this->createMock(Exception::class);
		$uniqueViolation->method('getReason')->willReturn(Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION);
		$this->mapper->expects($this->once())
			->method('exists')
			->with($uid, $domain)
			->willReturn(false);
		$this->mapper->expects($this->once())
			->method('create')
			->with($uid, $domain, 'domain')
			->willThrowException($uniqueViolation);

		$this->service->trust(
			$uid,
			$domain,
			'domain'
		);
	}

	public function testTrustRethrowsOtherDatabaseErrors(): void {
		$uid = 'alice';
		$domain = 'example.test';
		$this->normalizer->method('normalize')->willReturnArgument(0);
		$databaseError = $this->createMock(Exception::class);
		$databaseError->method('getReason')->willReturn(Exception::REASON_CONNECTION_LOST);
		$this->mapper->method('exists')
			->willReturn(false);
		$this->mapper->method('create')
			->willThrowException($databaseError);

		$this->expectExceptionObject($databaseError);

		$this->service->trust(
			$uid,
			$domain,
			'domain'
		);
	}

	public function testTrustRejectsAnInvalidEntry(): void {
		$this->normalizer->expects($this->once())
			->method('normalize')
			->with('not valid', 'domain')
			->willThrowException(new InvalidAddressException('Invalid domain'));
		$this->mapper->expects($this->never())
			->method('exists');
		$this->mapper->expects($this->never())
			->method('create');

		$this->expectException(InvalidAddressException::class);

		$this->service->trust('alice', 'not valid', 'domain');
	}

	public function testTrustStoresTheNormalizedValue(): void {
		$this->normalizer->expects($this->once())
			->method('normalize')
			->with('Example.TEST', 'domain')
			->willReturn('example.test');
		$this->mapper->expects($this->once())
			->method('exists')
			->with('alice', 'example.test')
			->willReturn(false);
		$this->mapper->expects($this->once())
			->method('create')
			->with('alice', 'example.test', 'domain');

		$this->service->trust('alice', 'Example.TEST', 'domain');
	}

	public function testRemoveTrust(): void {
		$uid = 'greta';
		$email = 'christoph@next.cloud';
		$this->normalizer->expects($this->never())
			->method('normalize');
		$this->mapper->expects($this->never())
			->method('exists');
		$this->mapper->expects($this->never())
			->method('create');
		$this->mapper->expects($this->once())
			->method('remove')
			->with($uid, $email);

		$this->service->trust(
			$uid,
			$email,
			'individual',
			false
		);
	}
}
