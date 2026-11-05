<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\AddressEntryType;
use OCA\Mail\Db\InternalAddress;
use OCA\Mail\Db\InternalAddressMapper;
use OCA\Mail\Exception\InvalidAddressException;
use OCA\Mail\Service\AddressEntryNormalizer;
use OCA\Mail\Service\InternalAddressService;
use OCP\DB\Exception;
use PHPUnit\Framework\MockObject\MockObject;

class InternalAddressServiceTest extends TestCase {
	private InternalAddressMapper&MockObject $mapper;
	private AddressEntryNormalizer&MockObject $normalizer;
	private InternalAddressService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(InternalAddressMapper::class);
		$this->normalizer = $this->createMock(AddressEntryNormalizer::class);

		$this->service = new InternalAddressService(
			$this->mapper,
			$this->normalizer
		);
	}

	public function testAddAlreadyInternal(): void {
		$this->normalizer->method('normalize')->willReturnArgument(0);
		$uniqueViolation = $this->createMock(Exception::class);
		$uniqueViolation->method('getReason')->willReturn(Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION);
		$this->mapper->expects($this->once())
			->method('create')
			->with('alice', 'bob@example.test', 'individual')
			->willThrowException($uniqueViolation);
		$this->mapper->expects($this->never())
			->method('find');
		$this->mapper->expects($this->never())
			->method('remove');

		$internalAddress = $this->service->add('alice', 'bob@example.test', 'individual');

		$this->assertNull($internalAddress);
	}

	public function testAddRethrowsOtherDatabaseErrors(): void {
		$this->normalizer->method('normalize')->willReturnArgument(0);
		$databaseError = $this->createMock(Exception::class);
		$databaseError->method('getReason')->willReturn(Exception::REASON_CONNECTION_LOST);
		$this->mapper->method('create')
			->willThrowException($databaseError);

		$this->expectExceptionObject($databaseError);

		$this->service->add('alice', 'example.test', 'domain');
	}

	public function testAddRejectsAnInvalidEntry(): void {
		$this->normalizer->expects($this->once())
			->method('normalize')
			->with('not valid', AddressEntryType::Domain)
			->willThrowException(new InvalidAddressException('Invalid domain'));
		$this->mapper->expects($this->never())
			->method('create');

		$this->expectException(InvalidAddressException::class);

		$this->service->add('alice', 'not valid', 'domain');
	}

	public function testAddRejectsAnUnknownType(): void {
		$this->normalizer->expects($this->never())
			->method('normalize');
		$this->mapper->expects($this->never())
			->method('create');

		$this->expectException(InvalidAddressException::class);
		$this->expectExceptionMessage('Invalid type');

		$this->service->add('alice', 'example.test', 'group');
	}

	public static function provideEntries(): array {
		return [
			'individual' => [AddressEntryType::Individual, 'alice@example.test'],
			'domain' => [AddressEntryType::Domain, 'example.test'],
		];
	}

	/**
	 * @dataProvider provideEntries
	 */
	public function testAddNormalizesTheValueAsItsType(AddressEntryType $type, string $value): void {
		$this->normalizer->expects($this->once())
			->method('normalize')
			->with($value, $type)
			->willReturnArgument(0);

		$this->service->add('alice', $value, $type->value);
	}

	public function testAddStoresTheNormalizedValue(): void {
		$stored = new InternalAddress();
		$stored->setUserId('alice');
		$stored->setAddress('example.test');
		$stored->setType('domain');
		$this->normalizer->expects($this->once())
			->method('normalize')
			->with('Example.TEST', AddressEntryType::Domain)
			->willReturn('example.test');
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
			->method('create');
		$this->mapper->expects($this->once())
			->method('remove')
			->with('alice', 'example.test', 'domain');

		$internalAddress = $this->service->add('alice', 'example.test', 'domain', false);

		$this->assertNull($internalAddress);
	}

	public function testRemoveRemovesTheNormalizedEntryToo(): void {
		$this->normalizer->method('tryNormalize')
			->with('xn--bcher-kva.example', 'domain')
			->willReturn('bücher.example');
		$removed = [];
		$this->mapper->expects($this->exactly(2))
			->method('remove')
			->willReturnCallback(function (string $uid, string $address, string $type) use (&$removed): void {
				$removed[] = ['uid' => $uid, 'address' => $address, 'type' => $type];
			});

		$this->service->add('alice', 'xn--bcher-kva.example', 'domain', false);

		$this->assertEqualsCanonicalizing([
			['uid' => 'alice', 'address' => 'xn--bcher-kva.example', 'type' => 'domain'],
			['uid' => 'alice', 'address' => 'bücher.example', 'type' => 'domain'],
		], $removed);
	}

	public function testRemoveOfAStoredFormRemovesItOnce(): void {
		$this->normalizer->method('tryNormalize')
			->willReturnArgument(0);
		$this->mapper->expects($this->once())
			->method('remove')
			->with('alice', 'bücher.example', 'domain');

		$this->service->add('alice', 'bücher.example', 'domain', false);
	}

	public function testRemoveAcceptsAnUnknownType(): void {
		$this->mapper->expects($this->once())
			->method('remove')
			->with('alice', 'example.test', 'group');

		$internalAddress = $this->service->add('alice', 'example.test', 'group', false);

		$this->assertNull($internalAddress);
	}
}
