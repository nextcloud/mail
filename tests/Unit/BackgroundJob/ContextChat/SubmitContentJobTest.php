<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\BackgroundJob;

use ChristophWurst\Nextcloud\Testing\ServiceMockObject;
use ChristophWurst\Nextcloud\Testing\TestCase;
use Horde_Imap_Client_DateTime;
use Horde_Imap_Client_Socket;
use OC\BackgroundJob\JobList;
use OCA\Mail\Account;
use OCA\Mail\AddressList;
use OCA\Mail\AppInfo\Application;
use OCA\Mail\BackgroundJob\ContextChat\SubmitContentJob;
use OCA\Mail\Db\Mailbox;
// use OCA\Mail\Db\Message;
use OCA\Mail\Model\IMAPMessage;
// use OCA\Mail\Service\ContextChat\JobsService;
use OCA\Mail\Service\Html;

class SubmitContentJobTest extends TestCase {
	/** @var ServiceMockObject */
	private $serviceMock;

	/** @var SubmitContentJob */
	private $job;

	/** @var Account|MockObject */
	private $account;

	/** @var Mailbox|MockObject */
	private $mailbox;

	protected function setUp(): void {
		parent::setUp();

		if (!class_exists(\OCP\ContextChat\IContentProvider::class)) {
			$this->markTestSkipped();
		}

		// TODO: setup other components if needed
		$this->serviceMock = $this->createServiceMock(SubmitContentJob::class);
		$this->job = $this->serviceMock->getService();

		// Make sure the job is actually run
		$this->serviceMock->getParameter('time')
			->method('getTime')
			->willReturn(500000);
		// Set our common argument
		$this->job->setArgument([
			'userId' => 'user123',
			'accountId' => 1,
			'mailboxId' => 1,
			'nextMessageId' => 1,
			'startTime' => time() - Application::CONTEXT_CHAT_MESSAGE_MAX_AGE,
		]);
		// Set a fake ID
		$this->job->setId(99);
	}

	public function testRun(): void {
		// TODO: add tests for various number of messages per mailbox and starting IDs
		$this->markTestIncomplete();

		$client = $this->createMock(Horde_Imap_Client_Socket::class);
		$this->serviceMock->getParameter('clientFactory')
			->expects(self::once())
			->method('getClient')
			->with($this->account)
			->willReturn($client);
		$imapMessage = new IMAPMessage(
			123,
			'',
			[],
			new AddressList([]),
			new AddressList([]),
			new AddressList([]),
			new AddressList([]),
			new AddressList([]),
			'',
			'',
			'',
			false,
			[],
			[],
			false,
			[],
			new Horde_Imap_Client_DateTime(),
			'',
			'',
			false,
			[],
			null,
			false,
			'',
			'',
			false,
			false,
			false,
			$this->createMock(Html::class),
			false,
		);
		$this->serviceMock->getParameter('mailManager')
			->expects(self::once())
			->method('getImapMessage')
			->with($client, $this->account, $this->mailbox, 123, true)
			->willReturn($imapMessage);
		$this->serviceMock->getParameter('contextChatProvider')
			->expects(self::once())
			->method('getId')
			->willReturn('mail');
		$this->serviceMock->getParameter('contentManager')
			->expects(self::once())
			->method('submitContent');

		$this->job->setArgument([
			'userId' => 'user123',
			'accountId' => 1,
			'mailboxId' => 1,
			'nextMessageId' => 1,
			'startTime' => time() - Application::CONTEXT_CHAT_MESSAGE_MAX_AGE,
		]);
		$this->job->start($this->createMock(JobList::class));
	}
}
