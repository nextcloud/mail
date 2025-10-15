<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service\Group;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\Group\NextcloudGroupService;
use OCP\IConfig;
use OCP\IGroup;
use OCP\IGroupManager;
use OCP\IUser;
use PHPUnit\Framework\MockObject\MockObject;

class NextcloudGroupServiceTest extends TestCase {
	/**
	 * @var IGroupManager|MockObject
	 */
	private $groupsManager;
	/**
	 * @var IGroupManager|MockObject
	 */
	private $config;
	/**
	 * @var NextcloudGroupService
	 */
	private $groupService;

	protected function setUp(): void {
		parent::setUp();

		$this->groupsManager = $this->createMock(IGroupManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->groupService = new NextcloudGroupService($this->groupsManager, $this->config);
	}

	private function createTestGroup($id, $name, $users = []) {
		$mockGroup = $this->createMock(IGroup::class);
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
		$mockUser = $this->createMock(IUser::class);
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

	public function dataForTestSearch(): array {
		return [
			['yes', 'no', [
				[
					'id' => 'testgroup',
					'name' => 'first test group',
				],
				[
					'id' => 'testgroup2',
					'name' => 'second test group',
				]
			]],
			['no', 'yes', []],
			['no', 'no', []],
			['yes', 'yes', []],
		];
	}


	/**
	 * @dataProvider dataForTestSearch
	 * @param string $allowGroupSharing
	 * @param array $expected
	 */
	public function testSearch(string $allowGroupSharing, string $restrictSharingToGroups, array $expected): void {
		$term = 'te'; // searching for: John Doe
		$searchResult = [
			$this->createTestGroup('testgroup', 'first test group'),
			$this->createTestGroup('testgroup2', 'second test group'),
		];

		$this->groupsManager->expects(($allowGroupSharing === 'yes' && $restrictSharingToGroups === 'no') ? self::once() : self::never())
			->method('search')
			->with($term)
			->willReturn($searchResult);

		$this->config->expects(self::exactly(2))
			->method('getAppValue')
			->willReturnMap([
				['core', 'shareapi_allow_group_sharing', 'yes', $allowGroupSharing],
				['core', 'shareapi_only_share_with_group_members', 'no', $restrictSharingToGroups],
			]);


		$actual = $this->groupService->search($term);

		self::assertEquals($expected, $actual);
	}

	public function testGetUsers() {
		$users = [
			$this->createTestUser('bob', 'Bobby', 'bob@smith.net'),
			$this->createTestUser('alice', 'Alice', 'alice@smith.net')
		];
		$group = $this->createTestGroup('testgroup', 'first test group', $users);

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
		$this->expectException(ServiceException::class);

		$this->groupsManager->expects($this->once())
			->method('groupExists')
			->willReturn(false);

		$this->groupService->getUsers('nogroup');
	}
}
