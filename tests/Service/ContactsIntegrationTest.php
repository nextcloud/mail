<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
use OCA\Mail\Service\ContactsIntegration;
use OCP\Contacts\IManager;
use OCP\IConfig;

class ContactsIntegrationTest extends TestCase {

	/** @var IManager */
	private $contactsManager;

	/** @var IConfig */
	private $config;

	/** @var ContactsIntegration */
	private $contactsIntegration;

	protected function setUp() {
		parent::setUp();

		$this->contactsManager = $this->createMock(IManager::class);
		$this->config = $this->createMock(IConfig::class);
		$this->contactsIntegration = new ContactsIntegration($this->contactsManager,
			$this->config);
	}

	public function testDisabledContactsManager() {
		$this->contactsManager->expects($this->once())
			->method('isEnabled')
			->will($this->returnValue(false));
		$this->contactsManager->expects($this->never())
			->method('search');

		$expected = [];
		$actual = $this->contactsIntegration->getMatchingRecipient("abc");

		$this->assertEquals($expected, $actual);
	}

	public function testGetMatchingRecipient() {
		$term = 'jo'; // searching for: John Doe
		$searchResult = [
			[
				// Simple match
				'UID' => 1,
				'FN' => 'Jonathan Frakes',
				'EMAIL' => 'jonathan@frakes.com',
			],
			[
				// Array of addresses
				'UID' => 2,
				'FN' => 'John Doe',
				'EMAIL' => [
					'john@doe.info',
					'doe@john.info',
				],
			],
			[
				// Johann Strauss II didn't have a email address ;-)
				'UID' => 3,
				'FN' => 'Johann Strauss II',
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
			->with($term, ['FN', 'EMAIL'])
			->will($this->returnValue($searchResult));
		$expected = [
			[
				'id' => 1,
				'label' => 'Jonathan Frakes (jonathan@frakes.com)',
				'email' => 'jonathan@frakes.com',
				'photo' => null,
			],
			[
				'id' => 2,
				'label' => 'John Doe (john@doe.info)',
				'email' => 'john@doe.info',
				'photo' => null,
			],
			[
				'id' => 2,
				'label' => 'John Doe (doe@john.info)',
				'email' => 'doe@john.info',
				'photo' => null,
			],
		];

		$actual = $this->contactsIntegration->getMatchingRecipient($term);

		$this->assertEquals($expected, $actual);
	}

	public function testGetMatchingRecipientNoSystemUsers() {
		$term = 'jo'; // searching for: John Doe
		$searchResult = [
			[
				// Simple match
				'UID' => 1,
				'FN' => 'Jonathan Frakes',
				'EMAIL' => 'jonathan@frakes.com',
			],
			[
				// Array of addresses
				'UID' => 2,
				'FN' => 'John Doe',
				'EMAIL' => [
					'john@doe.info',
					'doe@john.info',
				],
				'isLocalSystemBook' => true,
			],
			[
				// Johann Strauss II didn't have a email address ;-)
				'UID' => 3,
				'FN' => 'Johann Strauss II',
			]
		];
		$this->config->expects($this->once())
			->method('getAppValue')
			->with('core', 'shareapi_allow_share_dialog_user_enumeration', 'no')
			->willReturn('no');
		$this->contactsManager->expects($this->once())
			->method('isEnabled')
			->will($this->returnValue(true));
		$this->contactsManager->expects($this->once())
			->method('search')
			->with($term, ['FN', 'EMAIL'])
			->will($this->returnValue($searchResult));
		$expected = [
			[
				'id' => 1,
				'label' => 'Jonathan Frakes (jonathan@frakes.com)',
				'email' => 'jonathan@frakes.com',
				'photo' => null,
			],
		];

		$actual = $this->contactsIntegration->getMatchingRecipient($term);

		$this->assertEquals($expected, $actual);
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
