<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Tests\Unit\Provider;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Provider\MailService;
use OCP\Mail\Provider\Address;
use Psr\Container\ContainerInterface;

class MailServiceTest extends TestCase {
	/** @var MailService*/
	private $mailService;
	/** @var Address*/
	private $primaryAddress;

	protected function setUp(): void {
		parent::setUp();

		$container = $this->createMock(ContainerInterface::class);

		$this->primaryAddress = new Address('test@testing.com', 'Tester');

		$this->mailService = new MailService(
			$container,
			'user1',
			'service1',
			'Mail Service',
			$this->primaryAddress
		);
	}

	public function testId(): void {
		
		$this->assertEquals('service1', $this->mailService->id());

	}

	public function testCapable(): void {
		
		// test matched result
		$this->assertEquals(true, $this->mailService->capable('MessageSend'));

	}

	public function testCapabilities(): void {

		// test collection result
		$this->assertEquals([
			'MessageSend' => true,
		], $this->mailService->capabilities());

	}

	public function testLabel(): void {
		
		// test set by constructor
		$this->assertEquals('Mail Service', $this->mailService->getLabel());
		// test set by setter
		$this->mailService->setLabel('Mail Service 2');
		$this->assertEquals('Mail Service 2', $this->mailService->getLabel());

	}

	public function testPrimaryAddress(): void {
		
		// test set by constructor
		$this->assertEquals($this->primaryAddress, $this->mailService->getPrimaryAddress());
		// test set by setter
		$address = new Address('tester@testing.com');
		$this->mailService->setPrimaryAddress($address);
		$this->assertEquals($address, $this->mailService->getPrimaryAddress());

	}

	public function testSecondaryAddresses(): void {
		
		// test set by setter
		$address1 = new Address('test1@testing.com');
		$address2 = new Address('test2@testing.com');
		$this->mailService->setSecondaryAddresses($address1, $address2);
		$this->assertEquals([$address1, $address2], $this->mailService->getSecondaryAddresses());

	}

}
