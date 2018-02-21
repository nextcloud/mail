<?php

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

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\GroupsIntegration;
use OCA\Mail\Service\Group\NextcloudGroupService;
use OCA\Mail\Exception\ServiceException;

class GroupsIntegrationTest extends TestCase {

	private $groupService1;
	private $groupsIntegration;

	protected function setUp() {
		parent::setUp();

		$this->groupService1 = $this->createMock(NextcloudGroupService::class);
		$this->groupService1->expects($this->any())
			->method('getNamespace')
			->willReturn('Namespace1');
		$this->groupsIntegration = new GroupsIntegration($this->groupService1);
	}

	public function testGetMatchingGroups() {
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
			->will($this->returnValue($searchResult1));

		$expected = [
			[
				'id' => 'namespace1:testgroup',
				'label' => 'first test group (Namespace1)',
				'value' => 'namespace1:testgroup',
				'photo' => null,
			]
		];
		$actual = $this->groupsIntegration->getMatchingGroups($term);

		$this->assertEquals($expected, $actual);
	}

	public function testExpandNone() {
		$recipients = "john@doe.com,alice@smith.net";
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
		$this->groupService1->expects($this->never())
			->method('getUsers')
			->willReturn($members);

		$expected = $recipients;

		$actual = $this->groupsIntegration->expand($recipients);

		$this->assertEquals($expected, $actual);

	}

	public function testExpand() {
		$recipients = "john@doe.com,namespace1:testgroup,alice@smith.net";
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

		$expected = "john@doe.com,bob@smith.net,mary@smith.net,alice@smith.net";

		$actual = $this->groupsIntegration->expand($recipients);

		$this->assertEquals($expected, $actual);

	}

	public function testExpand2() {
		$recipients = "john@doe.com,namespace1:testgroup,alice@smith.net";
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

		$expected = "john@doe.com,bob@smith.net,mary@smith.net,alice@smith.net";

		$actual = $this->groupsIntegration->expand($recipients);

		$this->assertEquals($expected, $actual);

	}

	public function testExpandEmpty() {
		$this->expectException(ServiceException::class);
		$recipients = "john@doe.com,namespace1:testgroup,alice@smith.net";
		$members = [
		];
		$this->groupService1->expects($this->once())
			->method('getUsers')
			->willReturn($members);
		$this->groupsIntegration->expand($recipients);
	}

	public function testExpandWrong() {
		$recipients = "john@doe.com,nons:testgroup,alice@smith.net";
		$expected = "john@doe.com,nons:testgroup,alice@smith.net";

		$actual = $this->groupsIntegration->expand($recipients);

		$this->assertEquals($expected, $actual);

	}

	public function testExpandWrong2() {
		$this->expectException(ServiceException::class);
		$recipients = "john@doe.com,namespace1:nogroup,alice@smith.net";

		$this->groupsIntegration->expand($recipients);

	}

}
