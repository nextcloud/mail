<?php declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_Fetch_Results;
use Horde_Imap_Client_Socket;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\MailboxLockedException;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\Search\Provider;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Search\MailSearch;
use OCP\ILogger;
use PHPUnit\Framework\MockObject\MockObject;

class MailSearchTest extends TestCase {

	/** @var FilterStringParser|MockObject */
	private $filterStringParser;

	/** @var MockObject|MailboxMapper */
	private $mailboxMapper;

	/** @var MockObject|ILogger */
	private $logger;

	/** @var MailSearch */
	private $search;

	/** @var Provider|MockObject */
	private $imapSearchProvider;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	protected function setUp(): void {
		parent::setUp();

		$this->filterStringParser = $this->createMock(FilterStringParser::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->imapSearchProvider = $this->createMock(Provider::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->logger = $this->createMock(ILogger::class);

		$this->search = new MailSearch(
			$this->filterStringParser,
			$this->mailboxMapper,
			$this->imapSearchProvider,
			$this->messageMapper,
			$this->logger
		);
	}

	public function testFindMessagesNotCached() {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setSyncNewToken('abc');
		$mailbox->setSyncChangedToken('def');
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->willReturn($mailbox);
		$this->expectException(MailboxNotCachedException::class);

		$messages = $this->search->findMessages(
			$account,
			'INBOX',
			null,
			null
		);
	}

	public function testFindMessagesLocked() {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setSyncNewLock(123);
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->willReturn($mailbox);
		$this->expectException(MailboxLockedException::class);

		$messages = $this->search->findMessages(
			$account,
			'INBOX',
			null,
			null
		);
	}

	public function testNoFindMessages() {
		$account = $this->createMock(Account::class);
		$mailbox = new Mailbox();
		$mailbox->setSyncNewToken('abc');
		$mailbox->setSyncChangedToken('def');
		$mailbox->setSyncVanishedToken('ghi');
		$this->mailboxMapper->expects($this->once())
			->method('find')
			->willReturn($mailbox);

		$messages = $this->search->findMessages(
			$account,
			'INBOX',
			null,
			null
		);

		$this->assertEmpty($messages);
	}

}
