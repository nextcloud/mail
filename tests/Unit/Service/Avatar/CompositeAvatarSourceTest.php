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
use OCA\Mail\Service\Avatar\CompositeAvatarSource;
use OCA\Mail\Service\Avatar\FaviconSource;
use OCA\Mail\Service\Avatar\GravatarSource;
use PHPUnit_Framework_MockObject_MockObject;

class CompositeAvatarSourceTest extends TestCase {
	/** @var AddressbookSource|PHPUnit_Framework_MockObject_MockObject */
	private $addressbookSource;

	/** @var GravatarSource|PHPUnit_Framework_MockObject_MockObject */
	private $gravatarSource;

	/** @var FaviconSource|PHPUnit_Framework_MockObject_MockObject */
	private $faviconSource;

	/** @var CompositeAvatarSource */
	private $source;

	protected function setUp(): void {
		parent::setUp();

		$this->addressbookSource = $this->createMock(AddressbookSource::class);
		$this->gravatarSource = $this->createMock(GravatarSource::class);
		$this->faviconSource = $this->createMock(FaviconSource::class);

		$this->source = new CompositeAvatarSource($this->addressbookSource, $this->faviconSource, $this->gravatarSource);
	}

	public function testFetchNoneFound() {
		$email = 'jane@doe.com';
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->addressbookSource->expects($this->any())
			->method('isExternal')
			->willReturn(false);
		$this->addressbookSource->expects($this->once())
			->method('fetch')
			->with($email, $avatarFactory)
			->willReturn(null);
		$this->gravatarSource->expects($this->any())
			->method('isExternal')
			->willReturn(true);
		$this->gravatarSource->expects($this->once())
			->method('fetch')
			->with($email, $avatarFactory)
			->willReturn(null);
		$this->faviconSource->expects($this->any())
			->method('isExternal')
			->willReturn(true);
		$this->faviconSource->expects($this->once())
			->method('fetch')
			->with($email, $avatarFactory)
			->willReturn(null);

		$actualAvatar = $this->source->fetch($email, $avatarFactory, true);

		$this->assertNull($actualAvatar);
	}

	public function testFetchNoneFoundQueryOnlyInternal() {
		$email = 'jane@doe.com';
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->addressbookSource->expects($this->once())
			->method('isExternal')
			->willReturn(false);
		$this->addressbookSource->expects($this->once())
			->method('fetch')
			->with($email, $avatarFactory)
			->willReturn(null);
		$this->gravatarSource->expects($this->once())
			->method('isExternal')
			->willReturn(true);
		$this->gravatarSource->expects($this->never())
			->method('fetch')
			->with($email, $avatarFactory)
			->willReturn(null);
		$this->faviconSource->expects($this->once())
			->method('isExternal')
			->willReturn(true);
		$this->faviconSource->expects($this->never())
			->method('fetch')
			->with($email, $avatarFactory)
			->willReturn(null);

		$actualAvatar = $this->source->fetch($email, $avatarFactory, false);

		$this->assertNull($actualAvatar);
	}

	public function testFetchFromGravatar() {
		$email = 'jane@doe.com';
		$avatar = new Avatar('https://gravatar.com', 'image/jpeg');
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->addressbookSource->expects($this->once())
			->method('fetch')
			->with($email, $avatarFactory)
			->willReturn(null);
		$this->gravatarSource->expects($this->once())
			->method('fetch')
			->with($email, $avatarFactory)
			->willReturn($avatar);

		$actualAvatar = $this->source->fetch($email, $avatarFactory, true);

		$this->assertEquals($avatar, $actualAvatar);
	}
}
