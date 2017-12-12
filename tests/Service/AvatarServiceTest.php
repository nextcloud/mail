<?php

/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
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

namespace OCA\Mail\Tests\Service;

use OCA\Mail\Contracts\IUserPreferences;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\Avatar\AvatarFactory;
use OCA\Mail\Service\Avatar\Cache;
use OCA\Mail\Service\Avatar\CompositeAvatarSource;
use OCA\Mail\Service\Avatar\Downloader;
use OCA\Mail\Service\Avatar\IAvatarSource;
use OCA\Mail\Service\AvatarService;
use OCA\Mail\Tests\TestCase;
use OCP\IURLGenerator;
use PHPUnit_Framework_MockObject_MockObject;

class AvatarServiceTest extends TestCase {

	const BLACK_DOT_BASE64 = 'R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=';

	/** @var IAvatarSource|PHPUnit_Framework_MockObject_MockObject */
	private $source;

	/** @var Downloader|PHPUnit_Framework_MockObject_MockObject */
	private $downloader;

	/** @var Cache|PHPUnit_Framework_MockObject_MockObject */
	private $cache;

	/** @var IURLGenerator|PHPUnit_Framework_MockObject_MockObject */
	private $urlGenerator;

	/** @var AvatarFactory|PHPUnit_Framework_MockObject_MockObject */
	private $avatarFactory;

	/** @var IUserPreferences */
	private $preferences;

	/** @var AvatarService */
	private $avatarService;

	protected function setUp() {
		parent::setUp();

		$this->source = $this->createMock(CompositeAvatarSource::class);
		$this->downloader = $this->createMock(Downloader::class);
		$this->cache = $this->createMock(Cache::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->avatarFactory = $this->createMock(AvatarFactory::class);
		$this->preferences = $this->createMock(IUserPreferences::class);

		$this->avatarService = new AvatarService($this->source, $this->downloader,
			$this->cache, $this->urlGenerator, $this->avatarFactory, $this->preferences);
	}

	public function testGetCachedAvatarUrl() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->cache->expects($this->once())
			->method('get')
			->with($email, $uid)
			->willReturn('https://doe.com/favicon.ico');

		$url = $this->avatarService->getAvatar($email, $uid);

		$this->assertEquals('https://doe.com/favicon.ico', $url);
	}

	public function testGetAvatarNoAvatarFound() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->preferences->expects($this->once())
			->method('getPreference')
			->with('external-avatars', 'true')
			->willReturn('true');
		$this->cache->expects($this->once())
			->method('get')
			->with($email)
			->willReturn(null);
		$this->source->expects($this->once())
			->method('fetch')
			->with($email, $this->avatarFactory, true)
			->willReturn(null);
		$this->cache->expects($this->never())
			->method('add');

		$url = $this->avatarService->getAvatar($email, $uid);

		$this->assertNull($url);
	}

	public function testGetAvatarMimeNotAllowed() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->preferences->expects($this->once())
			->method('getPreference')
			->with('external-avatars', 'true')
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
		$this->cache->expects($this->never())
			->method('add');

		$url = $this->avatarService->getAvatar($email, $uid);

		$this->assertNull($url);
	}

	public function testGetAvatarOnlyInternalAllowed() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$avatar = new Avatar('https://doe.com/favicon.ico', 'image/png');
		$this->preferences->expects($this->once())
			->method('getPreference')
			->with('external-avatars', 'true')
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
			->with('external-avatars', 'true')
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
