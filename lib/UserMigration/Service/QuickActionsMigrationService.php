<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\UserMigration\Service;

use OCA\Mail\Db\ActionStep;
use OCA\Mail\Db\ActionStepMapper;
use OCA\Mail\Service\QuickActionsService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\DB\Exception;
use OCP\IConfig;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Symfony\Component\Console\Output\OutputInterface;

class QuickActionsMigrationService {
	public const QUICK_ACTIONS_FILE = MailAccountMigrator::EXPORT_ROOT . '/quick_actions.json';

	public function __construct(
		private readonly QuickActionsService $quickActionsService,
		private readonly ActionStepMapper $actionStepMapper,
		private readonly IL10N $l10n,
	) {
	}

	/**
	 * Export all quick actions the user defined across
	 * their accounts.
	 *
	 * @param IUser $user
	 * @param IExportDestination $exportDestination
	 * @param OutputInterface $output
	 * @throws UserMigrationException
	 */
	public function exportQuickActions(IUser $user, IExportDestination $exportDestination, OutputInterface $output): void {
		$quickActions = $this->quickActionsService->findAll($user->getUID());
		$exportDestination->addFileContents(self::QUICK_ACTIONS_FILE, json_encode($quickActions));
	}

	/**
	 * Import all quick actions the user defined across
	 * their accounts.
	 *
	 * @throws UserMigrationException
	 * @throws Exception
	 * @throws JsonException
	 * @throws \OCA\Mail\Exception\ServiceException
	 */
	public function importQuickActions(IImportSource $importSource, array $accountMapping, array $mailboxMapping, array $tagMapping): void {
		$quickActions = json_decode($importSource->getFileContents(self::QUICK_ACTIONS_FILE), true, flags: JSON_THROW_ON_ERROR);

		foreach ($quickActions as $quickAction) {
			$createdQuickAction = $this->quickActionsService->create($quickAction['name'], $accountMapping[$quickAction['accountId']]);

			foreach ($quickAction['actionSteps'] as $actionStep) {
				$this->quickActionsService->createActionStep($actionStep['name'], $actionStep['order'], $createdQuickAction->getId(), $tagMapping[$actionStep['tagId']], $mailboxMapping[$actionStep['mailboxId']]);
			}
		}
	}

	public function deleteAllQuickActions(IUser $user, IImportSource $importSource): void {
		$this->quickActionsService->deleteAll($user->getUID());
	}}
