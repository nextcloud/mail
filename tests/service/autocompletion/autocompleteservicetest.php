<?php

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2016
 */

namespace OCA\Mail\Tests\Service\Autocompletion;

use PHPUnit_Framework_TestCase;
use OCA\Mail\Service\AutoCompletion\AutoCompleteService;

class AutoCompleteServiceTest extends PHPUnit_Framework_TestCase {

	private $contactsIntegration;
	private $addressCollector;
	private $service;

	protected function setUp() {
		parent::setUp();

		$this->contactsIntegration = $this->getMockBuilder('\OCA\Mail\Service\ContactsIntegration')
			->disableOriginalConstructor()
			->getMock();
		$this->addressCollector = $this->getMockBuilder('\OCA\Mail\Service\AutoCompletion\AddressCollector')
			->disableOriginalConstructor()
			->getMock();

		$this->service = new AutoCompleteService($this->contactsIntegration,
			$this->addressCollector);
	}

	public function testFindMatches() {
		$term = 'jo';

		$contactsResult = [
			['id' => 12, 'label' => 'john doe', 'value' => 'john doe'],
			['id' => 13, 'label' => 'joe doe', 'value' => 'joe doe'],
		];
		$john = new \OCA\Mail\Db\CollectedAddress();
		$john->setId(1234);
		$john->setEmail('john@doe.com');
		$john->setUserId('testuser');
		$collectedResult = [
			$john,
		];

		$this->contactsIntegration->expects($this->once())
			->method('getMatchingRecipient')
			->with($term)
			->will($this->returnValue($contactsResult));
		$this->addressCollector->expects($this->once())
			->method('searchAddress')
			->with($term)
			->will($this->returnValue($collectedResult));

		$response = $this->service->findMatches($term);

		$expected = [
			['id' => 12, 'label' => 'john doe', 'value' => 'john doe'],
			['id' => 13, 'label' => 'joe doe', 'value' => 'joe doe'],
			['id' => 1234, 'label' => 'john@doe.com', 'value' => 'john@doe.com'],
		];
		$this->assertEquals($expected, $response);
	}

}
