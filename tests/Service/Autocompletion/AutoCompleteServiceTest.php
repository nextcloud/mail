<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Tests\Service\Autocompletion;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\CollectedAddress;
use OCA\Mail\Service\AutoCompletion\AutoCompleteService;

class AutoCompleteServiceTest extends TestCase {

	private $contactsIntegration;
	private $groupsIntegration;
	private $addressCollector;
	private $service;

	protected function setUp() {
		parent::setUp();

		$this->contactsIntegration = $this->getMockBuilder('\OCA\Mail\Service\ContactsIntegration')
			->disableOriginalConstructor()
			->getMock();
		$this->groupsIntegration = $this->getMockBuilder('\OCA\Mail\Service\GroupsIntegration')
			->disableOriginalConstructor()
			->getMock();
		$this->addressCollector = $this->getMockBuilder('\OCA\Mail\Service\AutoCompletion\AddressCollector')
			->disableOriginalConstructor()
			->getMock();

		$this->service = new AutoCompleteService($this->contactsIntegration,
      $this->groupsIntegration,
			$this->addressCollector);
	}

	public function testFindMatches() {
		$term = 'jo';

		$contactsResult = [
			['id' => 12, 'label' => '"john doe" <john@doe.cz>', 'value' => '"john doe" <john@doe.cz>'],
			['id' => 13, 'label' => '"joe doe" <joe@doe.se>', 'value' => '"joe doe" <joe@doe.se>'],
		];
		$john = new CollectedAddress();
		$john->setId(1234);
		$john->setEmail('john@doe.com');
		$john->setDisplayName('John Doe');
		$john->setUserId('testuser');
		$collectedResult = [
			$john,
		];

    $groupsResult = [
      ['id' => 20, 'label' => 'Journalists', 'value' => 'Journalists']
    ];

		$this->contactsIntegration->expects($this->once())
			->method('getMatchingRecipient')
			->with($term)
			->will($this->returnValue($contactsResult));
		$this->groupsIntegration->expects($this->once())
			->method('getMatchingGroups')
			->with($term)
			->will($this->returnValue($groupsResult));
		$this->addressCollector->expects($this->once())
			->method('searchAddress')
			->with($term)
			->will($this->returnValue($collectedResult));

		$response = $this->service->findMatches($term);

		$expected = [
			['id' => 12, 'label' => '"john doe" <john@doe.cz>', 'value' => '"john doe" <john@doe.cz>'],
			['id' => 13, 'label' => '"joe doe" <joe@doe.se>', 'value' => '"joe doe" <joe@doe.se>'],
			['id' => 1234, 'label' => 'John Doe', 'value' => '"John Doe" <john@doe.com>'],
			['id' => 20, 'label' => 'Journalists', 'value' => 'Journalists'],
		];
		$this->assertEquals($expected, $response);
	}

}
