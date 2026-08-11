<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\IMAP\PreviewEnhancer;
use OCA\Mail\Service\PreprocessingService;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class PreprocessingServiceTest extends TestCase {
	/** @var MailboxMapper|MockObject */
	private $mailboxMapper;

	/** @var MessageMapper|MockObject */
	private $messageMapper;

	/** @var MockObject|LoggerInterface */
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
