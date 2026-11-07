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
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;

class AddressbookSourceTest extends TestCase {
	/** @var ContactsIntegration|MockObject */
	private $ci;

	/** @var IConfig|MockObject */
	private $config;

	/** @var AddressbookSource */
	private $source;

	protected function setUp(): void {
		parent::setUp();

		$this->ci = $this->createMock(ContactsIntegration::class);

		$this->config = $this->createMock(IConfig::class);
		$this->source = new AddressbookSource($this->ci, $this->config);
	}

	public function testFetch() {
		$email = 'john@doe.com';
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->ci->expects($this->once())
			->method('getPhoto')
			->with('currentUser', $email)
			->willReturn('https://next.cloud/photo');
		$avatar = new Avatar('https://next.cloud/photo');
		$avatarFactory->expects($this->once())
			->method('createInternal')
			->with('https://next.cloud/photo')
			->willReturn($avatar);

		$actualAvatar = $this->source->fetch('currentUser', $email, $avatarFactory);

		$this->assertSame($avatar, $actualAvatar);
	}

	public function testFetchNoneFound() {
		$email = 'john@doe.com';
		$avatarFactory = $this->createStub(AvatarFactory::class);
		$this->ci->expects($this->once())
			->method('getPhoto')
			->with('currentUser', $email)
			->willReturn(null);

		$avatar = $this->source->fetch('currentUser', $email, $avatarFactory);

		$this->assertNull($avatar);
	}

	public function testFetchSkipsGeneratedSystemAvatar() {
		$email = 'oponce@example.com';
		$url = 'https://cloud.example.com/remote.php/dav/addressbooks/system/system/system/Database:OPF.vcf?photo';
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$this->ci->method('getPhoto')->with('currentUser', $email)->willReturn($url);
		$this->config->expects($this->once())
			->method('getUserValue')
			->with('OPF', 'avatar', 'generated', 'false')
			->willReturn('true');
		$avatarFactory->expects($this->never())->method('createInternal');

		$this->assertNull($this->source->fetch('currentUser', $email, $avatarFactory));
	}

	public function testFetchPreservesCustomSystemAvatar() {
		$email = 'jgg@example.com';
		$url = 'https://cloud.example.com/remote.php/dav/addressbooks/system/system/system/Database:JGG.vcf?photo';
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$avatar = new Avatar($url, null, false);
		$this->ci->method('getPhoto')->with('currentUser', $email)->willReturn($url);
		$this->config->expects($this->once())
			->method('getUserValue')
			->with('JGG', 'avatar', 'generated', 'false')
			->willReturn('false');
		$avatarFactory->expects($this->once())->method('createInternal')->with($url)->willReturn($avatar);

		$this->assertSame($avatar, $this->source->fetch('currentUser', $email, $avatarFactory));
	}

	public function testFetchPreservesPersonalContactPhoto() {
		$email = 'person@example.com';
		$url = 'https://cloud.example.com/remote.php/dav/addressbooks/users/me/contacts/person.vcf?photo';
		$avatarFactory = $this->createMock(AvatarFactory::class);
		$avatar = new Avatar($url, null, false);
		$this->ci->method('getPhoto')->with('currentUser', $email)->willReturn($url);
		$this->config->expects($this->never())->method('getUserValue');
		$avatarFactory->expects($this->once())->method('createInternal')->with($url)->willReturn($avatar);

		$this->assertSame($avatar, $this->source->fetch('currentUser', $email, $avatarFactory));
	}

}
