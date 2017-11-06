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

	/** @var AvatarService */
	private $avatarService;

	protected function setUp() {
		parent::setUp();

		$this->source = $this->createMock(CompositeAvatarSource::class);
		$this->downloader = $this->createMock(Downloader::class);
		$this->cache = $this->createMock(Cache::class);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);

		$this->avatarService = new AvatarService($this->source, $this->downloader, $this->cache, $this->urlGenerator);
	}

	public function testGetCachedAvatarUrl() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->cache->expects($this->once())
			->method('getUrl')
			->with($email, $uid)
			->willReturn('https://doe.com/favicon.ico');

		$url = $this->avatarService->getAvatarUrl($email, $uid);

		$this->assertEquals('https://doe.com/favicon.ico', $url);
	}

	public function testGetAvatarUrlNoAvatarFound() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->cache->expects($this->once())
			->method('getUrl')
			->with($email, $uid)
			->willReturn(null);
		$this->source->expects($this->once())
			->method('fetch')
			->with($email, $uid)
			->willReturn(null);
		$this->cache->expects($this->never())
			->method('addUrl');

		$url = $this->avatarService->getAvatarUrl($email, $uid);

		$this->assertNull($url);
	}

	public function testGetAvatarUrl() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->cache->expects($this->once())
			->method('getUrl')
			->with($email, $uid)
			->willReturn(null);
		$this->source->expects($this->once())
			->method('fetch')
			->with($email, $uid)
			->willReturn('https://doe.com/favicon.ico');
		$this->cache->expects($this->once())
			->method('addUrl')
			->with($email, $uid, 'https://doe.com/favicon.ico');

		$url = $this->avatarService->getAvatarUrl($email, $uid);

		$this->assertEquals('https://doe.com/favicon.ico', $url);
	}

	public function testGetCachedAvatarImage() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->cache->expects($this->once())
			->method('getUrl')
			->with($email, $uid)
			->willReturn('https://doe.com/favicon.ico');
		$this->cache->expects($this->once())
			->method('getImage')
			->with('https://doe.com/favicon.ico', $uid)
			->willReturn(self::BLACK_DOT_BASE64);

		$image = $this->avatarService->getAvatarImage($email, $uid);

		$this->assertEquals(base64_decode(self::BLACK_DOT_BASE64), $image);
	}

	public function testGetAvatarImageNoUrlCached() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->cache->expects($this->once())
			->method('getUrl')
			->with($email, $uid)
			->willReturn(null);

		$image = $this->avatarService->getAvatarImage($email, $uid);

		$this->assertNull($image);
	}

	public function testGetAvatarImageDownloadImage() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->cache->expects($this->once())
			->method('getUrl')
			->with($email, $uid)
			->willReturn('https://doe.com/favicon.ico');
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

		$image = $this->avatarService->getAvatarImage($email, $uid);

		$this->assertEquals(base64_decode(self::BLACK_DOT_BASE64), $image);
	}

	public function testGetAvatarImageDownloadImageFails() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->cache->expects($this->once())
			->method('getUrl')
			->with($email, $uid)
			->willReturn('https://doe.com/favicon.ico');
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
