<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration\Service;

use JsonException;
use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class TagsMigrationService {
	public const TAGS_FILE = MailAccountMigrator::EXPORT_ROOT . '/tags.json';

	public function __construct(
		private readonly TagMapper $tagMapper,
		private readonly IL10N $l10n,
	) {
	}

	/**
	 * Export all tags the user currently uses.
	 *
	 * @throws UserMigrationException
	 */
	public function exportTags(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t('Exporting tags for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$tags = $this->tagMapper->getAllTagsForUser($user->getUID());

		try {
			$exportDestination->addFileContents(self::TAGS_FILE, json_encode($tags, JSON_THROW_ON_ERROR));
		} catch (JsonException|UserMigrationException $exception) {
			throw new UserMigrationException(
				"Failed to export tags for user {$user->getUID()}",
				previous: $exception
			);
		}
	}

	/**
	 * Import all tags the user used on export.
	 *
	 * @throws \OCP\UserMigration\UserMigrationException
	 * @throws \OCP\DB\Exception
	 */
	public function importTags(IUser $user, IImportSource $importSource, OutputInterface $output): array {
		$output->writeln(
			$this->l10n->t('Importing tags for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		try {
			$tagsFileContent = $importSource->getFileContents(self::TAGS_FILE);
		} catch (UserMigrationException) {
			$output->writeln(
				$this->l10n->t('Tags for user %s not found. Continue...', [$user->getUID()]),
				OutputInterface::VERBOSITY_VERBOSE
			);

			return [];
		}

		$tags = json_decode($tagsFileContent, true);
		$this->validateTags($tags);

		$newTags = [];

		foreach ($tags as $tag) {
			$newTag = new Tag();

			$newTag->setUserId($user->getUID());
			$newTag->setDisplayName($tag['displayName']);
			$newTag->setImapLabel($tag['imapLabel']);
			$newTag->setColor($tag['color']);
			$newTag->setIsDefaultTag($tag['isDefaultTag']);

			$newTags[$tag['id']] = $this->tagMapper->insert($newTag)->getId();
		}

		return $newTags;
	}

	/**
	 * @throws \OCP\DB\Exception
	 */
	public function deleteAllTags(IUser $user, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t('Delete existing tags for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$this->tagMapper->deleteAll($user->getUID());
	}

	/**
	 * Validate the parsed tags to ensure they
	 * have the expected structure and types.
	 *
	 * @throws UserMigrationException
	 */
	private function validateTags(mixed $tags): void {
		$tagsArrayIsValid = is_array($tags) && array_is_list($tags);
		if (!$tagsArrayIsValid) {
			throw new UserMigrationException('Invalid tags export structure');
		}

		foreach ($tags as $tag) {
			$tagArrayIsValid = is_array($tag);

			$idIsValid = $tagArrayIsValid
				&& array_key_exists('id', $tag)
				&& is_int($tag['id']);

			$displayNameIsValid = $tagArrayIsValid
				&& array_key_exists('displayName', $tag)
				&& is_string($tag['displayName']);

			$imapLabelIsValid = $tagArrayIsValid
				&& array_key_exists('imapLabel', $tag)
				&& is_string($tag['imapLabel']);

			$colorIsValid = $tagArrayIsValid
				&& array_key_exists('color', $tag)
				&& is_string($tag['color']);

			$isDefaultTagIsValid = $tagArrayIsValid
				&& array_key_exists('isDefaultTag', $tag)
				&& is_bool($tag['isDefaultTag']);

			if (
				!$idIsValid
				|| !$displayNameIsValid
				|| !$imapLabelIsValid
				|| !$colorIsValid
				|| !$isDefaultTagIsValid
			) {
				throw new UserMigrationException('Invalid tag entry');
			}
		}
	}
}
