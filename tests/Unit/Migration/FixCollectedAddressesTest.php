<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Migration;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\CollectedAddress;
use OCA\Mail\Db\CollectedAddressMapper;
use OCA\Mail\Migration\FixCollectedAddresses;
use OCP\Migration\IOutput;

/**
 * @group DB
 */
class FixCollectedAddressesTest extends TestCase {
	/** @var CollectedAddressMapper */
	private $mapper;

	/** @var IOutput */
	private $output;

	/** @var FixCollectedAddresses */
	private $repairStep;

	protected function setUp(): void {
		parent::setUp();

		$this->mapper = $this->createMock(CollectedAddressMapper::class);
		$this->output = $this->createMock(IOutput::class);
		$this->repairStep = new FixCollectedAddresses($this->mapper);
	}

	public function testRunNothingToMigrate() {
		$address1 = new CollectedAddress();
		$address1->setId(100);
		$address1->setEmail('user1@domain1.com');
		$address1->setDisplayName('User 1');
		$address2 = new CollectedAddress();
		$address2->setId(200);
		$address2->setEmail('user2@domain2.com');
		$address2->setDisplayName('User 2');

		$this->mapper->expects($this->exactly(2))
			->method('getChunk')
			->will($this->returnValueMap([
				[
					null, [
						$address1,
						$address2,
					]],
				[
					201, []]
			]));
		$this->mapper->expects($this->never())
			->method('update');

		$this->repairStep->run($this->output);
	}

	public function testRunMigrateOne() {
		$address1 = new CollectedAddress();
		$address1->setId(100);
		$address1->setEmail('"User 1" <user1@domain1.com>');
		$address1->setDisplayName(null);
		$address2 = new CollectedAddress();
		$address2->setId(200);
		$address2->setEmail('user2@domain2.com');
		$address2->setDisplayName('User 2');

		$this->mapper->expects($this->exactly(2))
			->method('getChunk')
			->will($this->returnValueMap([
				[
					null, [
						$address1,
						$address2,
					]],
				[
					201, []]
			]));
		$this->mapper->expects($this->once())
			->method('update')
			->with($address1);

		$this->repairStep->run($this->output);

		$this->assertEquals('user1@domain1.com', $address1->getEmail());
		$this->assertEquals('User 1', $address1->getDisplayName());
	}

	public function testRunDeleteFaulty() {
		$address1 = new CollectedAddress();
		$address1->setId(100);
		$address1->setEmail('"User 1 <user1@domain1.com>>>');
		$address1->setDisplayName(null);
		$address2 = new CollectedAddress();
		$address2->setId(200);
		$address2->setEmail('user2@domain2.com');
		$address2->setDisplayName('User 2');

		$this->mapper->expects($this->exactly(2))
			->method('getChunk')
			->will($this->returnValueMap([
				[
					null, [
						$address1,
						$address2,
					]],
				[
					201, []]
			]));
		$this->mapper->expects($this->once())
			->method('delete')
			->with($address1);

		$this->repairStep->run($this->output);
	}
}
