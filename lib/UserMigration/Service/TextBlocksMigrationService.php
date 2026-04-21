<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration\Service;

use JsonException;
use OCA\Mail\Service\TextBlockService;
use OCA\Mail\UserMigration\MailAccountMigrator;
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
		$output->writeln(
			$this->l10n->t('Exporting text blocks for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$textBlocks = $this->textBlockService->findAll($user->getUID());

		try {
			$exportDestination->addFileContents(self::TEXT_BLOCKS_FILE, json_encode($textBlocks, JSON_THROW_ON_ERROR));
		} catch (JsonException|UserMigrationException $exception) {
			throw new UserMigrationException(
				"Failed to export text blocks for user {$user->getUID()}",
				previous: $exception
			);
		}
	}

	/**
	 * Import all text blocks the user created itself on export.
	 * This does not include those shared with others.
	 *
	 * @throws UserMigrationException
	 */
	public function importTextBlocks(IUser $user, IImportSource $importSource, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t('Importing text blocks for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		try {
			$textBlocksFileContent = $importSource->getFileContents(self::TEXT_BLOCKS_FILE);
		} catch (UserMigrationException) {
			$output->writeln(
				$this->l10n->t('Text blocks for user %s not found. Continue...', [$user->getUID()]),
				OutputInterface::VERBOSITY_VERBOSE
			);

			return;
		}

		$textBlocks = json_decode($textBlocksFileContent, true);
		$this->validateTextBlocks($textBlocks);

		foreach ($textBlocks as $textBlock) {
			$output->writeln(
				$this->l10n->t('Importing text block %s for user %s', [$textBlock['title'], $user->getUID()]),
				OutputInterface::VERBOSITY_VERBOSE
			);

			$this->textBlockService->create($user->getUID(), $textBlock['title'], $textBlock['content']);
		}
	}

	public function deleteAllTextBlocks(IUser $user, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t('Delete existing text blocks for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$this->textBlockService->deleteByUserId($user->getUID());
	}

	/**
	 * Validate the parsed text blocks to ensure they
	 * have the expected structure and types.
	 *
	 * @throws UserMigrationException
	 */
	private function validateTextBlocks(mixed $textBlocks): void {
		$textBlocksArrayIsValid = is_array($textBlocks) && array_is_list($textBlocks);
		if (!$textBlocksArrayIsValid) {
			throw new UserMigrationException('Invalid text blocks export structure');
		}

		foreach ($textBlocks as $textBlock) {
			$textBlockArrayIsValid = is_array($textBlock);

			$titleIsValid = $textBlockArrayIsValid
				&& array_key_exists('title', $textBlock)
				&& is_string($textBlock['title']);

			$contentIsValid = $textBlockArrayIsValid
				&& array_key_exists('content', $textBlock)
				&& is_string($textBlock['content']);

			if (!$titleIsValid || !$contentIsValid) {
				throw new UserMigrationException('Invalid text block entry');
			}
		}
	}
}
