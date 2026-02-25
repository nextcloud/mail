<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\UserMigration\Service;

use OCA\Mail\Db\Tag;
use OCA\Mail\Db\TagMapper;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\DB\Exception;
use OCP\IConfig;
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
		$tags = $this->tagMapper->getAllTagsForUser($user->getUID());
		$exportDestination->addFileContents(self::TAGS_FILE, json_encode($tags));
	}

	/**
	 * Import all tags the user used on export.
	 *
	 * @param IUser $user
	 * @param IImportSource $importSource
	 * @return array
	 * @throws Exception
	 * @throws JsonException
	 * @throws UserMigrationException
	 */
	public function importTags(IUser $user, IImportSource $importSource): array {
		$tags = json_decode($importSource->getFileContents(self::TAGS_FILE), true, flags: JSON_THROW_ON_ERROR);
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

	public function deleteAllTags(IUser $user, IImportSource $importSource): void {
		$this->tagMapper->deleteAll($user->getUID());
	}
}
