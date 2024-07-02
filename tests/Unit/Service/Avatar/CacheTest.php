<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service\Avatar;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\Avatar\AvatarFactory;
use OCA\Mail\Service\Avatar\Cache;
use OCP\ICache;
use OCP\ICacheFactory;
use PHPUnit_Framework_MockObject_MockObject;

class CacheTest extends TestCase {
	public const BLACK_DOT_BASE64 = 'R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=';

	/** @var ICacheFactory|PHPUnit_Framework_MockObject_MockObject */
	private $cacheFactory;

	/** @var ICache|PHPUnit_Framework_MockObject_MockObject */
	private $cacheImpl;

	/** @var AvatarFactory|PHPUnit_Framework_MockObject_MockObject */
	private $avatarFactory;

	/** @var Cache */
	private $cache;

	protected function setUp(): void {
		parent::setUp();

		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cacheImpl = $this->createMock(ICache::class);
		$this->cacheFactory->expects($this->once())
			->method('createLocal')
			->with('mail.avatars')
			->willReturn($this->cacheImpl);
		$this->avatarFactory = $this->createMock(AvatarFactory::class);
		$this->cache = new Cache($this->cacheFactory, $this->avatarFactory);
	}

	public function testGetNonCachedAvatar() {
		$email = 'john@doe.com';
		$uid = 'jane';
		$this->cacheImpl->expects($this->once())
			->method('get')
			->with(base64_encode(json_encode([$email, $uid])))
			->willReturn(null);

		$cachedAvatar = $this->cache->get($email, $uid);

		$this->assertNull($cachedAvatar);
	}

	public function testGetCachedAvatar() {
		$email = 'john@doe.com';
		$uid = 'jane';
		$this->cacheImpl->expects($this->once())
			->method('get')
			->with(base64_encode(json_encode([$email, $uid])))
			->willReturn(['isExternal' => true, 'mime' => 'image/jpeg', 'url' => 'https://…']);
		$expected = new Avatar('https://…', 'image/jpeg');
		$this->avatarFactory->expects($this->once())
			->method('createExternal')
			->with('https://…', 'image/jpeg')
			->willReturn($expected);

		$cachedAvatar = $this->cache->get($email, $uid);

		$this->assertEquals($expected, $cachedAvatar);
	}

	public function testSetAvatar() {
		$email = 'john@doe.com';
		$uid = 'jane';
		$avatar = new Avatar('https://…', 'image/jpeg');
		$this->cacheImpl->expects($this->once())
			->method('set')
			->with(base64_encode(json_encode([$email, $uid])), ['isExternal' => true, 'mime' => 'image/jpeg', 'url' => 'https://…'], 7 * 24 * 60 * 60);

		$this->cache->add($email, $uid, $avatar);
	}

	public function testGetImage() {
		$uid = 'jane';
		$this->cacheImpl->expects($this->once())
			->method('get')
			->with(base64_encode(json_encode(['https://doe.com/favicon.ico', $uid])))
			->willReturn(self::BLACK_DOT_BASE64);

		$image = $this->cache->getImage('https://doe.com/favicon.ico', $uid);

		$this->assertEquals(self::BLACK_DOT_BASE64, $image);
	}

	public function testGetNonCachedImage() {
		$uid = 'jane';
		$this->cacheImpl->expects($this->once())
			->method('get')
			->with(base64_encode(json_encode(['https://doe.com/favicon.ico', $uid])))
			->willReturn(null);

		$image = $this->cache->getImage('https://doe.com/favicon.ico', $uid);

		$this->assertNull($image);
	}

	public function testAddImage() {
		$uid = 'jane';
		$this->cacheImpl->expects($this->once())
			->method('set')
			->with(base64_encode(json_encode(['https://doe.com/favicon.ico', $uid])), self::BLACK_DOT_BASE64, 7 * 24 * 60 * 60);

		$this->cache->addImage('https://doe.com/favicon.ico', $uid, self::BLACK_DOT_BASE64);
	}
}
