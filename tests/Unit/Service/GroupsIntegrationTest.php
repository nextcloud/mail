<?php

declare(strict_types=1);

/**
 * @author Matthias Rella <mrella@pisys.eu>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Service\Group\ContactsGroupService;
use OCA\Mail\Service\GroupsIntegration;
use OCA\Mail\Service\Group\NextcloudGroupService;
use OCA\Mail\Exception\ServiceException;
use PHPUnit\Framework\MockObject\MockObject;

class GroupsIntegrationTest extends TestCase {
	/** @var NextcloudGroupService|MockObject */
	private $groupService1;

	/** @var GroupsIntegration */
	private $groupsIntegration;

	protected function setUp(): void {
		parent::setUp();

		$this->groupService1 = $this->createMock(ContactsGroupService::class);
		$this->groupService2 = $this->createMock(NextcloudGroupService::class);
		$this->groupService1
			->method('getNamespace')
			->willReturn('Namespace1');
		$this->groupService2
			->method('getNamespace')
			->willReturn('Namespace2');
		$this->groupsIntegration = new GroupsIntegration(
			$this->groupService1,
			$this->groupService2
		);
	}

	public function testGetMatchingGroups(): void {
		$term = 'te'; // searching for: John Doe
		$searchResult1 = [
			[
				'id' => 'testgroup',
				'name' => "first test group"
			]
		];
		$this->groupService1->expects($this->once())
			->method('search')
			->with($term)
			->willReturn($searchResult1);

		$actual = $this->groupsIntegration->getMatchingGroups($term);

		$this->assertEquals(
			[
				[
					'id' => 'namespace1:testgroup',
					'label' => 'first test group (Namespace1)',
					'email' => 'namespace1:testgroup',
					'photo' => null,
				]
			],
			$actual
		);
	}

	public function testExpandNone(): void {
		$recipients = [
			Recipient::fromParams(['label' => 'John Doe', 'email' => 'john@doe.com']),
			Recipient::fromParams(['label' => 'alice@smith.net', 'email' => 'alice@smith.net']),
		];
		$this->groupService1->expects($this->never())
			->method('getUsers');

		$expanded = $this->groupsIntegration->expand($recipients);

		$this->assertEquals($recipients, $expanded);
	}

	public function testExpand(): void {
		$recipients = [
			Recipient::fromParams(['label' => 'John Doe', 'email' => 'john@doe.com']),
			Recipient::fromParams(['label' => 'testgroup (namespace1)', 'email' => 'namespace1:testgroup']),
			Recipient::fromParams(['label' => 'alice@smith.net', 'email' => 'alice@smith.net']),
		];
		$members = [
			[
				'id' => 'bob',
				'name' => "Bobby",
				'email' => "bob@smith.net"
			],
			[
				'id' => 'mary',
				'name' => 'Mary',
				'email' => 'mary@smith.net'
			]
		];
		$this->groupService1->expects($this->once())
			->method('getUsers')
			->willReturn($members);

		$expanded = $this->groupsIntegration->expand($recipients);

		$this->assertEquals(
			[
				Recipient::fromParams(['label' => 'John Doe', 'email' => 'john@doe.com']),
				Recipient::fromParams(['label' => 'Bobby', 'email' => 'bob@smith.net']),
				Recipient::fromParams(['label' => 'Mary', 'email' => 'mary@smith.net']),
				Recipient::fromParams(['label' => 'alice@smith.net', 'email' => 'alice@smith.net']),
			],
			$expanded
		);
	}

	public function testExpandUmlauts(): void {
		$recipients = [
			Recipient::fromParams(['label' => 'John Doe', 'email' => 'john@doe.com']),
			Recipient::fromParams(['label' => 'ümlaut (namespace1)', 'email' => 'namespace1:ümlaut']),
			Recipient::fromParams(['label' => 'alice@smith.net', 'email' => 'alice@smith.net']),
		];
		$members = [
			[
				'id' => 'bob',
				'name' => "Bobby",
				'email' => "bob@smith.net"
			],
			[
				'id' => 'mary',
				'name' => 'Mary',
				'email' => 'mary@smith.net'
			]
		];
		$this->groupService1->expects($this->once())
			->method('getUsers')
			->willReturn($members);

		$expanded = $this->groupsIntegration->expand($recipients);

		$this->assertEquals(
			[
				Recipient::fromParams(['label' => 'John Doe', 'email' => 'john@doe.com']),
				Recipient::fromParams(['label' => 'Bobby', 'email' => 'bob@smith.net']),
				Recipient::fromParams(['label' => 'Mary', 'email' => 'mary@smith.net']),
				Recipient::fromParams(['label' => 'alice@smith.net', 'email' => 'alice@smith.net']),
			],
			$expanded
		);
	}

	public function testExpandSpace(): void {
		$recipients = [
			Recipient::fromParams(['label' => 'John Doe', 'email' => 'john@doe.com']),
			Recipient::fromParams(['label' => 'test group (namespace1)', 'email' => 'namespace1:test group']),
		];
		$members = [
			[
				'id' => 'bob',
				'name' => "Bobby",
				'email' => "bob@smith.net"
			],
			[
				'id' => 'mary',
				'name' => 'Mary',
				'email' => 'mary@smith.net'
			]
		];
		$this->groupService1->expects($this->once())
			->method('getUsers')
			->with('test group')
			->willReturn($members);

		$expanded = $this->groupsIntegration->expand($recipients);

		$this->assertEquals(
			[
				Recipient::fromParams(['label' => 'John Doe', 'email' => 'john@doe.com']),
				Recipient::fromParams(['label' => 'Bobby', 'email' => 'bob@smith.net']),
				Recipient::fromParams(['label' => 'Mary', 'email' => 'mary@smith.net']),
			],
			$expanded
		);
	}

	public function testExpandEmpty(): void {
		$recipients = [
			Recipient::fromParams(['label' => 'testgroup (namespace1)', 'email' => 'namespace1:testgroup']),
		];
		$members = [];
		$this->groupService1->expects($this->once())
			->method('getUsers')
			->willReturn($members);
		$this->expectException(ServiceException::class);

		$this->groupsIntegration->expand($recipients);
	}

	public function testExpandWrong(): void {
		$recipients = [
			Recipient::fromParams(['label' => 'John Doe', 'email' => 'john@doe.com']),
			Recipient::fromParams(['label' => 'testgroup (nons)', 'email' => 'nons:testgroup']),
		];

		$actual = $this->groupsIntegration->expand($recipients);

		$this->assertEquals(
			[
				Recipient::fromParams(['label' => 'John Doe', 'email' => 'john@doe.com']),
				Recipient::fromParams(['label' => 'testgroup (nons)', 'email' => 'nons:testgroup']),
			],
			$actual
		);
	}

	public function testExpandWrong2(): void {
		$recipients = [
			Recipient::fromParams(['label' => 'John Doe', 'email' => 'john@doe.com']),
			Recipient::fromParams(['label' => 'nogroup (namespace1)', 'email' => 'namespace1:nogroup']),
		];
		$this->expectException(ServiceException::class);

		$this->groupsIntegration->expand($recipients);
	}
}
