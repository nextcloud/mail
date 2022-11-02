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

namespace OCA\Mail\Tests\Unit\Service\Autocompletion;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\CollectedAddress;
use OCA\Mail\Service\AutoCompletion\AutoCompleteService;
use OCA\Mail\Service\AutoCompletion\AddressCollector;
use OCA\Mail\Service\ContactsIntegration;
use OCA\Mail\Service\GroupsIntegration;

class AutoCompleteServiceTest extends TestCase {
	private $contactsIntegration;
	private $groupsIntegration;
	private $addressCollector;
	private $service;

	protected function setUp(): void {
		parent::setUp();

		$this->contactsIntegration = $this->createMock(ContactsIntegration::class);
		$this->groupsIntegration = $this->createMock(GroupsIntegration::class);
		$this->addressCollector = $this->createMock(AddressCollector::class);

		$this->service = new AutoCompleteService($this->contactsIntegration,
			$this->groupsIntegration,
			$this->addressCollector);
	}

	public function testFindMatches() {
		$term = 'jo';

		$contactsResult = [
			['id' => 12, 'label' => '"john doe" <john@doe.cz>', 'email' => 'john@doe.cz'],
			['id' => 13, 'label' => '"joe doe" <joe@doe.se>', 'email' => 'joe@doe.se'],
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
			['id' => 20, 'label' => 'Journalists', 'email' => 'Journalists']
		];

		$this->contactsIntegration->expects($this->once())
			->method('getMatchingRecipient')
			->with('testuser', $term)
			->willReturn($contactsResult);
		$this->groupsIntegration->expects($this->once())
			->method('getMatchingGroups')
			->with($term)
			->willReturn($groupsResult);
		$this->addressCollector->expects($this->once())
			->method('searchAddress')
			->with(
				'testuser',
				$term
			)
			->willReturn($collectedResult);

		$response = $this->service->findMatches('testuser', $term);

		$expected = [
			['id' => 12, 'label' => '"john doe" <john@doe.cz>', 'email' => 'john@doe.cz'],
			['id' => 13, 'label' => '"joe doe" <joe@doe.se>', 'email' => 'joe@doe.se'],
			['id' => 1234, 'label' => 'John Doe', 'email' => 'john@doe.com'],
			['id' => 20, 'label' => 'Journalists', 'email' => 'Journalists'],
		];
		$this->assertEquals($expected, $response);
	}
}
