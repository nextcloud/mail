<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Integration\Connector;

use ChristophWurst\Nextcloud\Testing\DatabaseTransaction;
use OCA\Mail\Account;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\JMAP\JmapMailboxConnector;
use OCA\Mail\JMAP\JmapMessageConnector;
use OCA\Mail\Service\JMAP\JmapOperationsService;
use OCA\Mail\Tests\Integration\Framework\JmapTestAccount;
use OCA\Mail\Tests\Integration\TestCase;
use OCP\Server;
use Psr\Log\NullLogger;

final class JmapMessageConnectorTest extends TestCase {
	use DatabaseTransaction;
	use JmapTestAccount;

	private JmapMessageConnector $connector;
	private Account $account;
	private Mailbox $inbox;

	protected function setUp(): void {
		parent::setUp();

		$this->connector = Server::get(JmapMessageConnector::class);
		$this->account = new Account($this->createTestAccount());
		Server::get(JmapMailboxConnector::class)->syncAll($this->account, true);
		$inboxes = array_filter(
			Server::get(MailboxMapper::class)->findAll($this->account),
			static fn (Mailbox $mailbox): bool => $mailbox->isInbox(),
		);
		self::assertNotEmpty($inboxes, 'The test account has no inbox');
		$this->inbox = reset($inboxes);
	}

	public function testFetchedMessageHasListUnsubscribeTargets(): void {
		$operations = Server::get(JmapOperationsService::class);
		$operations->connect($this->account);
		$created = $operations->entityCreate($this->inbox->getRemoteId(), [
			'subject' => 'List-Unsubscribe ' . uniqid(),
			'from' => [['email' => 'list@example.org']],
			'header:List-Unsubscribe:asURLs' => ['https://example.org/unsubscribe', 'mailto:unsubscribe@example.org'],
			'header:List-Unsubscribe-Post:asText' => 'List-Unsubscribe=One-Click',
			'bodyValues' => ['body' => ['value' => 'Hello']],
			'textBody' => [['partId' => 'body', 'type' => 'text/plain']],
		]);
		self::assertNotNull($created, 'The JMAP server did not create the message');

		try {
			$this->connector->syncMailbox($this->account, $this->inbox, new NullLogger(), 0, null, true);
			$messages = Server::get(MessageMapper::class)->findByRemoteIds($this->inbox, [$created['id']]);
			self::assertCount(1, $messages, 'The synced inbox does not contain the message');
			$fetched = $this->connector->fetchMessages($this->account, $this->inbox, true, ...$messages);
		} finally {
			$operations->entityDelete($created['id']);
		}

		$json = reset($fetched)->jsonSerialize();
		self::assertSame('https://example.org/unsubscribe', $json['unsubscribeUrl']);
		self::assertSame('mailto:unsubscribe@example.org', $json['unsubscribeMailto']);
		self::assertTrue($json['isOneClickUnsubscribe']);
	}
}
