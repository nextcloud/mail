<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use DateTimeImmutable;
use JmapClient\Enums\Mail\MailHeaderForm;
use JmapClient\Responses\Mail\MailParameters as MailParametersResponse;
use JmapClient\Responses\Mail\MailPart as MailPartResponse;
use OCA\Mail\Db\Tag;
use OCA\Mail\JMAP\JmapMessageAdapter;
use OCA\Mail\Service\Html;
use PHPUnit\Framework\MockObject\MockObject;

final class JmapMessageAdapterTest extends TestCase {
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

	public function testMapsSentDateToTimestamp(): void {
		$source = $this->source([
			'sent' => new DateTimeImmutable('2026-11-12T10:00:00+02:00'),
			'received' => new DateTimeImmutable('2026-11-12T09:00:00Z'),
		]);

		$message = $this->adapter->convertToDatabaseMessage($source);

		self::assertSame(1794470400, $message->getSentAt());
		self::assertSame(1794470400, $message->getUpdatedAt());
	}

	public function testFallsBackToReceivedDateWhenSentDateIsMissing(): void {
		$source = $this->source(['received' => new DateTimeImmutable('2026-11-12T09:00:00Z')]);

		$message = $this->adapter->convertToDatabaseMessage($source);

		self::assertSame(1794474000, $message->getSentAt());
	}

