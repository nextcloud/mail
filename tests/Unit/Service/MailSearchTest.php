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
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\Search\SearchFilterStringParser;
use OCA\Mail\IMAP\Search\SearchStrategyFactory;
use OCA\Mail\Service\MailSearch;
use OCP\ILogger;
use PHPUnit\Framework\MockObject\MockObject;

class MailSearchTest extends TestCase {

	/** @var MockObject|IMAPClientFactory */
	private $imapClientFactory;

	/** @var MockObject|SearchStrategyFactory */
	private $searchStrategyFactory;

	/** @var MockObject|SearchFilterStringParser */
	private $searchStringParser;

	/** @var MockObject|MailboxMapper */
	private $mailboxMapper;

	/** @var MockObject|ILogger */
	private $logger;

	/** @var MailSearch */
	private $search;

	protected function setUp(): void {
		parent::setUp();

		$this->imapClientFactory = $this->createMock(IMAPClientFactory::class);
		$this->searchStrategyFactory = $this->createMock(SearchStrategyFactory::class);
		$this->searchStringParser = $this->createMock(SearchFilterStringParser::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->logger = $this->createMock(ILogger::class);

		$this->search = new MailSearch(
			$this->imapClientFactory,
			$this->searchStrategyFactory,
			$this->searchStringParser,
			$this->mailboxMapper,
			$this->logger
		);
	}

	public function testNoFindMessages() {
		$account = $this->createMock(Account::class);
		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->imapClientFactory->expects($this->once())
			->method('getClient')
			->with($account)
			->willReturn($client);
		$client->expects($this->once())
			->method('fetch')
			->willReturn(new Horde_Imap_Client_Fetch_Results());

		$messages = $this->search->findMessages(
			$account,
			'INBOX',
			null,
			null
		);

		$this->assertEmpty($messages);
	}

}
