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

namespace OCA\Mail\Tests\Service\Avatar;

use OCA\Mail\Service\Avatar\Cache;
use OCA\Mail\Tests\TestCase;
use OCP\ICache;
use OCP\ICacheFactory;
use PHPUnit_Framework_MockObject_MockObject;

class CacheTest extends TestCase {

	const BLACK_DOT_BASE64 = 'R0lGODlhAQABAIAAAAUEBAAAACwAAAAAAQABAAACAkQBADs=';

	/** @var ICacheFactory|PHPUnit_Framework_MockObject_MockObject */
	private $cacheFactory;

	/** @var ICache|PHPUnit_Framework_MockObject_MockObject */
	private $cacheImpl;

	/** @var Cache */
	private $cache;

	protected function setUp() {
		parent::setUp();

		$this->cacheFactory = $this->createMock(ICacheFactory::class);
		$this->cacheImpl = $this->createMock(ICache::class);
		$this->cacheFactory->expects($this->once())
			->method('create')
			->with('mail.avatars')
			->willReturn($this->cacheImpl);
		$this->cache = new Cache($this->cacheFactory);
	}

	public function testGetNonCachedUrl() {
		$email = 'john@doe.com';
		$uid = 'jane';
		$this->cacheImpl->expects($this->once())
			->method('get')
			->with(base64_encode(json_encode([$email, $uid])))
			->willReturn(null);

		$url = $this->cache->getUrl($email, $uid);

		$this->assertNull($url);
	}

	public function testGetCachedUrl() {
		$email = 'john@doe.com';
		$uid = 'jane';
		$this->cacheImpl->expects($this->once())
			->method('get')
			->with(base64_encode(json_encode([$email, $uid])))
			->willReturn('https://doe.com/favicon.ico');

		$url = $this->cache->getUrl($email, $uid);

		$this->assertEquals('https://doe.com/favicon.ico', $url);
	}

	public function testSetUrl() {
		$email = 'john@doe.com';
		$uid = 'jane';
		$this->cacheImpl->expects($this->once())
			->method('set')
			->with(base64_encode(json_encode([$email, $uid])), 'https://doe.com/favicon.ico', 7 * 24 * 60 * 60);

		$this->cache->addUrl($email, $uid, 'https://doe.com/favicon.ico');
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
