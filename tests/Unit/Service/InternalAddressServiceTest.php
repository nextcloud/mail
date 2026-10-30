<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\InternalAddress;
use OCA\Mail\Db\InternalAddressMapper;
use OCA\Mail\Exception\InvalidAddressException;
use OCA\Mail\Service\AddressOrDomainNormalizer;
use OCA\Mail\Service\InternalAddressService;
use OCP\DB\Exception;
use PHPUnit\Framework\MockObject\MockObject;

class InternalAddressServiceTest extends TestCase {
	private InternalAddressMapper&MockObject $mapper;
	private AddressOrDomainNormalizer&MockObject $normalizer;
	private InternalAddressService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(InternalAddressMapper::class);
		$this->normalizer = $this->createMock(AddressOrDomainNormalizer::class);

		$this->service = new InternalAddressService(
			$this->mapper,
			$this->normalizer
		);
	}

	public function testAddAlreadyInternal(): void {
		$this->normalizer->method('normalize')->willReturnArgument(0);
		$this->mapper->expects($this->once())
			->method('exists')
			->with('alice', 'bob@example.test')
			->willReturn(true);
		$this->mapper->expects($this->never())
			->method('create');
		$this->mapper->expects($this->never())
			->method('remove');

		$internalAddress = $this->service->add('alice', 'bob@example.test', 'individual');

		$this->assertNull($internalAddress);
	}

	public function testAddNew(): void {
		$this->normalizer->method('normalize')->willReturnArgument(0);
		$created = new InternalAddress();
		$created->setUserId('alice');
		$created->setAddress('example.test');
		$created->setType('domain');
		$this->mapper->expects($this->once())
			->method('exists')
			->with('alice', 'example.test')
			->willReturn(false);
		$this->mapper->expects($this->once())
			->method('create')
			->with('alice', 'example.test', 'domain')
			->willReturn(1);
		$this->mapper->expects($this->once())
			->method('find')
			->with('alice', 'example.test')
			->willReturn($created);

		$internalAddress = $this->service->add('alice', 'example.test', 'domain');

		$this->assertSame($created, $internalAddress);
	}

	public function testAddAlreadyStoredDomain(): void {
		$this->normalizer->method('normalize')->willReturnArgument(0);
		$uniqueViolation = $this->createMock(Exception::class);
		$uniqueViolation->method('getReason')->willReturn(Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION);
		$this->mapper->expects($this->once())
			->method('exists')
			->with('alice', 'example.test')
			->willReturn(false);
		$this->mapper->expects($this->once())
			->method('create')
			->with('alice', 'example.test', 'domain')
			->willThrowException($uniqueViolation);
		$this->mapper->expects($this->never())
			->method('find');

		$internalAddress = $this->service->add('alice', 'example.test', 'domain');

		$this->assertNull($internalAddress);
	}

	public function testAddRethrowsOtherDatabaseErrors(): void {
		$this->normalizer->method('normalize')->willReturnArgument(0);
		$databaseError = $this->createMock(Exception::class);
		$databaseError->method('getReason')->willReturn(Exception::REASON_CONNECTION_LOST);
		$this->mapper->method('exists')
			->willReturn(false);
		$this->mapper->method('create')
			->willThrowException($databaseError);

		$this->expectExceptionObject($databaseError);

		$this->service->add('alice', 'example.test', 'domain');
	}

	public function testAddRejectsAnInvalidEntry(): void {
		$this->normalizer->expects($this->once())
			->method('normalize')
			->with('not valid', 'domain')
			->willThrowException(new InvalidAddressException('Invalid domain'));
		$this->mapper->expects($this->never())
			->method('exists');
		$this->mapper->expects($this->never())
			->method('create');

		$this->expectException(InvalidAddressException::class);

		$this->service->add('alice', 'not valid', 'domain');
	}

	public function testAddStoresTheNormalizedValue(): void {
		$stored = new InternalAddress();
		$stored->setUserId('alice');
		$stored->setAddress('example.test');
		$stored->setType('domain');
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
			->with('alice', 'example.test', 'domain')
			->willReturn(1);
		$this->mapper->expects($this->once())
			->method('find')
			->with('alice', 'example.test')
			->willReturn($stored);

		$internalAddress = $this->service->add('alice', 'Example.TEST', 'domain');

		$this->assertSame($stored, $internalAddress);
	}

	public function testRemove(): void {
		$this->normalizer->expects($this->never())
			->method('normalize');
		$this->mapper->expects($this->never())
			->method('exists');
		$this->mapper->expects($this->never())
			->method('create');
		$this->mapper->expects($this->once())
			->method('remove')
			->with('alice', 'example.test', 'domain');

		$internalAddress = $this->service->add('alice', 'example.test', 'domain', false);

		$this->assertNull($internalAddress);
	}
}
