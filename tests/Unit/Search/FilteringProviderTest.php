<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Search;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\AddressList;
use OCA\Mail\Db\Message;
use OCA\Mail\Search\FilteringProvider;
use OCP\IUser;
use OCP\Search\IFilter;
use OCP\Search\IFilteringProvider;
use OCP\Search\ISearchQuery;
use function interface_exists;

/**
 * @covers \OCA\Mail\Search\FilteringProvider
 */
class FilteringProviderTest extends TestCase {
	private ServiceMockObject $serviceMock;
	private FilteringProvider $provider;

	protected function setUp(): void {
		parent::setUp();

		if (!interface_exists(IFilteringProvider::class)) {
			$this->markTestSkipped('Base class missing');
		}

		$this->serviceMock = $this->createServiceMock(FilteringProvider::class);
		$this->provider = $this->serviceMock->getService();
	}

	public function testSearchForTerm(): void {
		$term = 'spam';
		$user = $this->createMock(IUser::class);
		$query = $this->createMock(ISearchQuery::class);
		$termFilter = $this->createMock(IFilter::class);
		$termFilter->method('get')->willReturn($term);
		$query->method('getFilter')->willReturnCallback(function ($filter) use ($termFilter) {
			return match ($filter) {
				'term' => $termFilter,
				default => null,
			};
		});
		$message1 = new Message();
		$message1->setSubject('This is not spam');
		$message1->setFrom(AddressList::parse('Sender <sender@domain.tld>'));
		$this->serviceMock->getParameter('mailSearch')
			->expects(self::once())
			->method('findMessagesGlobally')
			->with(
				$user,
				'subject:spam'
			)
			->willReturn([
				$message1,
			]);

		$result = $this->provider->search(
			$user,
			$query,
		);

		self::assertNotEmpty($result->jsonSerialize()['entries'] ?? []);
	}

	public function testSearchForUserNoEmail(): void {
		$user = $this->createMock(IUser::class);
		$otherUser = $this->createMock(IUser::class);
		$query = $this->createMock(ISearchQuery::class);
		$termFilter = $this->createMock(IFilter::class);
		$termFilter->method('get')->willReturn($otherUser);
		$query->method('getFilter')->willReturnCallback(function ($filter) use ($termFilter) {
			return match ($filter) {
				'person' => $termFilter,
				default => null,
			};
		});
		$this->serviceMock->getParameter('mailSearch')
			->expects(self::never())
			->method('findMessagesGlobally');

		$result = $this->provider->search(
			$user,
			$query,
		);

		self::assertEmpty($result->jsonSerialize()['entries'] ?? []);
	}

	public function testSearchForUser(): void {
		$user = $this->createMock(IUser::class);
		$otherUser = $this->createMock(IUser::class);
		$otherUser->method('getEMailAddress')->willReturn('other@domain.tld');
		$query = $this->createMock(ISearchQuery::class);
		$userFilter = $this->createMock(IFilter::class);
		$userFilter->method('get')->willReturn($otherUser);
		$query->method('getFilter')->willReturnCallback(function ($filter) use ($userFilter) {
			return match ($filter) {
				'person' => $userFilter,
				default => null,
			};
		});
		$message1 = new Message();
		$message1->setSubject('This is not spam');
		$message1->setFrom(AddressList::parse('Other <other@domain.tld>'));
		$this->serviceMock->getParameter('mailSearch')
			->expects(self::once())
			->method('findMessagesGlobally')
			->with(
				$user,
				'from:other@domain.tld to:other@domain.tld cc:other@domain.tld'
			)
			->willReturn([
				$message1,
			]);

		$result = $this->provider->search(
			$user,
			$query,
		);

		self::assertNotEmpty($result->jsonSerialize()['entries'] ?? []);
	}

}
