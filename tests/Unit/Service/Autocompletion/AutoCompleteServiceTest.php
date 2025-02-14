<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Tests\Unit\Service\Autocompletion;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Db\CollectedAddress;
use OCA\Mail\Service\AutoCompletion\AddressCollector;
use OCA\Mail\Service\AutoCompletion\AutoCompleteService;
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
			['id' => 12, 'label' => '"john doe" <john@doe.cz>', 'email' => 'john@doe.cz', 'source' => 'contacts'],
			['id' => 13, 'label' => '"joe doe" <joe@doe.se>', 'email' => 'joe@doe.se', 'source' => 'contacts'],
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
			['id' => 20, 'label' => 'Journalists', 'email' => 'Journalists', 'source' => 'groups']
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
			['id' => 12, 'label' => '"john doe" <john@doe.cz>', 'email' => 'john@doe.cz', 'source' => 'contacts'],
			['id' => 13, 'label' => '"joe doe" <joe@doe.se>', 'email' => 'joe@doe.se', 'source' => 'contacts'],
			['id' => 1234, 'label' => 'John Doe', 'email' => 'john@doe.com', 'source' => 'collector'],
			['id' => 20, 'label' => 'Journalists', 'email' => 'Journalists', 'source' => 'groups'],
		];
		$this->assertEquals($expected, $response);
	}
}
