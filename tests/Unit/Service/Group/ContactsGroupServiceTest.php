<?php

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service\Group;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\Group\ContactsGroupService;
use OCP\Contacts\IManager;
use OCP\IConfig;

class ContactsGroupServiceTest extends TestCase {
	/** @var IManager */
	private $contactsManager;

	/** @var IConfig */
	private $config;

	private $groupService;

	protected function setUp(): void {
		parent::setUp();

		$this->contactsManager = $this->createMock(IManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->groupService = new ContactsGroupService($this->contactsManager,
			$this->config);
	}

	public function testDisabledContactsManager() {
		$this->contactsManager->expects($this->once())
			->method('isEnabled')
			->will($this->returnValue(false));
		$this->contactsManager->expects($this->never())
			->method('search');

		$expected = [];
		$actual = $this->groupService->search('abc');

		$this->assertEquals($expected, $actual);
	}

	public function testSearchGroups() {
		$term = 'wor'; // searching for: group Work
		$searchResult = [
			[
				// multiple groups
				'UID' => 1,
				'FN' => 'Jonathan Frakes',
				'EMAIL' => 'jonathan@frakes.com',
				'CATEGORIES' => 'work1,work2,family'
			],
			[
				// 1 group
				'UID' => 2,
				'FN' => 'John Doe',
				'EMAIL' => [
					'john@doe.info',
					'doe@john.info',
				],
				'CATEGORIES' => 'work3'
			],
			[
				// 1 group no e-mail
				'UID' => 3,
				'FN' => 'Johann Strauss II',
				'CATEGORIES' => 'suppliers'
			]
		];
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('core', 'shareapi_allow_share_dialog_user_enumeration', 'no')
			->willReturn('yes');
		$this->contactsManager->expects($this->once())
			->method('isEnabled')
			->will($this->returnValue(true));
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($term, ['CATEGORIES'])
			->will($this->returnValue($searchResult));
		$expected = [
			[
				'id' => 'work1',
				'name' => 'work1',
			],
			[
				'id' => 'work2',
				'name' => 'work2',
			],
			[
				'id' => 'family',
				'name' => 'family',
			],
			[
				'id' => 'work3',
				'name' => 'work3',
			],
		];

		$actual = $this->groupService->search($term);

		$this->assertEquals($expected, $actual);
	}

	public function testGetUsersForGroup() {
		$groupId = 'work'; // searching for: group Work
		$searchResult = [
			[
				// multiple groups
				'UID' => 1,
				'FN' => 'Jonathan Frakes',
				'EMAIL' => 'jonathan@frakes.com',
				'CATEGORIES' => 'work,family'
			],
			[
				// take all email
				'UID' => 2,
				'FN' => 'John Doe',
				'EMAIL' => [
					'john@doe.info',
					'doe@john.info',
				],
				'CATEGORIES' => 'work'
			],
			[
				// substring of group should not match
				'UID' => 3,
				'FN' => 'Jonathan Frakes',
				'EMAIL' => 'jonathan2@frakes.com',
				'CATEGORIES' => 'work2'
			]
		];
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('core', 'shareapi_allow_share_dialog_user_enumeration', 'no')
			->willReturn('yes');
		$this->contactsManager->expects($this->once())
			->method('isEnabled')
			->will($this->returnValue(true));
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($groupId, ['CATEGORIES'])
			->will($this->returnValue($searchResult));
		$expected = [
			[
				'email' => 'jonathan@frakes.com',
			],
			[
				'email' => 'john@doe.info',
			],
			[
				'email' => 'doe@john.info',
			],
		];

		$actual = $this->groupService->getUsers($groupId);

		$this->assertEquals($expected, $actual);
	}
}
