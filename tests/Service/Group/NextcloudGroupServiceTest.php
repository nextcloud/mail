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

namespace OCA\Mail\Tests\Service\Group;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\Group\NextcloudGroupService;

class NextcloudGroupServiceTest extends TestCase {

	private $groupsManager;
	private $groupService;

	protected function setUp() {
		parent::setUp();

		$this->groupsManager = $this->createMock('OCP\IGroupManager');
		$this->groupService = new NextcloudGroupService($this->groupsManager);
	}

	private function createTestGroup($id, $name, $users = []) {
		$mockGroup = $this->createMock('OCP\IGroup');
		$mockGroup->expects($this->any())
			->method('getGID')
			->will($this->returnValue($id));
		$mockGroup->expects($this->any())
			->method('getDisplayName')
			->will($this->returnValue($name));
		$mockGroup->expects($this->any())
			->method('getUsers')
			->willReturn($users);
		return $mockGroup;
	}

	private function createTestUser($id, $name, $email) {
		$mockUser = $this->createMock('OCP\IUser');
		$mockUser->expects($this->any())
			->method('getUID')
			->will($this->returnValue($id));
		$mockUser->expects($this->any())
			->method('getDisplayName')
			->will($this->returnValue($name));
		$mockUser->expects($this->any())
			->method('getEMailAddress')
			->willReturn($email);
		return $mockUser;
	}


	public function testSearch() {
		$term = 'te'; // searching for: John Doe
		$searchResult = [
			$this->createTestGroup('testgroup', 'first test group'),
			$this->createTestGroup('testgroup2', 'second test group'),
		];

		$this->groupsManager->expects($this->once())
			->method('search')
			->with($term)
			->will($this->returnValue($searchResult));

		$expected = [
			[
				'id' => 'testgroup',
				'name' => 'first test group',
			],
			[
				'id' => 'testgroup2',
				'name' => 'second test group',
			]
		];
		$actual = $this->groupService->search($term);

		$this->assertEquals($expected, $actual);
	}

	public function testGetUsers() {
		$users = [ 
			$this->createTestUser('bob', 'Bobby', 'bob@smith.net'),
			$this->createTestUser('alice', 'Alice', 'alice@smith.net')
		];
		$group =
			$this->createTestGroup('testgroup', 'first test group', $users);

		$this->groupsManager->expects($this->once())
			->method('groupExists')
			->willReturn(true);

		$this->groupsManager->expects($this->once())
			->method('get')
			->with('testgroup')
			->willReturn($group);

		$actual = $this->groupService->getUsers('testgroup');

		$expected = [
			[
				'id' => 'bob',
				'name' => 'Bobby',
				'email' => 'bob@smith.net'
			],
			[
				'id' => 'alice',
				'name' => 'Alice',
				'email' => 'alice@smith.net'
			]
		];

		$this->assertEquals($expected, $actual);

	}

	public function testGetUsersWrong() {
		$this->expectException(\Exception::class);

		$this->groupsManager->expects($this->once())
			->method('groupExists')
			->willReturn(false);

		$this->groupService->getUsers('nogroup');
	}

}

