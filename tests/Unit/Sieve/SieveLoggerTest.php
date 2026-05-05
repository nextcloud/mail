<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Sieve;

use OCA\Mail\Sieve\SieveLogger;
use PHPUnit\Framework\TestCase;

final class SieveLoggerTest extends TestCase {
	private string $tempDir;

	protected function setUp(): void {
		// Create temporary directory for test files
		$this->tempDir = sys_get_temp_dir() . '/mail-sieve-test-' . uniqid();
		mkdir($this->tempDir, 0777, true);
	}

	protected function tearDown(): void {
		// Cleanup test files recursively
		if (is_dir($this->tempDir)) {
			$this->removeDirectory($this->tempDir);
		}
	}

	private function removeDirectory(string $dir): void {
		if (!is_dir($dir)) {
			return;
		}
		$files = scandir($dir);
		foreach ($files as $file) {
			if ($file === '.' || $file === '..') {
				continue;
			}
			$path = $dir . '/' . $file;
			if (is_dir($path)) {
				$this->removeDirectory($path);
			} else {
				unlink($path);
			}
		}
		rmdir($dir);
	}

	public function testConstructorCreatesLogger(): void {
		$logFile = $this->tempDir . '/test.log';

		$logger = new SieveLogger($logFile);

		$this->assertInstanceOf(SieveLogger::class, $logger);
		$this->assertFileExists($logFile);
	}

	public function testConstructorThrowsOnInvalidPath(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Unable to use');

		// Try to create logger with non-writable path
		new SieveLogger('/root/definitely/not/writable/path/test.log');
	}

	public function testDebugWritesMessageToFile(): void {
		$logFile = $this->tempDir . '/test.log';
		$logger = new SieveLogger($logFile);

		$logger->debug('Test message');
		unset($logger); // Trigger destructor to flush/close

		$content = file_get_contents($logFile);
		$this->assertStringContainsString('Test message', (string)$content);
	}

	public function testDebugWritesMultipleMessages(): void {
		$logFile = $this->tempDir . '/test.log';
		$logger = new SieveLogger($logFile);

		$logger->debug('Message 1');
		$logger->debug('Message 2');
		$logger->debug('Message 3');
		unset($logger);

		$content = file_get_contents($logFile);
		$this->assertStringContainsString('Message 1', (string)$content);
		$this->assertStringContainsString('Message 2', (string)$content);
		$this->assertStringContainsString('Message 3', (string)$content);
	}

	public function testDebugWithEmptyString(): void {
		$logFile = $this->tempDir . '/test.log';
		$logger = new SieveLogger($logFile);

		$logger->debug('');
		unset($logger);

		$this->assertFileExists($logFile);
	}

	public function testDebugWithSpecialCharacters(): void {
		$logFile = $this->tempDir . '/test.log';
		$logger = new SieveLogger($logFile);

		$message = 'Special chars: äöü, émojis 🎉, quotes "test", etc.';
		$logger->debug($message);
		unset($logger);

		$content = file_get_contents($logFile);
		$this->assertStringContainsString('äöü', (string)$content);
		$this->assertStringContainsString('🎉', (string)$content);
	}

	public function testDebugWithMultilineMessage(): void {
		$logFile = $this->tempDir . '/test.log';
		$logger = new SieveLogger($logFile);

		$message = <<<'EOF'
			Line 1
			Line 2
			Line 3
			EOF;
		$logger->debug($message);
		unset($logger);

		$content = file_get_contents($logFile);
		$this->assertStringContainsString('Line 1', (string)$content);
		$this->assertStringContainsString('Line 2', (string)$content);
		$this->assertStringContainsString('Line 3', (string)$content);
	}

	public function testDebugWithLongMessage(): void {
		$logFile = $this->tempDir . '/test.log';
		$logger = new SieveLogger($logFile);

		$message = str_repeat('a', 10000);
		$logger->debug($message);
		unset($logger);

		$content = file_get_contents($logFile);
		$this->assertSame($message, $content);
	}

	public function testConstructorAppendsToExistingFile(): void {
		$logFile = $this->tempDir . '/test.log';

		// Create first logger and write
		$logger1 = new SieveLogger($logFile);
		$logger1->debug('First message');
		unset($logger1);

		// Create second logger and write to same file
		$logger2 = new SieveLogger($logFile);
		$logger2->debug('Second message');
		unset($logger2);

		$content = file_get_contents($logFile);
		$this->assertStringContainsString('First message', (string)$content);
		$this->assertStringContainsString('Second message', (string)$content);
	}

	public function testConstructorWithAbsolutePath(): void {
		$logFile = $this->tempDir . '/subdir/test.log';
		mkdir(dirname($logFile), 0777, true);

		$logger = new SieveLogger($logFile);
		$logger->debug('Test');
		unset($logger);

		$this->assertFileExists($logFile);
	}

	public function testDebugWritesWithoutTrailingNewline(): void {
		$logFile = $this->tempDir . '/test.log';
		$logger = new SieveLogger($logFile);

		$message = 'Message without newline';
		$logger->debug($message);
		unset($logger);

		$content = file_get_contents($logFile);
		// Verify exact content - no automatic newline added
		$this->assertSame($message, $content);
	}

	public function testMultipleDebugCallsAreSequential(): void {
		$logFile = $this->tempDir . '/test.log';
		$logger = new SieveLogger($logFile);

		$logger->debug('A');
		$logger->debug('B');
		$logger->debug('C');
		unset($logger);

		$content = file_get_contents($logFile);
		// Should be concatenated without separators
		$this->assertSame('ABC', $content);
	}

	public function testDestructorFlushesAndClosesFile(): void {
		$logFile = $this->tempDir . '/test.log';
		$logger = new SieveLogger($logFile);
		$logger->debug('Test message');

		// Before destructor, file might not be flushed
		unset($logger); // Trigger destructor

		// After destructor, file should be readable and contain data
		$this->assertFileExists($logFile);
		$content = file_get_contents($logFile);
		$this->assertSame('Test message', $content);
	}

	public function testDebugWithBinaryData(): void {
		$logFile = $this->tempDir . '/test.log';
		$logger = new SieveLogger($logFile);

		// Binary data (MIME-encoded message part)
		$binaryMessage = "\x89PNG\r\n\x1a\n" . pack('H*', 'deadbeef');
		$logger->debug($binaryMessage);
		unset($logger);

		$content = file_get_contents($logFile);
		$this->assertStringContainsString(pack('H*', 'deadbeef'), (string)$content);
	}

	public function testConstructorMultipleInstancesWithDifferentFiles(): void {
		$logFile1 = $this->tempDir . '/test1.log';
		$logFile2 = $this->tempDir . '/test2.log';

		$logger1 = new SieveLogger($logFile1);
		$logger2 = new SieveLogger($logFile2);

		$logger1->debug('File 1 content');
		$logger2->debug('File 2 content');
		unset($logger1);
		unset($logger2);

		$content1 = file_get_contents($logFile1);
		$content2 = file_get_contents($logFile2);

		$this->assertSame('File 1 content', $content1);
		$this->assertSame('File 2 content', $content2);
	}
}
