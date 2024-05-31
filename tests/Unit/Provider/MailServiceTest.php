<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Sebastian Krupinski <krupinski01@gmail.com>
 *
 * @author Sebastian Krupinski <krupinski01@gmail.com>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Tests\Unit\Provider;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Provider\MailService;
use OCA\Mail\Provider\MailServiceIdentity;
use OCA\Mail\Provider\MailServiceLocation;
use OCP\Mail\Provider\Address;
use Psr\Container\ContainerInterface;

class MailServiceTest extends TestCase {
	/** @var MailService*/
	private $mailService;
	/** @var MailServiceIdentity*/
	private $mailServiceIdentity;
	/** @var MailServiceLocation*/
	private $mailServiceLocation;
	/** @var Address*/
	private $primaryAddress;

	protected function setUp(): void {
		parent::setUp();

		$container = $this->createMock(ContainerInterface::class);

		$this->primaryAddress = new Address('test@testing.com', 'Tester');
		$this->mailServiceIdentity = new MailServiceIdentity();
		$this->mailServiceLocation = new MailServiceLocation();

		$this->mailService = new MailService(
			$container,
			'user1',
			'service1',
			'Mail Service',
			$this->primaryAddress,
			$this->mailServiceIdentity,
			$this->mailServiceLocation
		);
	}

	public function testId(): void {
		
		$this->assertEquals('service1', $this->mailService->id());

	}

	public function testCapable(): void {
		
		// test matched result
		$this->assertEquals(true, $this->mailService->capable('MessageSend'));
		// test not matched result
		$this->assertEquals(false, $this->mailService->capable('NoMatch'));
		// test collection result
		$this->assertEquals([
			'MessageSend' => true,
		], $this->mailService->capable());

	}

	public function testLabel(): void {
		
		// test set by constructor
		$this->assertEquals('Mail Service', $this->mailService->getLabel());
		// test set by setter
		$this->mailService->setLabel('Mail Service 2');
		$this->assertEquals('Mail Service 2', $this->mailService->getLabel());

	}

	public function testIdentity(): void {
		
		// test set by constructor
		$this->assertEquals($this->mailServiceIdentity, $this->mailService->getIdentity());

	}

	public function testLocation(): void {
		
		// test set by constructor
		$this->assertEquals($this->mailServiceLocation, $this->mailService->getLocation());

	}

	public function testPrimaryAddress(): void {
		
		// test set by constructor
		$this->assertEquals($this->primaryAddress, $this->mailService->getPrimaryAddress());
		// test set by setter
		$address = new Address('tester@testing.com');
		$this->mailService->setPrimaryAddress($address);
		$this->assertEquals($address, $this->mailService->getPrimaryAddress());

	}

	public function testSecondaryAddress(): void {
		
		// test set by setter
		$address1 = new Address('test1@testing.com');
		$address2 = new Address('test2@testing.com');
		$this->mailService->setSecondaryAddress($address1, $address2);
		$this->assertEquals([$address1, $address2], $this->mailService->getSecondaryAddress());

	}

}
