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

class GroupsIntegrationTest extends TestCase {

	private $groupService1;
	private $groupService2;
	private $groupsIntegration;

	protected function setUp() {
		parent::setUp();

		$this->groupService1 = $this->getMockBuilder(NextcloudGroupService::class)
			->disableOriginalConstructor()
			->getMock();
		$this->groupService2 = $this->getMockBuilder(NextcloudGroupService::class)
			->disableOriginalConstructor()
			->getMock();
		$this->groupsIntegration = new GroupsIntegration($this->groupService1, $this->groupService2);
	}

	public function testGetMatchingGroups() {
		$term = 'te'; // searching for: John Doe
		$searchResult1 = [
			[
				'id' => 'testgroup',
				'name' => "first test group"
			]
		];
		$searchResult2 = [
			[
				'id' => 'testgroup2',
				'name' => "second test group"
			]
		];

		$this->groupService1->expects($this->once())
			->method('search')
			->with($term)
			->will($this->returnValue($searchResult1));
		$this->groupService2->expects($this->once())
			->method('search')
			->with($term)
			->will($this->returnValue($searchResult2));

		$expected = [
			[
				'id' => 'testgroup',
				'label' => 'first test group',
				'value' => 'testgroup',
				'photo' => null,
			],
			[
				'id' => 'testgroup2',
				'label' => 'second test group',
				'value' => 'testgroup2',
				'photo' => null,
			]
		];
		$actual = $this->groupsIntegration->getMatchingGroups($term);

		$this->assertEquals($expected, $actual);
	}

}
