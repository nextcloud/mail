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
final class FilteringProviderTest extends TestCase {
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
		$user = $this->createStub(IUser::class);
		$filters = [
			IFilter::BUILTIN_TERM => new StringFilter($term),
		];
		$filterCollection = new FilterCollection(... $filters);
		$searchQuery = new SearchQuery($filterCollection);
		$message1 = new Message();
		$message1->setSubject('This is not spam');
		$message1->setFrom(AddressList::parse('Sender <sender@domain.tld>'));
		$messageId = '<org/repo/issues/123/456@example.invalid>';
		$message1->setMessageId($messageId);
		$expectedEncoded = 'PG9yZy9yZXBvL2lzc3Vlcy8xMjMvNDU2QGV4YW1wbGUuaW52YWxpZD4';
		$this->urlGenerator->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('mail.deep_link.open', ['messageId' => $expectedEncoded])
			->willReturn('http://localhost/apps/mail/open/' . $expectedEncoded);
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
		self::assertSame('http://localhost/apps/mail/open/' . $expectedEncoded, $entries[0]->jsonSerialize()['resourceUrl']);
	}

	public function testSearchForMessageWithoutMessageIdFallsBackToThreadRoute(): void {
		$term = 'spam';
		$user = $this->createStub(IUser::class);
		$filters = [
			IFilter::BUILTIN_TERM => new StringFilter($term),
		];
		$filterCollection = new FilterCollection(... $filters);
		$searchQuery = new SearchQuery($filterCollection);
		$message1 = new Message();
		$message1->setId(42);
		$message1->setMailboxId(7);
		$message1->setSubject('This is not spam');
		$message1->setFrom(AddressList::parse('Sender <sender@domain.tld>'));
		// No messageId set -> should fall back to thread route

		$this->urlGenerator->expects($this->once())
			->method('linkToRouteAbsolute')
			->with('mail.page.thread', ['mailboxId' => 7, 'id' => 42])
			->willReturn('http://localhost/apps/mail/box/7/thread/42');
		$this->mailSearch->expects($this->once())
			->method('findMessagesGlobally')
			->willReturn([$message1]);

		$result = $this->provider->search(
			$user,
			$searchQuery
		);
		$entries = $result->jsonSerialize()['entries'] ?? [];

		self::assertNotEmpty($entries);
		self::assertSame('http://localhost/apps/mail/box/7/thread/42', $entries[0]->jsonSerialize()['resourceUrl']);
	}

	public static function multiWordTermProvider(): \Iterator {
		yield 'several words' => ['QA invite'];
		yield 'colon in the term' => ['Re: invoice'];
		yield 'plus sign' => ['C++ meetup'];
		yield 'surrounding whitespace' => ['  QA invite ', 'QA invite'];
	}

	/**
	 * @dataProvider multiWordTermProvider
	 */
	public function testSearchForFullTerm(string $term, ?string $expected = null): void {
		$user = $this->createStub(IUser::class);
		$searchQuery = new SearchQuery(new FilterCollection(...[
			IFilter::BUILTIN_TERM => new StringFilter($term),
		]));
		$this->mailSearch->expects($this->once())
			->method('findMessagesGlobally')
			->with($user, $this->callback(function (MailSearchQuery $query) use ($term, $expected) {
				self::assertEquals([$expected ?? $term], $query->getSubjects());
				return true;
			}))
			->willReturn([]);

		$this->provider->search($user, $searchQuery);
	}

	public function testSearchForWhitespaceOnlyTerm(): void {
		$user = $this->createStub(IUser::class);
		$searchQuery = new SearchQuery(new FilterCollection(...[
			IFilter::BUILTIN_TERM => new StringFilter('   '),
		]));
		$this->mailSearch->expects($this->never())
			->method('findMessagesGlobally');

		$result = $this->provider->search($user, $searchQuery);

		self::assertEmpty($result->jsonSerialize()['entries']);
	}

	public function testSearchForUserWithPlusAddress(): void {
		$user = $this->createStub(IUser::class);
		$otherUser = $this->createMock(IUser::class);
		$otherUser->method('getEMailAddress')
			->willReturn('other+news@domain.tld');
		$this->userManager->method('get')
			->with('bob')
			->willReturn($otherUser);
		$searchQuery = new SearchQuery(new FilterCollection(...[
			IFilter::BUILTIN_PERSON => new UserFilter('bob', $this->userManager),
		]));
		$this->mailSearch->expects($this->once())
			->method('findMessagesGlobally')
			->with($user, $this->callback(function (MailSearchQuery $query) {
				self::assertEquals(['other+news@domain.tld'], $query->getFrom());
				self::assertEquals(['other+news@domain.tld'], $query->getTo());
				self::assertEquals(['other+news@domain.tld'], $query->getCc());
				return true;
			}))
			->willReturn([]);

		$this->provider->search($user, $searchQuery);
	}

	public function testSearchForUserNoEmail(): void {
		$user = $this->createStub(IUser::class);
		$otherUser = $this->createStub(IUser::class);
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
		$user = $this->createStub(IUser::class);
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
		$user = $this->createStub(IUser::class);
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
