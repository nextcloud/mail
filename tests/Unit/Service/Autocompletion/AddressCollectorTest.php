<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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

	public function testAddAddresses() {
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
			->method('exists')
			->withConsecutive(
				[$this->userId, 'user@example.com'],
				[$this->userId, 'example@user.com']
			)
			->willReturnOnConsecutiveCalls(
				false,
				false
			);
		$this->mapper->expects($this->exactly(2))
			->method('insert')
			->withConsecutive(
				[$address1],
				[$address2]
			);

		$this->collector->addAddresses($this->userId, $addressList);
	}

	public function testAddDuplicateAddresses() {
		$addresses = [
			'user@example.com',
		];
		$addressList = AddressList::parse($addresses);

		$this->mapper->expects($this->once())
			->method('exists')
			->with($this->userId, $addresses[0])
			->will($this->returnValue(true));
		$this->mapper->expects($this->never())
			->method('insert');

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
