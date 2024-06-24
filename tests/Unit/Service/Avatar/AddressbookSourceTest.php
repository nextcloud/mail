<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Avatar;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\Avatar\AddressbookSource;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\Avatar\AvatarFactory;
use OCA\Mail\Service\ContactsIntegration;
use PHPUnit_Framework_MockObject_MockObject;

class AddressbookSourceTest extends TestCase {
	/** @var ContactsIntegration|PHPUnit_Framework_MockObject_MockObject */
	private $ci;

	/** @var AddressbookSource */
	private $source;

	protected function setUp(): void {
		parent::setUp();

		$this->ci = $this->createMock(ContactsIntegration::class);

		$this->source = new AddressbookSource($this->ci);
	}

	public function testFetch() {
		$email = 'john@doe.com';
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->ci->expects($this->once())
			->method('getPhoto')
			->willReturn('https://next.cloud/photo');
		$avatar = new Avatar('https://next.cloud/photo');
		$avatarFactory->expects($this->once())
			->method('createInternal')
			->with('https://next.cloud/photo')
			->willReturn($avatar);

		$actualAvatar = $this->source->fetch($email, $avatarFactory);

		$this->assertSame($avatar, $actualAvatar);
	}

	public function testFetchNoneFound() {
		$email = 'john@doe.com';
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->ci->expects($this->once())
			->method('getPhoto')
			->willReturn(null);

		$avatar = $this->source->fetch($email, $avatarFactory);

		$this->assertNull($avatar);
	}
}
