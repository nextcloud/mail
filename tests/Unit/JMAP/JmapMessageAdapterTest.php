<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use JmapClient\Responses\Mail\MailParameters as MailParametersResponse;
use OCA\Mail\Db\Tag;
use OCA\Mail\JMAP\JmapMessageAdapter;
use OCA\Mail\Service\Html;
use PHPUnit\Framework\MockObject\MockObject;

class JmapMessageAdapterTest extends TestCase {
	private JmapMessageAdapter $adapter;

	protected function setUp(): void {
		parent::setUp();

		$this->adapter = new JmapMessageAdapter($this->createMock(Html::class));
	}

	/**
	 * Build a fully stubbed JMAP message response. Every accessor returns a
	 * harmless default so a test only has to override what it cares about.
	 *
	 * @param array<string, mixed> $values
	 * @param array<string, bool> $keywords keyword => present
	 */
	private function source(array $values = [], array $keywords = []): MailParametersResponse&MockObject {
		$source = $this->createMock(MailParametersResponse::class);

		$defaults = [
			'id' => 'remote-1',
			'messageId' => null,
			'inReplyTo' => null,
			'references' => null,
			'thread' => null,
			'subject' => null,
			'sent' => null,
			'received' => null,
			'answered' => null,
			'draft' => null,
			'flagged' => null,
			'seen' => null,
			'forwarded' => null,
			'junk' => null,
			'notjunk' => null,
			'bodyTextPreview' => null,
			'hasAttachment' => null,
			'from' => null,
			'sender' => [],
			'to' => null,
			'cc' => null,
			'bcc' => null,
		];
		foreach (array_merge($defaults, $values) as $method => $value) {
			$source->method($method)->willReturn($value);
		}

		$source->method('keywords')->willReturn($keywords);
		$source->method('parameter')->willReturn($values['__updatedAt'] ?? null);
		$source->method('keyword')->willReturnCallback(
			static fn (string $name): ?bool => $keywords[$name] ?? null,
		);

		return $source;
	}

	public function testMapsFlags(): void {
		$source = $this->source([
			'answered' => true,
			'draft' => true,
			'flagged' => true,
			'seen' => true,
			'forwarded' => true,
			'junk' => true,
			'notjunk' => true,
			'hasAttachment' => true,
		], [
			'$deleted' => true,
			'$mdnsent' => true,
			Tag::LABEL_IMPORTANT => true,
		]);

		$message = $this->adapter->convertToDatabaseMessage($source);

		self::assertTrue($message->getFlagAnswered());
		self::assertTrue($message->getFlagDraft());
		self::assertTrue($message->getFlagFlagged());
		self::assertTrue($message->getFlagSeen());
		self::assertTrue($message->getFlagForwarded());
		self::assertTrue($message->getFlagJunk());
		self::assertTrue($message->getFlagNotjunk());
		self::assertTrue($message->getFlagAttachments());
		self::assertTrue($message->getFlagDeleted());
		self::assertTrue($message->getFlagMdnsent());
		self::assertTrue($message->getFlagImportant());
	}

	public function testUnsetFlagsDefaultToFalseNotNull(): void {
		$message = $this->adapter->convertToDatabaseMessage($this->source());

		self::assertFalse($message->getFlagAnswered());
		self::assertFalse($message->getFlagSeen());
		self::assertFalse($message->getFlagImportant());
		self::assertFalse($message->getFlagAttachments());
	}

	public function testImportantViaLegacyLabel(): void {
		$source = $this->source([], ['$label1' => true]);

		$message = $this->adapter->convertToDatabaseMessage($source);

		self::assertTrue($message->getFlagImportant());
	}

	public function testReferencesArrayIsJsonEncoded(): void {
		$source = $this->source(['references' => ['<a@x>', '', '<b@x>']]);

		$message = $this->adapter->convertToDatabaseMessage($source);

		self::assertSame('["<a@x>","<b@x>"]', $message->getReferences());
	}

	public function testReferencesNullStaysNull(): void {
		$message = $this->adapter->convertToDatabaseMessage($this->source(['references' => null]));

		self::assertNull($message->getReferences());
	}

	public function testSubjectDefaultsToEmptyString(): void {
		$message = $this->adapter->convertToDatabaseMessage($this->source(['subject' => null]));

		self::assertSame('', $message->getSubject());
	}

	public function testConvertsCustomKeywordsToTagsAndSkipsReserved(): void {
		$source = $this->source([], [
			'$seen' => true,
			'$flagged' => true,
			'work' => true,
			'urgent' => true,
			'ignored' => false,
		]);

		$message = $this->adapter->convertToDatabaseMessage($source);

		$labels = array_map(static fn (Tag $tag): string => $tag->getImapLabel(), $message->getTags());
		sort($labels);
		self::assertSame(['urgent', 'work'], $labels);
	}

	public function testMapsFromAddress(): void {
		$source = $this->source([
			'from' => [['email' => 'alice@example.com', 'name' => 'Alice']],
		]);

		$message = $this->adapter->convertToDatabaseMessage($source);

		$from = $message->getFrom()->first();
		self::assertNotNull($from);
		self::assertSame('alice@example.com', $from->getEmail());
	}

	public function testFallsBackToSenderWhenFromMissing(): void {
		$source = $this->source([
			'from' => null,
			'sender' => [['email' => 'sender@example.com', 'name' => 'Sender']],
		]);

		$message = $this->adapter->convertToDatabaseMessage($source);

		$from = $message->getFrom()->first();
		self::assertNotNull($from);
		self::assertSame('sender@example.com', $from->getEmail());
	}
}
