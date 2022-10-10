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

namespace OCA\Mail\Tests\Unit\Service\Avatar;

use OCA\Mail\Service\Avatar\AddressbookSource;
use OCA\Mail\Service\Avatar\Avatar;
use OCA\Mail\Service\Avatar\AvatarFactory;
use OCA\Mail\Service\ContactsIntegration;
use ChristophWurst\Nextcloud\Testing\TestCase;
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
