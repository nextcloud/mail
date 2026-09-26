<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Account;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Events\MailboxesSynchronizedEvent;
use OCA\Mail\Listener\MailboxesSynchronizedSpecialMailboxesUpdater;
use Psr\Log\LoggerInterface;

class MailboxesSynchronizedSpecialMailboxesUpdaterTest extends TestCase {
	private MailAccountMapper $mailAccountMapper;
	private MailboxMapper $mailboxMapper;
	private MailboxesSynchronizedSpecialMailboxesUpdater $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->mailAccountMapper = $this->createMock(MailAccountMapper::class);
		$this->mailboxMapper = $this->createMock(MailboxMapper::class);

		$this->listener = new MailboxesSynchronizedSpecialMailboxesUpdater(
			$this->mailAccountMapper,
			$this->mailboxMapper,
			$this->createMock(LoggerInterface::class),
		);
	}

	public function testIgnoresSharedMailboxWithMatchingName(): void {
		$mailAccount = new MailAccount();
		$account = new Account($mailAccount);
		$sharedSent = new Mailbox();
		$sharedSent->setId(100);
		$sharedSent->setName('Sent');
		$sharedSent->setSpecialUse('[]');
		$sharedSent->setShared(true);
		$personalSent = new Mailbox();
		$personalSent->setId(200);
		$personalSent->setName('Sent');
		$personalSent->setSpecialUse('[]');
		$personalSent->setShared(false);
		$this->mailboxMapper->method('findAll')
			->willReturn([$sharedSent, $personalSent]);
		$this->mailAccountMapper->expects($this->once())
			->method('update')
			->with($this->callback(function (MailAccount $updated) {
				return $updated->getSentMailboxId() === 200;
			}));

		$this->listener->handle(new MailboxesSynchronizedEvent($account));
	}

	public function testIgnoresSharedMailboxWithMatchingSpecialUse(): void {
		$mailAccount = new MailAccount();
		$account = new Account($mailAccount);
		$sharedDrafts = new Mailbox();
		$sharedDrafts->setId(100);
		$sharedDrafts->setName('Shared/Drafts');
		$sharedDrafts->setSpecialUse('["drafts"]');
		$sharedDrafts->setShared(true);
		$personalDrafts = new Mailbox();
		$personalDrafts->setId(200);
		$personalDrafts->setName('Drafts');
		$personalDrafts->setSpecialUse('["drafts"]');
		$personalDrafts->setShared(false);
		$this->mailboxMapper->method('findAll')
			->willReturn([$sharedDrafts, $personalDrafts]);
		$this->mailAccountMapper->expects($this->once())
			->method('update')
			->with($this->callback(function (MailAccount $updated) {
				return $updated->getDraftsMailboxId() === 200;
			}));

		$this->listener->handle(new MailboxesSynchronizedEvent($account));
	}

	public function testKeepsExistingAssignmentEvenIfShared(): void {
		$mailAccount = new MailAccount();
		$mailAccount->setSentMailboxId(100);
		$account = new Account($mailAccount);
		$sharedSent = new Mailbox();
		$sharedSent->setId(100);
		$sharedSent->setName('Sent');
		$sharedSent->setSpecialUse('[]');
		$sharedSent->setShared(true);
		$this->mailboxMapper->method('findAll')
			->willReturn([$sharedSent]);
		$this->mailAccountMapper->expects($this->once())
			->method('update')
			->with($this->callback(function (MailAccount $updated) {
				return $updated->getSentMailboxId() === 100;
			}));

		$this->listener->handle(new MailboxesSynchronizedEvent($account));
	}
}
