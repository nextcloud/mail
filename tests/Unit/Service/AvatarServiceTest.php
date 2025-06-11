<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\Avatar\AvatarFactory;
use OCA\Mail\Service\Avatar\Cache;
use OCA\Mail\Service\Avatar\CompositeAvatarSource;
use OCA\Mail\Service\Avatar\Downloader;
use OCA\Mail\Service\Avatar\IAvatarSource;
use OCA\Mail\Service\AvatarService;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;

class AvatarServiceTest extends TestCase {
	public const BLACK_DOT_BASE64 = 'R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=';

	/** @var IAvatarSource|MockObject */
	private $source;

	/** @var Downloader|MockObject */
	private $downloader;

	/** @var Cache|MockObject */
	private $cache;

	/** @var AvatarFactory|MockObject */
	private $avatarFactory;

	/** @var IUserPreferences */
	private $preferences;

	/** @var AvatarService */
	private $avatarService;

	/** @var IUser|MockObject */
	private $user;

	protected function setUp(): void {
		parent::setUp();

		$this->source = $this->createMock(CompositeAvatarSource::class);
		$this->downloader = $this->createMock(Downloader::class);
		$this->cache = $this->createMock(Cache::class);
		$this->avatarFactory = $this->createMock(AvatarFactory::class);
		$this->preferences = $this->createMock(IUserPreferences::class);

		$this->avatarService = new AvatarService(
			$this->source,
			$this->downloader,
			$this->cache,
			$this->avatarFactory,
			$this->preferences,
		);
	}

	public function testGetCachedAvatarUrl() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$avatar = new Avatar('https://doe.com/favicon.ico');
		$this->cache->expects($this->once())
			->method('get')
			->with($email, $uid)
			->willReturn($avatar);

		$result = $this->avatarService->getAvatar($email, $uid);

		$this->assertSame($avatar, $result);
	}

	public function testGetAvatarNoAvatarFound() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->preferences->expects($this->once())
			->method('getPreference')
			->with($uid, 'external-avatars', 'true')
			->willReturn('true');
		$this->cache->expects($this->once())
			->method('get')
			->with($email)
			->willReturn(null);
		$this->source->expects($this->once())
			->method('fetch')
			->with($email, $this->avatarFactory, true)
			->willReturn(null);
		$this->cache->expects($this->once())
			->method('add')
			->with($email, $uid, null);

		$url = $this->avatarService->getAvatar($email, $uid);

		$this->assertNull($url);
	}

	public function testGetAvatarMimeNotAllowed() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->preferences->expects($this->once())
			->method('getPreference')
			->with($uid, 'external-avatars', 'true')
			->willReturn('true');
		$this->cache->expects($this->once())
			->method('get')
			->with($email)
			->willReturn(null);
		$avatar = new Avatar('http://…', 'application/xml');
		$this->source->expects($this->once())
			->method('fetch')
			->with($email, $this->avatarFactory, true)
			->willReturn($avatar);
		$this->cache->expects($this->once())
			->method('add')
			->with($email, $uid, null);

		$url = $this->avatarService->getAvatar($email, $uid);

		$this->assertNull($url);
	}

	public function testGetAvatarOnlyInternalAllowed() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$avatar = new Avatar('https://doe.com/favicon.ico', 'image/png');
		$this->preferences->expects($this->once())
			->method('getPreference')
			->with($uid, 'external-avatars', 'true')
			->willReturn('false');
		$this->cache->expects($this->once())
			->method('get')
			->with($email)
			->willReturn(null);
		$this->source->expects($this->once())
			->method('fetch')
			->with($email, $this->avatarFactory, false)
			->willReturn($avatar);
		$this->cache->expects($this->once())
			->method('add')
			->with($email, $uid, $avatar);

		$actualAvatar = $this->avatarService->getAvatar($email, $uid);

		$this->assertEquals($avatar, $actualAvatar);
	}

	public function testGetAvatar() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$avatar = new Avatar('https://doe.com/favicon.ico', 'image/png');
		$this->preferences->expects($this->once())
			->method('getPreference')
			->with($uid, 'external-avatars', 'true')
			->willReturn('true');
		$this->cache->expects($this->once())
			->method('get')
			->with($email)
			->willReturn(null);
		$this->source->expects($this->once())
			->method('fetch')
			->with($email, $this->avatarFactory, true)
			->willReturn($avatar);
		$this->cache->expects($this->once())
			->method('add')
			->with($email, $uid, $avatar);

		$actualAvatar = $this->avatarService->getAvatar($email, $uid);

		$this->assertEquals($avatar, $actualAvatar);
	}

	public function testGetCachedAvatarImage() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$avatar = new Avatar('https://doe.com/favicon.ico', 'image/png');
		$this->cache->expects($this->once())
			->method('get')
			->with($email, $uid)
			->willReturn($avatar);
		$this->cache->expects($this->once())
			->method('getImage')
			->with('https://doe.com/favicon.ico', $uid)
			->willReturn(self::BLACK_DOT_BASE64);

		$data = $this->avatarService->getAvatarImage($email, $uid);

		$this->assertEquals([$avatar, base64_decode(self::BLACK_DOT_BASE64)], $data);
	}

	public function testGetAvatarImageNoUrlCached() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->cache->expects($this->once())
			->method('get')
			->with($email, $uid)
			->willReturn(null);

		$image = $this->avatarService->getAvatarImage($email, $uid);

		$this->assertNull($image);
	}

	public function testGetAvatarImageDownloadImage() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$avatar = new Avatar('https://doe.com/favicon.ico', 'image/jpg');
		$this->cache->expects($this->once())
			->method('get')
			->with($email, $uid)
			->willReturn($avatar);
		$this->cache->expects($this->once())
			->method('getImage')
			->with('https://doe.com/favicon.ico', $uid)
			->willReturn(null);
		$this->downloader->expects($this->once())
			->method('download')
			->with('https://doe.com/favicon.ico')
			->willReturn(base64_decode(self::BLACK_DOT_BASE64));
		$this->cache->expects($this->once())
			->method('addImage')
			->with('https://doe.com/favicon.ico', $uid, self::BLACK_DOT_BASE64);

		$data = $this->avatarService->getAvatarImage($email, $uid);

		$this->assertEquals([$avatar, base64_decode(self::BLACK_DOT_BASE64)], $data);
	}

	public function testGetAvatarImageDownloadImageFails() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$avatar = new Avatar('https://doe.com/favicon.ico', 'image/jpg');
		$this->cache->expects($this->once())
			->method('get')
			->with($email, $uid)
			->willReturn($avatar);
		$this->cache->expects($this->once())
			->method('getImage')
			->with('https://doe.com/favicon.ico', $uid)
			->willReturn(null);
		$this->downloader->expects($this->once())
			->method('download')
			->with('https://doe.com/favicon.ico')
			->willReturn(null);

		$image = $this->avatarService->getAvatarImage($email, $uid);

		$this->assertNull($image);
	}
}
