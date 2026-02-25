<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\UserMigration\Service;

use OCA\Mail\Service\TextBlockService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class TextBlocksMigrationService {
	public const TEXT_BLOCKS_FILE = MailAccountMigrator::EXPORT_ROOT . '/text_blocks.json';

	public function __construct(
		private readonly TextBlockService $textBlockService,
		private readonly IL10N $l10n,
	) {
	}

	/**
	 * Export all text blocks the user created itself.
	 * This does not include those shared with others.
	 *
	 * @throws UserMigrationException
	 */
	public function exportTextBlocks(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$textBlocks = $this->textBlockService->findAll($user->getUID());
		$exportDestination->addFileContents(self::TEXT_BLOCKS_FILE, json_encode($textBlocks));
	}

	/**
	 * Import all text blocks the user created itself on export.
	 * This does not include those shared with others.
	 *
	 * @throws UserMigrationException
	 * @throws JsonException
	 */
	public function importTextBlocks(IUser $user, IImportSource $importSource): void {
		$textBlocks = json_decode($importSource->getFileContents(self::TEXT_BLOCKS_FILE), true, flags: JSON_THROW_ON_ERROR);

		foreach ($textBlocks as $textBlock) {
			$this->textBlockService->create($user->getUID(), $textBlock['title'], $textBlock['content']);
		}
	}

	public function deleteAllTextBlocks(IUser $user, IImportSource $importSource): void {
		$this->textBlockService->deleteAll($user->getUID());
	}
}
