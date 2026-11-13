<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Command;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Command\StdinImapPasswords;
use Symfony\Component\Console\Input\StreamableInputInterface;

class StdinImapPasswordsTest extends TestCase {
	private function input(string $contents): StreamableInputInterface {
		$stream = fopen('php://memory', 'r+');
		fwrite($stream, $contents);
		rewind($stream);
		$input = $this->createMock(StreamableInputInterface::class);
		$input->method('getStream')->willReturn($stream);
		return $input;
	}

	public function testReadsBothPasswordsWithoutTrimmingSpaces(): void {
		self::assertSame([' imap secret ', 'smtp secret'], StdinImapPasswords::read($this->input(" imap secret \r\nsmtp secret\n")));
	}

	public function testAcceptsMaximumPasswordLength(): void {
		$imapPassword = str_repeat('x', 4096);

		self::assertSame([$imapPassword, 'smtp'], StdinImapPasswords::read($this->input($imapPassword . "\nsmtp\n")));
	}

	/** @dataProvider invalidStreams */
	#[\PHPUnit\Framework\Attributes\DataProvider('invalidStreams')]
	public function testRejectsIncompleteOrInvalidStreams(string $contents): void {
		$this->expectException(\InvalidArgumentException::class);
		StdinImapPasswords::read($this->input($contents));
	}

	public static function invalidStreams(): iterable {
		yield 'empty' => [''];
		yield 'only imap' => ["imap\n"];
		yield 'unterminated smtp' => ["imap\nsmtp"];
		yield 'blank imap' => ["\nsmtp\n"];
		yield 'blank smtp' => ["imap\n\n"];
		yield 'nul byte' => ["im\0ap\nsmtp\n"];
		yield 'embedded carriage return' => ["im\rap\nsmtp\n"];
		yield 'oversized imap' => [str_repeat('x', 4097) . "\nsmtp\n"];
		yield 'oversized smtp' => ["imap\n" . str_repeat('x', 4097) . "\n"];
	}
}
