<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2015-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\ContactsIntegration;
use OCP\Contacts\IManager;
use OCP\IConfig;
use OCP\IGroupManager;
use OCP\IUser;
use OCP\IUserManager;

class ContactsIntegrationTest extends TestCase {
	/** @var IManager */
	private $contactsManager;

	/** @var IGroupManager */
	private $groupManager;

	/** @var IUserManager */
	private $userManager;

	/** @var IConfig */
	private $config;

	/** @var ContactsIntegration */
	private $contactsIntegration;

	protected function setUp(): void {
		parent::setUp();

		$this->contactsManager = $this->createMock(IManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->groupManager = $this->createMock(IGroupManager::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->contactsIntegration = new ContactsIntegration($this->contactsManager,
			$this->groupManager,
			$this->userManager,
			$this->config);
	}

	public function testDisabledContactsManager() {
		$this->contactsManager->expects($this->once())
			->method('isEnabled')
			->will($this->returnValue(false));
		$this->contactsManager->expects($this->never())
			->method('search');

		$expected = [];
		$actual = $this->contactsIntegration->getMatchingRecipient('', 'abc');

		$this->assertEquals($expected, $actual);
	}

	public function testGetMatchingRecipient() {
		$term = 'jo'; // searching for: John Doe
		$searchResult = [
			[
				// Simple match
				'UID' => 'jf',
				'FN' => 'Jonathan Frakes',
				'EMAIL' => 'jonathan@frakes.com',
			],
			[
				// Array of addresses
				'UID' => 'jd',
				'FN' => 'John Doe',
				'EMAIL' => [
					'john@doe.info',
					'doe@john.info',
				],
			],
			[
				// Johann Strauss II didn't have a email address ;-)
				'UID' => 'js',
				'FN' => 'Johann Strauss II',
			]
		];

		$this->common($term, $searchResult, false, false, false);

		$expected = [
			[
				'id' => 'jf',
				'label' => 'Jonathan Frakes',
				'email' => 'jonathan@frakes.com',
				'photo' => null,
				'source' => 'contacts'
			],
			[
				'id' => 'jd',
				'label' => 'John Doe',
				'email' => 'john@doe.info',
				'photo' => null,
				'source' => 'contacts'
			],
			[
				'id' => 'jd',
				'label' => 'John Doe',
				'email' => 'doe@john.info',
				'photo' => null,
				'source' => 'contacts'
			],
		];

		$actual = $this->contactsIntegration->getMatchingRecipient('', $term);

		$this->assertEquals($expected, $actual);
	}

	public function testGetMatchingRecipientRestrictedToGroup() {
		$term = 'jo'; // searching for: John Doe
		$searchResult = [
			[
				// Simple match
				'UID' => 'jf',
				'FN' => 'Jonathan Frakes',
				'EMAIL' => 'jonathan@frakes.com',
				'isLocalSystemBook' => true,
			],
			[
				// Array of addresses
				'UID' => 'jd',
				'FN' => 'John Doe',
				'EMAIL' => [
					'john@doe.info',
					'doe@john.info',
				],
				'isLocalSystemBook' => true,
			],
			[
				'UID' => 'js',
				'FN' => 'Johann Strauss II',
				'EMAIL' => 'johann@strauss.com',
			],
		];

		$this->common($term, $searchResult, true, true, false, false, false);
		$user = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with('auser')
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->will($this->returnValue(['agroup']));
		$this->groupManager->expects(self::exactly(2))
			->method('isInGroup')
			->withConsecutive(['jf', 'agroup'], ['jd', 'agroup'])
			->willReturnOnConsecutiveCalls(false, true);

		$expected = [
			[
				'id' => 'jd',
				'label' => 'John Doe',
				'email' => 'john@doe.info',
				'photo' => null,
				'source' => 'contacts'
			],
			[
				'id' => 'jd',
				'label' => 'John Doe',
				'email' => 'doe@john.info',
				'photo' => null,
				'source' => 'contacts',
			],
			[
				'id' => 'js',
				'label' => 'Johann Strauss II',
				'email' => 'johann@strauss.com',
				'photo' => null,
				'source' => 'contacts',
			],
		];

		$actual = $this->contactsIntegration->getMatchingRecipient('auser', $term);

		$this->assertEquals($expected, $actual);
	}

	public function testGetMatchingRecipientRestrictedToGroupFullMatchUserId() {
		$term = 'jf'; // searching for: Jonathan Frakes
		$searchResult = [
			[
				// Simple match
				'UID' => 'jf',
				'FN' => 'Jonathan Frakes',
				'EMAIL' => 'jonathan@frakes.com',
				'isLocalSystemBook' => true,
			],
		];

		$this->common($term, $searchResult, true, true, true);
		$user = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with('auser')
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->will($this->returnValue(['agroup']));
		$this->groupManager->expects(self::exactly(1))
			->method('isInGroup')
			->withConsecutive(['jf', 'agroup'])
			->willReturnOnConsecutiveCalls(false);

		$expected = [
			[
				'id' => 'jf',
				'label' => 'Jonathan Frakes',
				'email' => 'jonathan@frakes.com',
				'photo' => null,
				'source' => 'contacts',
			],
		];

		$actual = $this->contactsIntegration->getMatchingRecipient('auser', $term);

		$this->assertEquals($expected, $actual);
	}

	public function testGetMatchingRecipientRestrictedToGroupFullMatchFullName() {
		$term = 'Jonathan Frakes'; // searching for: Jonathan Frakes
		$searchResult = [
			[
				// Simple match
				'UID' => 'jf',
				'FN' => 'Jonathan Frakes',
				'EMAIL' => 'jonathan@frakes.com',
				'isLocalSystemBook' => true,
			],
		];

		$this->common($term, $searchResult, true, true, true);
		$user = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with('auser')
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->will($this->returnValue(['agroup']));
		$this->groupManager->expects(self::exactly(1))
			->method('isInGroup')
			->withConsecutive(['jf', 'agroup'])
			->willReturnOnConsecutiveCalls(false);

		$expected = [
			[
				'id' => 'jf',
				'label' => 'Jonathan Frakes',
				'email' => 'jonathan@frakes.com',
				'photo' => null,
				'source' => 'contacts',
			],
		];

		$actual = $this->contactsIntegration->getMatchingRecipient('auser', $term);

		$this->assertEquals($expected, $actual);
	}

	public function testGetMatchingRecipientRestrictedToGroupFullMatchEmail() {
		$term = 'jonathan@frakes.com'; // searching for: Jonathan Frakes
		$searchResult = [
			[
				// Simple match
				'UID' => 'jf',
				'FN' => 'Jonathan Frakes',
				'EMAIL' => 'jonathan@frakes.com',
				'isLocalSystemBook' => true,
			],
		];

		$this->common($term, $searchResult, true, true, true);
		$user = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with('auser')
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->will($this->returnValue(['agroup']));
		$this->groupManager->expects(self::exactly(1))
			->method('isInGroup')
			->withConsecutive(['jf', 'agroup'])
			->willReturnOnConsecutiveCalls(false);

		$expected = [
			[
				'id' => 'jf',
				'label' => 'Jonathan Frakes',
				'email' => 'jonathan@frakes.com',
				'photo' => null,
				'source' => 'contacts',
			],
		];

		$actual = $this->contactsIntegration->getMatchingRecipient('auser', $term);

		$this->assertEquals($expected, $actual);
	}

	public function testGetMatchingRecipientRestrictedToGroupFullMatchFalse() {
		$term = 'jf'; // searching for: Jonathan Frakes

		$searchResult = [
			[
				// Simple match
				'UID' => 'jf',
				'FN' => 'Jonathan Frakes',
				'EMAIL' => 'jonathan@frakes.com',
				'isLocalSystemBook' => true,
			],
		];

		$this->common($term, $searchResult, true, true, false, false, false);

		$user = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with('auser')
			->will($this->returnValue($user));
		$this->groupManager->expects($this->once())
			->method('getUserGroupIds')
			->will($this->returnValue(['agroup']));
		$this->groupManager->expects(self::exactly(1))
			->method('isInGroup')
			->withConsecutive(['jf', 'agroup'])
			->willReturnOnConsecutiveCalls(false);

		$expected = [];

		$actual = $this->contactsIntegration->getMatchingRecipient('auser', $term);
		$this->assertEquals($expected, $actual);
	}

	public function common($term, $searchResult, $allowSystemUsers, $allowSystemUsersInGroupOnly, $shareeEnumerationFullMatch, $shareeEnumerationFullMatchUserId = true, $shareeEnumerationFullMatchEmail = true) {
		$this->config->expects(self::atLeast(3))
			->method('getAppValue')
			->withConsecutive(
				['core', 'shareapi_allow_share_dialog_user_enumeration', 'no'],
				['core', 'shareapi_restrict_user_enumeration_to_group', 'no'],
				['core', 'shareapi_restrict_user_enumeration_full_match', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_full_match_userid', 'yes'],
				['core', 'shareapi_restrict_user_enumeration_full_match_email', 'yes'],
			)
			->willReturnOnConsecutiveCalls(
				$allowSystemUsers ? 'yes' : ' no',
				$allowSystemUsersInGroupOnly ? 'yes' : ' no',
				$shareeEnumerationFullMatch ? 'yes' : ' no',
				$shareeEnumerationFullMatchUserId ? 'yes' : 'no',
				$shareeEnumerationFullMatchEmail ? 'yes' : ' no');
		$this->contactsManager->expects($this->once())
			->method('isEnabled')
			->will($this->returnValue(true));
		$this->contactsManager->expects($this->once())
			->method('search')
			->with(
				$term,
				['UID', 'FN', 'EMAIL'],
				[
					'enumeration' => $allowSystemUsers,
					'fullmatch' => $shareeEnumerationFullMatch,
					'limit' => 20,
				])
			->will($this->returnValue($searchResult));
	}

	public function getPhotoDataProvider() {
		return [
			[
				// Match with photo
				'john@doe.com',
				[
					'id' => 2,
					'FN' => 'John Doe',
					'PHOTO' => 'abcdefg'
				],
				'abcdefg',
			],
			[
				// Match without photo
				'doe@john.com',
				[
					'id' => 2,
					'FN' => 'John Doe',
				],
				null,
			],
			[
				// No match
				'abc@def.gh',
				[],
				null,
			],
		];
	}

	/**
	 * @dataProvider getPhotoDataProvider
	 */
	public function testGetPhoto($email, $searchResult, $expected) {
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($email, ['EMAIL'])
			->will($this->returnValue([$searchResult]));

		$actual = $this->contactsIntegration->getPhoto($email);

		$this->assertEquals($actual, $actual);
	}
}
