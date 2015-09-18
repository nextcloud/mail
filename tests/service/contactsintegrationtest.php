<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */
use Test\TestCase;
use OCA\Mail\Service\ContactsIntegration;

class ContactsIntegrationTest extends TestCase {

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
				'id' => 1,
				'FN' => 'Jonathan Frakes',
				'EMAIL' => 'jonathan@frakes.com',
			],
			[
				// Array of addresses
				'id' => 2,
				'FN' => 'John Doe',
				'EMAIL' => [
					'john@doe.info',
					'doe@john.info',
				],
			],
			[
				// Johann Struass II didn't have a email address ;-)
				'id' => 3,
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
				'label' => '"Jonathan Frakes" <jonathan@frakes.com>',
				'value' => '"Jonathan Frakes" <jonathan@frakes.com>',
			],
			[
				'id' => 2,
				'label' => '"John Doe" <john@doe.info>',
				'value' => '"John Doe" <john@doe.info>',
			],
			[
				'id' => 2,
				'label' => '"John Doe" <doe@john.info>',
				'value' => '"John Doe" <doe@john.info>',
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
