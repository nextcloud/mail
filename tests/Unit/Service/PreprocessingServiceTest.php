<?php

declare(strict_types=1);

/**
 * @copyright 2022 Anna Larch <anna.larch@gm.net>
 *
 * @author 2022 Anna Larch <anna.larch@gm.net>
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

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\Service\PreprocessingService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class PreprocessingServiceTest extends TestCase {
	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var MockObject|LoggerInterface  */
	private $logger;

	/** @var MockObject|PreviewEnhancer */
	private $previewEnhancer;

	private PreprocessingService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->mailboxMapper = $this->createMock(MailboxMapper::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->messageMapper = $this->createMock(MessageMapper::class);
		$this->previewEnhancer = $this->createMock(PreviewEnhancer::class);

		$this->service = new PreprocessingService(
			$this->messageMapper,
			$this->logger,
			$this->mailboxMapper,
			$this->previewEnhancer
		);
	}

	public function testNoMailboxes(): void {
		$account = new Account(new MailAccount());
		$timestamp = 0;

		$this->mailboxMapper->expects(self::once())
			->method('findAll')
			->with($account)
			->willReturn([]);
		$this->messageMapper->expects(self::never())
			->method('getUnanalyzed');
		$this->previewEnhancer->expects(self::never())
			->method('process');

		$this->service->process($timestamp, $account);
	}

	public function testNoUnanalysed(): void {
		$account = new Account(new MailAccount());
		$timestamp = 0;
		$mailbox = new Mailbox();
		$mailbox->setId(1);

		$this->mailboxMapper->expects(self::once())
			->method('findAll')
			->with($account)
			->willReturn([$mailbox]);
		$this->messageMapper->expects(self::once())
			->method('getUnanalyzed')
			->with($timestamp, [$mailbox->getId()])
			->willReturn([]);
		$this->previewEnhancer->expects(self::never())
			->method('process');

		$this->service->process($timestamp, $account);
	}

	public function testProcessing(): void {
		$account = new Account(new MailAccount());
		$timestamp = 0;
		$mailbox = new Mailbox();
		$mailbox->setId(1);
		$message = new Message();
		$message->setMailboxId($mailbox->getId());

		$this->mailboxMapper->expects(self::once())
			->method('findAll')
			->with($account)
			->willReturn([$mailbox]);
		$this->messageMapper->expects(self::once())
			->method('getUnanalyzed')
			->with($timestamp, [$mailbox->getId()])
			->willReturn([$message]);
		$this->previewEnhancer->expects(self::once())
			->method('process')
			->with($account, $mailbox, [$message])
			->willReturn([$message]);

		$this->service->process($timestamp, $account);
	}
}
