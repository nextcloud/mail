<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration\Service;

use JsonException;
use OCA\Mail\Service\QuickActionsService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\DB\Exception;
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
		$output->writeln(
			$this->l10n->t('Exporting quick actions for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$quickActions = $this->quickActionsService->findAll($user->getUID());

		try {
			$exportDestination->addFileContents(self::QUICK_ACTIONS_FILE, json_encode($quickActions, JSON_THROW_ON_ERROR));
		} catch (JsonException|UserMigrationException $exception) {
			throw new UserMigrationException(
				"Failed to export quick actions for user {$user->getUID()}",
				previous: $exception
			);
		}
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
	public function importQuickActions(IUser $user, IImportSource $importSource, OutputInterface $output, array $accountAndMailboxMapping, array $tagMapping): void {
		$output->writeln(
			$this->l10n->t('Importing quick actions for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$quickActions = json_decode($importSource->getFileContents(self::QUICK_ACTIONS_FILE), true);
		$this->validateQuickActions($quickActions);

		foreach ($quickActions as $quickAction) {
			$createdQuickAction = $this->quickActionsService->create($quickAction['name'], $accountAndMailboxMapping['accounts'][$quickAction['accountId']]);

			foreach ($quickAction['actionSteps'] as $actionStep) {
				$this->quickActionsService->createActionStep($actionStep['name'], $actionStep['order'], $createdQuickAction->getId(), $tagMapping[$actionStep['tagId']] ?? null, $accountAndMailboxMapping['mailboxes'][$actionStep['mailboxId']] ?? null);
			}
		}
	}

	public function deleteAllQuickActions(IUser $user, OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t('Deleting all quick actions for user %s', [$user->getUID()]),
			OutputInterface::VERBOSITY_VERBOSE
		);

		$this->quickActionsService->deleteAll($user->getUID());
	}

	/**
	 * Validate the parsed quick actions to ensure they
	 * have the expected structure and types.
	 *
	 * @throws UserMigrationException
	 */
	private function validateQuickActions(mixed $quickActions): void {
		$quickActionsArrayIsValid = is_array($quickActions) && array_is_list($quickActions);
		if (!$quickActionsArrayIsValid) {
			throw new UserMigrationException('Invalid quick actions export structure');
		}

		foreach ($quickActions as $quickAction) {
			$quickActionArrayIsValid = is_array($quickAction);

			$idIsValid = $quickActionArrayIsValid
				&& array_key_exists('id', $quickAction)
				&& is_int($quickAction['id']);

			$nameIsValid = $quickActionArrayIsValid
				&& array_key_exists('name', $quickAction)
				&& is_string($quickAction['name']);

			$orderIsValid = $quickActionArrayIsValid
				&& array_key_exists('accountId', $quickAction)
				&& is_int($quickAction['accountId']);

			$actionStepsArrayIsValid = $quickActionArrayIsValid
				&& array_key_exists('actionSteps', $quickAction)
				&& is_array($quickAction['actionSteps'])
				&& array_is_list($quickAction['actionSteps'])
				&& $this->validateQuickSteps($quickAction['actionSteps']);

			if (
				!$idIsValid
				|| !$nameIsValid
				|| !$orderIsValid
				|| !$actionStepsArrayIsValid
			) {
				throw new UserMigrationException('Invalid quick action entry');
			}
		}
	}

	private function validateQuickSteps(mixed $quickSteps): bool {
		$quickStepsArrayIsValid = true;

		foreach ($quickSteps as $actionStep) {
			$actionStepArrayIsValid = is_array($actionStep);

			$idIsValid = $actionStepArrayIsValid
				&& array_key_exists('id', $actionStep)
				&& is_int($actionStep['id']);

			$nameIsValid = $actionStepArrayIsValid
				&& array_key_exists('name', $actionStep)
				&& is_string($actionStep['name']);

			$orderIsValid = $actionStepArrayIsValid
				&& array_key_exists('order', $actionStep)
				&& is_int($actionStep['order']);

			$actionIdIsValid = $actionStepArrayIsValid
				&& array_key_exists('actionId', $actionStep)
				&& is_int($actionStep['actionId']);

			$tagIdIsValid = $actionStepArrayIsValid
				&& array_key_exists('tagId', $actionStep)
				&& (is_int($actionStep['tagId']) || is_null($actionStep['tagId']));

			$mailboxIdIsValid = $actionStepArrayIsValid
				&& array_key_exists('mailboxId', $actionStep)
				&& (is_int($actionStep['mailboxId']) || is_null($actionStep['mailboxId']));

			$actionStepIsValid = $idIsValid
				&& $nameIsValid
				&& $orderIsValid
				&& $actionIdIsValid
				&& $tagIdIsValid
				&& $mailboxIdIsValid;

			$quickStepsArrayIsValid = $quickStepsArrayIsValid && $actionStepIsValid;
		}

		return $quickStepsArrayIsValid;
	}
}
