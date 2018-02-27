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

class GroupsIntegrationTest extends TestCase {

	private $groupsManager;
	private $groupService;

	protected function setUp() {
		parent::setUp();

		$this->groupsManager = $this->getMockBuilder('OCP\IGroupManager')
			->disableOriginalConstructor()
			->getMock();
		$this->groupService = new NextcloudGroupService($this->groupsManager);
	}

	private function createTestGroup($id, $name) {
		$mockGroup = $this->createMock('OCP\IGroup');
		$mockGroup->expects($this->any())
			->method('getGID')
			->will($this->returnValue($id));
		$mockGroup->expects($this->any())
			->method('getDisplayName')
			->will($this->returnValue($name));
		return $mockGroup;
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
				'id' => 'nextcloud:testgroup',
				'name' => 'first test group (Nextcloud)',
			],
			[
				'id' => 'nextcloud:testgroup2',
				'name' => 'second test group (Nextcloud)',
			]
		];
		$actual = $this->groupService->search($term);

		$this->assertEquals($expected, $actual);
	}

}

