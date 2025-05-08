<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Search;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OC\DateTimeFormatter;
use OC\Search\Filter\DateTimeFilter;
use OC\Search\Filter\StringFilter;
use OC\Search\Filter\UserFilter;
use OC\Search\FilterCollection;
use OC\Search\SearchQuery;
use OCA\Mail\AddressList;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Db\Message;
use OCA\Mail\Search\FilteringProvider;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Search\SearchQuery as MailSearchQuery;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\IUserManager;
use OCP\Search\IFilter;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * @covers \OCA\Mail\Search\FilteringProvider
 */
class FilteringProviderTest extends TestCase {
	private IMailSearch&MockObject $mailSearch;
	private IL10N&MockObject $l10n;
	private IDateTimeFormatter $dateTimeFormatter;
	private IURLGenerator&MockObject $urlGenerator;
	private FilterStringParser $filterStringParser;
	private IUserManager&MockObject $userManager;
	private FilteringProvider $provider;

	protected function setUp(): void {
		parent::setUp();

		$this->mailSearch = $this->createMock(IMailSearch::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->dateTimeFormatter = new DateTimeFormatter(new \DateTimeZone('Europe/Berlin'), $this->l10n);
		$this->urlGenerator = $this->createMock(IURLGenerator::class);
		$this->filterStringParser = new FilterStringParser();
		$this->userManager = $this->createMock(IUserManager::class);

		$this->provider = new FilteringProvider(
			$this->mailSearch,
			$this->l10n,
			$this->dateTimeFormatter,
			$this->urlGenerator,
			$this->filterStringParser
		);
	}

	public function testSearchForTerm(): void {
		$term = 'spam';
		$user = $this->createMock(IUser::class);
		$filters = [
			IFilter::BUILTIN_TERM => new StringFilter($term),
		];
		$filterCollection = new FilterCollection(... $filters);
		$searchQuery = new SearchQuery($filterCollection);
		$message1 = new Message();
		$message1->setSubject('This is not spam');
		$message1->setFrom(AddressList::parse('Sender <sender@domain.tld>'));
		$this->mailSearch->expects($this->once())
			->method('findMessagesGlobally')
			->with($user, $this->callback(function (MailSearchQuery $query) {
				self::assertCount(1, $query->getSubjects());
				self::assertEquals('spam', $query->getSubjects()[0]);
				return true;
			}))
			->willReturn([$message1]);

		$result = $this->provider->search(
			$user,
			$searchQuery
		);
		$entries = $result->jsonSerialize()['entries'] ?? [];

		self::assertNotEmpty($entries);
	}

	public function testSearchForUserNoEmail(): void {
		$user = $this->createMock(IUser::class);
		$otherUser = $this->createMock(IUser::class);
		$this->userManager->expects($this->once())
			->method('get')
			->with('bob')
			->willReturn($otherUser);
		$filters = [
			IFilter::BUILTIN_PERSON => new UserFilter('bob', $this->userManager),
		];
		$filterCollection = new FilterCollection(... $filters);
		$searchQuery = new SearchQuery($filterCollection);
		$this->mailSearch->expects($this->never())
			->method('findMessagesGlobally');

		$result = $this->provider->search(
			$user,
			$searchQuery,
		);
		$entries = $result->jsonSerialize()['entries'] ?? [];

		self::assertEmpty($entries);
	}

	public function testSearchForUser(): void {
		$user = $this->createMock(IUser::class);
		$otherUser = $this->createMock(IUser::class);
		$otherUser->method('getEMailAddress')
			->willReturn('other@domain.tld');
		$this->userManager->expects($this->once())
			->method('get')
			->with('bob')
			->willReturn($otherUser);
		$filters = [
			IFilter::BUILTIN_PERSON => new UserFilter('bob', $this->userManager),
		];
		$filterCollection = new FilterCollection(... $filters);
		$searchQuery = new SearchQuery($filterCollection);
		$message1 = new Message();
		$message1->setSubject('This is not spam');
		$message1->setFrom(AddressList::parse('Other <other@domain.tld>'));
		$this->mailSearch->expects($this->once())
			->method('findMessagesGlobally')
			->with($user, $this->callback(function (MailSearchQuery $query) {
				self::assertCount(1, $query->getFrom());
				self::assertCount(1, $query->getTo());
				self::assertCount(1, $query->getCc());
				return true;
			}))
			->willReturn([$message1]);

		$result = $this->provider->search(
			$user,
			$searchQuery,
		);
		$entries = $result->jsonSerialize()['entries'] ?? [];

		self::assertNotEmpty($entries);
	}

	public function testSearchSinceAndUntil(): void {
		$user = $this->createMock(IUser::class);
		$otherUser = $this->createMock(IUser::class);
		$otherUser->method('getEMailAddress')
			->willReturn('other@domain.tld');
		$this->userManager->expects($this->once())
			->method('get')
			->with('bob')
			->willReturn($otherUser);
		$filters = [
			IFilter::BUILTIN_PERSON => new UserFilter('bob', $this->userManager),
			IFilter::BUILTIN_SINCE => new DateTimeFilter('2025-04-25T15:30:00'),
			IFilter::BUILTIN_UNTIL => new DateTimeFilter('2025-05-25T15:30:00'),
		];
		$filterCollection = new FilterCollection(... $filters);
		$searchQuery = new SearchQuery($filterCollection);
		$message1 = new Message();
		$message1->setSubject('This is not spam');
		$message1->setFrom(AddressList::parse('Other <other@domain.tld>'));
		$this->mailSearch->expects($this->once())
			->method('findMessagesGlobally')
			->with($user, $this->callback(function (MailSearchQuery $query) {
				self::assertCount(1, $query->getFrom());
				self::assertCount(1, $query->getTo());
				self::assertCount(1, $query->getCc());
				self::assertNotNull($query->getStart());
				self::assertNotNull($query->getEnd());
				return true;
			}))
			->willReturn([$message1]);

		$result = $this->provider->search(
			$user,
			$searchQuery,
		);
		$entries = $result->jsonSerialize()['entries'] ?? [];

		self::assertNotEmpty($entries);
	}

	public function testGetSupportedFilters(): void {
		$filters = $this->provider->getSupportedFilters();
		self::assertCount(4, $filters);
	}

	public function testGetAlternateIds(): void {
		self::assertCount(0, $this->provider->getAlternateIds());
	}

	public function testGetCustomFilters(): void {
		self::assertCount(0, $this->provider->getCustomFilters());
	}
}
