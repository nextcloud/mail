<?php

declare(strict_types=1);

/**
 * @copyright 2021 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author 2021 Richard Steinmetz <richard@steinmetz.cloud>
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

namespace OCA\Mail\Tests\Unit\Service\Sync;

use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\MailboxNotCachedException;
use OCA\Mail\IMAP\MailboxStats;
use OCA\Mail\IMAP\MailboxSync;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\IMAP\Sync\Response;
use OCA\Mail\Service\Search\FilterStringParser;
use OCA\Mail\Service\Sync\ImapToDbSynchronizer;
use OCA\Mail\Service\Sync\SyncService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class SyncServiceTest extends TestCase {
	/** @var ImapToDbSynchronizer */
	private $synchronizer;

	/** @var FilterStringParser */
	private $filterStringParser;

	/** @var MailboxMapper */
	private $mailboxMapper;

	/** @var MessageMapper */
	private $messageMapper;

	/** @var PreviewEnhancer */
	private $previewEnhancer;

	/** @var LoggerInterface */
	private $loggerInterface;

	/** @var MailboxSync */
	private $mailboxSync;

	/** @var SyncService */
	private $syncService;

	protected function setUp(): void {
		parent::setUp();

		$this->synchronizer = $this->createMock(ImapToDbSynchronizer::class);
		$this->filterStringParser = $this->createMock(FilterStringParser::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->previewEnhancer = $this->createMock(PreviewEnhancer::class);
		$this->loggerInterface = $this->createMock(LoggerInterface::class);
		$this->mailboxSync = $this->createMock(MailboxSync::class);

		$this->syncService = new SyncService(
			$this->synchronizer,
			$this->filterStringParser,
			$this->mailboxMapper,
			$this->messageMapper,
			$this->previewEnhancer,
			$this->loggerInterface,
			$this->mailboxSync
		);
	}

	public function testPartialSyncOnUncachedMailbox() {
		$account = $this->createMock(Account::class);
		$mailbox = $this->createMock(Mailbox::class);
		$mailbox->expects($this->once())
			->method('isCached')
			->willReturn(false);

		$this->expectException(MailboxNotCachedException::class);
		$this->syncService->syncMailbox(
			$account,
			$mailbox,
			42,
			[],
			true
		);
	}

	public function testSyncMailboxReturnsFolderStats() {
		$account = $this->createMock(Account::class);
		$account->method('getUserId')->willReturn('user');
		$mailbox = new Mailbox();
		$mailbox->setMessages(42);
		$mailbox->setUnseen(10);
		$expectedResponse = new Response(
			[],
			[],
			[],
			new MailboxStats(42, 10, null)
		);

		$this->messageMapper
			->method('findUidsForIds')
			->with($mailbox, [])
			->willReturn([]);
		$this->synchronizer->expects($this->once())
			->method('sync')
			->with(
				$account,
				$mailbox,
				$this->loggerInterface,
				0,
				[],
				true
			);
		$this->mailboxSync->expects($this->once())
			->method('syncStats')
			->with($account, $mailbox);

		$response = $this->syncService->syncMailbox(
			$account,
			$mailbox,
			0,
			[],
			false
		);

		$this->assertEquals($expectedResponse, $response);
	}
}
