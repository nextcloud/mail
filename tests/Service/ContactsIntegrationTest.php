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

use OCA\Mail\Service\ContactsIntegration;
use PHPUnit_Framework_TestCase;

class ContactsIntegrationTest extends PHPUnit_Framework_TestCase {

	private $contactsManager;
	private $contactsIntegration;

	protected function setUp() {
		parent::setUp();

		$this->contactsManager = $this->getMockBuilder('OCP\Contacts\IManager')
			->disableOriginalConstructor()
			->getMock();
		$this->contactsIntegration = new ContactsIntegration($this->contactsManager);
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
				'value' => '"Jonathan Frakes" <jonathan@frakes.com>',
				'photo' => null,
			],
			[
				'id' => 2,
				'label' => 'John Doe (john@doe.info)',
				'value' => '"John Doe" <john@doe.info>',
				'photo' => null,
			],
			[
				'id' => 2,
				'label' => 'John Doe (doe@john.info)',
				'value' => '"John Doe" <doe@john.info>',
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
