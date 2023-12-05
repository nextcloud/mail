<?php

declare(strict_types=1);

/*
 * @copyright 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2023 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
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
				"from:other@domain.tld to:other@domain.tld cc:other@domain.tld"
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
