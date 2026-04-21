<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Connector;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailboxConnector;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Tests\Integration\TestCase;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Server;

/**
 * Contract test for {@see IMailboxConnector}. Each protocol (IMAP, JMAP) runs the
 * exact same assertions against its own connector + test server, proving that both
 * implementations honour the same behaviour.
 *
 * Concrete subclasses provide the protocol-specific connector and account; everything
 * else is shared. These tests hit a live server and only run in the integration suite.
 */
abstract class AbstractMailboxConnectorTest extends TestCase {
	protected MailboxMapper $mailboxMapper;
	protected IMailboxConnector $connector;
	protected Account $account;

	abstract protected function createConnector(): IMailboxConnector;

	abstract protected function createAccount(): Account;

	protected function setUp(): void {
		parent::setUp();

		$this->mailboxMapper = Server::get(MailboxMapper::class);
		$this->connector = $this->createConnector();
		$this->account = $this->createAccount();
		$this->connector->syncAll($this->account, true);
	}

	/** Unique per test run so leftover server state never collides. */
	private function uniqueName(string $prefix): string {
		return $prefix . '-' . uniqid();
	}

	/** @return string[] */
	private function localMailboxNames(): array {
		return array_map(
			static fn (Mailbox $mailbox): string => $mailbox->getName(),
			$this->mailboxMapper->findAll($this->account),
		);
	}

	public function testSyncListsTheInbox(): void {
		$inboxes = array_filter(
			$this->mailboxMapper->findAll($this->account),
			static fn (Mailbox $mailbox): bool => $mailbox->isInbox(),
		);

		self::assertNotEmpty($inboxes, 'Synced mailboxes should include the inbox');
	}

	public function testCreateMailboxAppearsLocally(): void {
		$name = $this->uniqueName('Created');

		$created = $this->connector->create($this->account, $name);

		self::assertSame($name, $created->getName());
		self::assertContains($name, $this->localMailboxNames());

		$this->connector->delete($this->account, $created);
	}

	public function testDeleteMailboxRemovesItLocally(): void {
		$name = $this->uniqueName('Doomed');
		$created = $this->connector->create($this->account, $name);

		$this->connector->delete($this->account, $created);

		self::assertNotContains($name, $this->localMailboxNames());
		$this->expectException(DoesNotExistException::class);
		$this->mailboxMapper->find($this->account, $name);
	}

	public function testRenameMailbox(): void {
		$name = $this->uniqueName('Before');
		$newName = $this->uniqueName('After');
		$created = $this->connector->create($this->account, $name);

		$renamed = $this->connector->rename($this->account, $created, $newName);

		self::assertSame($newName, $renamed->getName());
		$names = $this->localMailboxNames();
		self::assertContains($newName, $names);
		self::assertNotContains($name, $names);

		$this->connector->delete($this->account, $renamed);
	}

	public function testSubscribeAndUnsubscribe(): void {
		$name = $this->uniqueName('Sub');
		$created = $this->connector->create($this->account, $name);

		$unsubscribed = $this->connector->subscribe($this->account, $created, false);
		self::assertStringNotContainsString('\\subscribed', $unsubscribed->getAttributes() ?? '');

		$subscribed = $this->connector->subscribe($this->account, $unsubscribed, true);
		self::assertStringContainsString('\\subscribed', $subscribed->getAttributes() ?? '');

		$this->connector->delete($this->account, $subscribed);
	}
}
