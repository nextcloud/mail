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
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\DB\Exception;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class TagsMigrationService {
	public const TAGS_FILE = MailAccountMigrator::EXPORT_ROOT . '/tags.json';

	public const ESTIMATED_TAG_SIZE = 150;

	public function __construct(
		private readonly TagMapper $tagMapper,
		private readonly IL10N $l10n,
	) {
	}

	public function getEstimatedExportSize(IUser $user): int {
		return count($this->tagMapper->getAllTagsForUser($user->getUID())) * self::ESTIMATED_TAG_SIZE;
	}

	/**
	 * Export all tags the user currently uses.
	 *
	 * @throws UserMigrationException
	 */
	public function exportTags(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t(
				'Exporting tags for %s',
				[$user->getUID()]
			), OutputInterface::VERBOSITY_VERBOSE
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
	 * @throws DoesNotExistException
	 * @throws Exception
	 */
	public function importTags(IUser $user, IImportSource $importSource, OutputInterface $output): array {
		$output->writeln(
			$this->l10n->t(
				'Importing tags for %s',
				[$user->getUID()]
			), OutputInterface::VERBOSITY_VERBOSE
		);

		try {
			$tagsFileContent = $importSource->getFileContents(self::TAGS_FILE);
		} catch (UserMigrationException) {
			$output->writeln(
				$this->l10n->t(
					'Tags for %s not found. Continue …',
					[$user->getUID()]
				), OutputInterface::VERBOSITY_VERBOSE
			);

			return [];
		}

		try {
			$tags = json_decode($tagsFileContent, true, flags: JSON_THROW_ON_ERROR);
			$this->validateTags($tags);
		} catch (JsonException|UserMigrationException) {
			$output->writeln(
				$this->l10n->t(
					'Tag configuration for %s is invalid and will be skipped. Continue …',
					[$user->getUID()]
				)
			);

			return [];
		}

		$newTags = [];
		$existingTagIds = $this->getTagIdsByImapLabel($user->getUID());

		foreach ($tags as $tag) {
			if (!$this->isWellFormedTag($tag)) {
				$output->writeln(
					$this->l10n->t(
						'A tag of %s is invalid and will be skipped. Continue …',
						[$user->getUID()]
					)
				);

				continue;
			}

			$existingTagId = $existingTagIds[$tag['imapLabel']] ?? null;
			if ($existingTagId !== null) {
				$output->writeln(
					$this->l10n->t(
						'Tag %1$s already exists for %2$s and will be reused. Continue …',
						[$tag['imapLabel'], $user->getUID()]
					), OutputInterface::VERBOSITY_VERBOSE
				);

				$newTags[$tag['id']] = $existingTagId;

				continue;
			}

			if (!$this->isValidTag($tag)) {
				$output->writeln(
					$this->l10n->t(
						'Tag %1$s of %2$s is invalid and will be skipped. Continue …',
						[$tag['imapLabel'], $user->getUID()]
					)
				);

				continue;
			}

			$newTag = new Tag();

			$newTag->setUserId($user->getUID());
			$newTag->setDisplayName($tag['displayName']);
			$newTag->setImapLabel($tag['imapLabel']);
			$newTag->setColor($tag['color']);
			$newTag->setIsDefaultTag($tag['isDefaultTag']);

			try {
				$newTagId = $this->tagMapper->insert($newTag)->getId();
			} catch (Exception $exception) {
				if ($exception->getReason() !== Exception::REASON_UNIQUE_CONSTRAINT_VIOLATION) {
					throw $exception;
				}

				// A case-insensitive collation treats labels that only differ in case as the same tag
				$newTagId = $this->tagMapper->getTagByImapLabel($tag['imapLabel'], $user->getUID())->getId();
			}

			$newTags[$tag['id']] = $newTagId;
			$existingTagIds[$tag['imapLabel']] = $newTagId;
		}

		return $newTags;
	}

	/** IMAP labels are limited to what the tag form and mail servers produce: printable ASCII, no system flags. */
	private function isValidTag(array $tag): bool {
		return preg_match('/^[\x21-\x7e]{1,' . Tag::IMAP_LABEL_MAX_LENGTH . '}$/D', $tag['imapLabel']) === 1
			&& !str_starts_with($tag['imapLabel'], '\\')
			&& mb_strlen($tag['displayName']) <= Tag::DISPLAY_NAME_MAX_LENGTH
			&& mb_strlen($tag['color']) <= Tag::COLOR_MAX_LENGTH;
	}

	/**
	 * A user can only have one tag per IMAP label, so an already existing tag
	 * has to be reused instead of inserted again.
	 *
	 * @return array<string, int>
	 */
	private function getTagIdsByImapLabel(string $userId): array {
		$tagIds = [];

		foreach ($this->tagMapper->getAllTagsForUser($userId) as $tag) {
			$tagIds[$tag->getImapLabel()] = $tag->getId();
		}

		return $tagIds;
	}

	/**
	 * Validate that the parsed tags are a list.
	 *
	 * @throws UserMigrationException
	 */
	private function validateTags(mixed $tags): void {
		$tagsArrayIsValid = is_array($tags) && array_is_list($tags);
		if (!$tagsArrayIsValid) {
			throw new UserMigrationException('Invalid tags export structure');
		}
	}

	private function isWellFormedTag(mixed $tag): bool {
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

		return $idIsValid
			&& $displayNameIsValid
			&& $imapLabelIsValid
			&& $colorIsValid
			&& $isDefaultTagIsValid;
	}
}
