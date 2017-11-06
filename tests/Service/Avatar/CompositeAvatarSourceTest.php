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

use OCA\Mail\Service\Avatar\AddressbookSource;
use OCA\Mail\Service\Avatar\CompositeAvatarSource;
use OCA\Mail\Service\Avatar\FaviconSource;
use OCA\Mail\Service\Avatar\GravatarSource;
use OCA\Mail\Tests\TestCase;
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

	protected function setUp() {
		parent::setUp();

		$this->addressbookSource = $this->createMock(AddressbookSource::class);
		$this->gravatarSource = $this->createMock(GravatarSource::class);
		$this->faviconSource = $this->createMock(FaviconSource::class);

		$this->source = new CompositeAvatarSource($this->addressbookSource, $this->faviconSource, $this->gravatarSource);
	}

	public function testFetchNoneFound() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->addressbookSource->expects($this->once())
			->method('fetch')
			->with($email)
			->willReturn(null);
		$this->gravatarSource->expects($this->once())
			->method('fetch')
			->with($email)
			->willReturn(null);
		$this->faviconSource->expects($this->once())
			->method('fetch')
			->with($email)
			->willReturn(null);

		$avatar = $this->source->fetch($email, $uid);

		$this->assertNull($avatar);
	}

	public function testFetchFromGravatar() {
		$email = 'jane@doe.com';
		$uid = 'john';
		$this->addressbookSource->expects($this->once())
			->method('fetch')
			->with($email)
			->willReturn(null);
		$this->gravatarSource->expects($this->once())
			->method('fetch')
			->with($email)
			->willReturn('https://gravatar.com');

		$avatar = $this->source->fetch($email, $uid);

		$this->assertEquals('https://gravatar.com', $avatar);
	}

}
