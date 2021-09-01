<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Tests\Unit\Service\Search;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\MailboxLockedException;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\IMAP\Search\Provider;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Search\Flag;
use OCA\Mail\Service\Search\MailSearch;
use OCA\Mail\Service\Search\Result;
use OCA\Mail\Service\Search\SearchQuery;
use OCP\AppFramework\Utility\ITimeFactory;
use PHPUnit\Framework\MockObject\MockObject;

class MailSearchTest extends TestCase {

	/** @var FilterStringParser|MockObject */
	private $filterStringParser;

	/** @var MockObject|MailboxMapper */
	private $mailboxMapper;

	/** @var MailSearch */
	private $search;

	/** @var Provider|MockObject */
	private $imapSearchProvider;

	/** @var PreviewEnhancer|MockObject */
	private $previewEnhancer;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var ITimeFactory|MockObject */
	private $timeFactory;

	protected function setUp(): void {
		parent::setUp();

		$this->filterStringParser = $this->createMock(FilterStringParser::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->imapSearchProvider = $this->createMock(Provider::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->previewEnhancer = $this->createMock(PreviewEnhancer::class);
		$this->timeFactory = $this->createMock(ITimeFactory::class);

		$this->search = new MailSearch(
			$this->filterStringParser,
			$this->mailboxMapper,
			$this->imapSearchProvider,
			$this->messageMapper,
			$this->previewEnhancer,
			$this->timeFactory
		);
	}

	public function testFindMessagesNotCached(): void {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setSyncNewToken('abc');
		$mailbox->setSyncChangedToken('def');
		$this->expectException(MailboxNotCachedException::class);

		$this->search->findMessages(
			$account,
			$mailbox,
			null,
			null,
			null,
			IMailSearch::ORDER_NEWEST_FIRST,
		);
	}

	public function testFindMessagesLocked(): void {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setSyncNewLock(123);
		$this->expectException(MailboxLockedException::class);

		$this->search->findMessages(
			$account,
			$mailbox,
			null,
			null,
			null,
			IMailSearch::ORDER_NEWEST_FIRST,
		);
	}

	public function testFindNoMessages(): void {
		$account = $this->createMock(Account::class);
		$account->expects(self::once())
			->method('getUserId')
			->willReturn('admin');
		$mailbox = new Mailbox();
		$mailbox->setSyncNewToken('abc');
		$mailbox->setSyncChangedToken('def');
		$mailbox->setSyncVanishedToken('ghi');

		$result = $this->search->findMessages(
			$account,
			$mailbox,
			null,
			null,
			null,
			IMailSearch::ORDER_NEWEST_FIRST,
		);

		self::assertEquals(new Result([], null), $result);
	}

	public function testFindFlagsLocally(): void {
		$account = $this->createMock(Account::class);
		$account->expects(self::once())
			->method('getUserId')
			->willReturn('admin');
		$mailbox = new Mailbox();
		$mailbox->setSyncNewToken('abc');
		$mailbox->setSyncChangedToken('def');
		$mailbox->setSyncVanishedToken('ghi');
		$query = new SearchQuery();
		$query->addFlag(Flag::is(Flag::SEEN));
		$this->filterStringParser->expects(self::once())
			->method('parse')
			->with('my search')
			->willReturn($query);
		$messages = [
			$this->createMock(Message::class),
			$this->createMock(Message::class),
		];
		$this->messageMapper->expects(self::once())
			->method('findByIds')
			->willReturn($messages);
		$this->imapSearchProvider->expects(self::never())
			->method('findMatches');
		$this->previewEnhancer->expects(self::once())
			->method('process')
			->willReturnArgument(2);

		$result = $this->search->findMessages(
			$account,
			$mailbox,
			'my search',
			null,
			null,
			IMailSearch::ORDER_NEWEST_FIRST,
		);

		self::assertEquals(new Result($messages, null), $result);
	}

	public function testFindText(): void {
		$account = $this->createMock(Account::class);
		$account->expects(self::once())
			->method('getUserId')
			->willReturn('admin');
		$mailbox = new Mailbox();
		$mailbox->setSyncNewToken('abc');
		$mailbox->setSyncChangedToken('def');
		$mailbox->setSyncVanishedToken('ghi');
		$query = new SearchQuery();
		$query->addTextToken('my');
		$query->addTextToken('search');
		$this->filterStringParser->expects(self::once())
			->method('parse')
			->with('my search')
			->willReturn($query);
		$this->imapSearchProvider->expects(self::once())
			->method('findMatches')
			->with($account, $mailbox, $query)
			->willReturn([2, 3]);
		$messages = [
			$this->createMock(Message::class),
			$this->createMock(Message::class),
		];
		$this->messageMapper->expects(self::once())
			->method('findByIds')
			->willReturn($messages);
		$this->previewEnhancer->expects(self::once())
			->method('process')
			->willReturnArgument(2);

		$result = $this->search->findMessages(
			$account,
			$mailbox,
			'my search',
			null,
			null,
			IMailSearch::ORDER_NEWEST_FIRST,
		);

		self::assertEquals(new Result($messages, null), $result);
	}
}
