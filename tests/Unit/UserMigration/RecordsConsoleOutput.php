<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\UserMigration;

trait RecordsConsoleOutput {
	/** @var list<array{message: string, options: int}> */
	private array $writtenLines = [];

	private function recordConsoleOutput(): void {
		$this->serviceMock->getParameter('l10n')
			->method('t')
			->willReturnCallback(static fn (string $text, array $parameters = []): string => vsprintf($text, $parameters));
		$this->output->method('writeln')
			->willReturnCallback(function (string $message, int $options = 0): void {
				$this->writtenLines[] = ['message' => $message, 'options' => $options];
			});
	}

	private function assertWritten(string $text, int $options): void {
		$lines = array_filter($this->writtenLines, static fn (array $line): bool => str_contains($line['message'], $text));

		self::assertNotEmpty($lines);
		foreach ($lines as $line) {
			self::assertSame($options, $line['options']);
		}
	}
}
