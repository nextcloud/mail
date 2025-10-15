<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service\Autocompletion;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\AddressList;
use OCA\Mail\Db\CollectedAddress;
use OCA\Mail\Db\CollectedAddressMapper;
use OCA\Mail\Service\AutoCompletion\AddressCollector;
use Psr\Log\LoggerInterface;

class AddressCollectorTest extends TestCase {
	private $mapper;
	private $userId = 'testuser';
	private $logger;
	private $collector;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(CollectedAddressMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);

		$this->collector = new AddressCollector(
			$this->mapper,
			$this->logger
		);
	}

	public function testAddAddresses(): void {
		$addresses = [
			'"User" <user@example.com>',
			'Example <example@user.com>',
		];
		$addressList = AddressList::parse($addresses);
		$address1 = new CollectedAddress();
		$address1->setDisplayName('User');
		$address1->setEmail('user@example.com');
		$address1->setUserId($this->userId);
		$address2 = new CollectedAddress();
		$address2->setDisplayName('Example');
		$address2->setEmail('example@user.com');
		$address2->setUserId($this->userId);

		$this->mapper->expects($this->exactly(2))
			->method('insertIfNew')
			->withConsecutive(
				[$this->userId, 'user@example.com'],
				[$this->userId, 'example@user.com']
			)
			->willReturnOnConsecutiveCalls(
				true,
				true,
			);

		$this->collector->addAddresses($this->userId, $addressList);
	}

	public function testAddDuplicateAddresses(): void {
		$addresses = [
			'user@example.com',
		];
		$addressList = AddressList::parse($addresses);

		$this->mapper->expects($this->once())
			->method('insertIfNew')
			->with($this->userId, $addresses[0])
			->willReturn(false);

		$this->collector->addAddresses($this->userId, $addressList);
	}

	public function testSearchAddress() {
		$term = 'john';
		$mapperResult = ['some', 'data'];

		$this->mapper->expects($this->once())
			->method('findMatching')
			->with($this->userId, $term)
			->will($this->returnValue($mapperResult));

		$result = $this->collector->searchAddress($this->userId, $term);

		$this->assertequals($mapperResult, $result);
	}
}