	public function testModelMessageUsesReceivedDate(): void {
		$source = $this->source([
			'sent' => new DateTimeImmutable('2026-11-12T10:00:00+02:00'),
			'received' => new DateTimeImmutable('2026-11-12T09:00:00Z'),
		]);

		$message = $this->adapter->convertToModelMessage($source, 1, false);

		self::assertSame(1794474000, $message->getSentDate()->getTimestamp());
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
			'$hasattachment' => true,
			'$hasnoattachment' => true,
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

	public static function aiGeneratedHeaderProvider(): \Iterator {
		yield 'absent' => [null, false];
		yield 'set' => ['1', true];
		yield 'set with whitespace' => [' 1 ', true];
		yield 'other value' => ['0', false];
	}

	/**
	 * @dataProvider aiGeneratedHeaderProvider
	 */
	public function testModelMessageAiGeneratedHeader(?string $headerValue, bool $expected): void {
		$source = $this->source();
		$source->method('header')->willReturnCallback(
			static fn (string $name, MailHeaderForm $form): ?string => $name === 'X-AI-Generated' && $form === MailHeaderForm::Text ? $headerValue : null,
		);

		$message = $this->adapter->convertToModelMessage($source, 1, false);

		self::assertSame($expected, $message->jsonSerialize()['hasAiGeneratedHeader']);
	}

	/**
	 * @param mixed $urls List-Unsubscribe in the URLs form
	 * @return array{unsubscribeUrl: ?string, unsubscribeMailto: ?string, isOneClickUnsubscribe: bool}
	 */
	private function unsubscribeTargets(mixed $urls, ?string $post = null): array {
		$source = $this->source();
		$source->method('header')->willReturnCallback(
			static fn (string $name, MailHeaderForm $form): mixed => match (true) {
				$name === 'List-Unsubscribe' && $form === MailHeaderForm::URLs => $urls,
				$name === 'List-Unsubscribe-Post' && $form === MailHeaderForm::Text => $post,
				default => null,
			},
		);

		$json = $this->adapter->convertToModelMessage($source, 1, false)->jsonSerialize();

		return [
			'unsubscribeUrl' => $json['unsubscribeUrl'],
			'unsubscribeMailto' => $json['unsubscribeMailto'],
			'isOneClickUnsubscribe' => $json['isOneClickUnsubscribe'],
		];
	}

	public function testModelMessageReadsUnsubscribeTargetsFromUrls(): void {
		$targets = $this->unsubscribeTargets(
			['https://example.org/unsubscribe', 'mailto:unsubscribe@example.org'],
			'List-Unsubscribe=One-Click',
		);

		self::assertSame([
			'unsubscribeUrl' => 'https://example.org/unsubscribe',
			'unsubscribeMailto' => 'mailto:unsubscribe@example.org',
			'isOneClickUnsubscribe' => true,
		], $targets);
	}

	public function testModelMessageUsesTheFirstUnsubscribeTargetOfEachScheme(): void {
		$targets = $this->unsubscribeTargets([
			'mailto:first@example.org',
			'ftp://example.org/unsubscribe',
			'HTTPS://example.org/first',
			'mailto:second@example.org',
			'http://example.org/second',
		]);

		self::assertSame([
			'unsubscribeUrl' => 'HTTPS://example.org/first',
			'unsubscribeMailto' => 'mailto:first@example.org',
			'isOneClickUnsubscribe' => false,
		], $targets);
	}

	public function testModelMessageWithoutListUnsubscribe(): void {
		$targets = $this->unsubscribeTargets(null, 'List-Unsubscribe=One-Click');

		self::assertSame([
			'unsubscribeUrl' => null,
			'unsubscribeMailto' => null,
			'isOneClickUnsubscribe' => false,
		], $targets);
	}

	public function testModelMessageIsNotOneClickUnsubscribeWithMailtoOnly(): void {
		$targets = $this->unsubscribeTargets(['mailto:unsubscribe@example.org'], 'List-Unsubscribe=One-Click');

		self::assertSame([
			'unsubscribeUrl' => null,
			'unsubscribeMailto' => 'mailto:unsubscribe@example.org',
			'isOneClickUnsubscribe' => false,
		], $targets);
	}

	public function testModelMessageSkipsInvalidUnsubscribeTargets(): void {
		$targets = $this->unsubscribeTargets(['', 42, null, 'mailto:unsubscribe@example.org']);

		self::assertSame([
			'unsubscribeUrl' => null,
			'unsubscribeMailto' => 'mailto:unsubscribe@example.org',
			'isOneClickUnsubscribe' => false,
		], $targets);
	}

	public function testModelMessageIgnoresUnsubscribeTargetsThatAreNotAList(): void {
		$targets = $this->unsubscribeTargets('https://example.org/unsubscribe', 'List-Unsubscribe=One-Click');

		self::assertSame([
			'unsubscribeUrl' => null,
			'unsubscribeMailto' => null,
			'isOneClickUnsubscribe' => false,
		], $targets);
	}

	/**
	 * @return list<array<string, mixed>>
	 */
	private function schedulingFor(string $calendarContent): array {
		$source = $this->source();
		$source->method('bodyPartStructure')->willReturn(new MailPartResponse([
			'type' => 'multipart/mixed',
			'subParts' => [
				['partId' => '1', 'type' => 'text/plain'],
				['partId' => '2', 'blobId' => 'blob-2', 'type' => 'text/calendar', 'name' => 'event.ics'],
			],
		]));
		$source->method('bodyPartValue')->willReturnCallback(
			static fn (string $partId): ?string => $partId === '2' ? $calendarContent : null,
		);

		return $this->adapter->convertToModelMessage($source, 7, true)->jsonSerialize()['scheduling'];
	}

	public function testModelMessageSchedulingIncludesCalendarContents(): void {
		$ics = "BEGIN:VCALENDAR\r\nMETHOD:request\r\nBEGIN:VEVENT\r\nUID:abc\r\nEND:VEVENT\r\nEND:VCALENDAR\r\n";

		$scheduling = $this->schedulingFor($ics);

		self::assertSame([[
			'id' => '2',
			'messageId' => 7,
			'method' => 'REQUEST',
			'contents' => $ics,
		]], $scheduling);
	}

	public function testModelMessageSchedulingSkipsCalendarWithoutSupportedMethod(): void {
		$scheduling = $this->schedulingFor("BEGIN:VCALENDAR\r\nMETHOD:PUBLISH\r\nEND:VCALENDAR\r\n");

		self::assertSame([], $scheduling);
	}

	public function testModelMessageSchedulingSkipsCalendarWithoutContent(): void {
		$scheduling = $this->schedulingFor('');

		self::assertSame([], $scheduling);
	}
}
