<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\UserMigration\Service;

use JsonException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\QuickActionsService;
use OCA\Mail\UserMigration\MailAccountMigrator;
use OCP\AppFramework\Db\TTransactional;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUser;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\UserMigrationException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Output\OutputInterface;

class QuickActionsMigrationService {
	use TTransactional;

	public const QUICK_ACTIONS_FILE = MailAccountMigrator::EXPORT_ROOT . '/quick_actions.json';

	public const ESTIMATED_QUICK_ACTION_SIZE = 150;

	public const ESTIMATED_ACTION_STEP_SIZE = 120;

	public function __construct(
		private readonly QuickActionsService $quickActionsService,
		private readonly IL10N $l10n,
		private readonly IDBConnection $connection,
		private readonly LoggerInterface $logger,
	) {
	}

	public function getEstimatedExportSize(IUser $user): int {
		$bytes = 0;

		foreach ($this->quickActionsService->findAll($user->getUID()) as $quickAction) {
			$bytes += self::ESTIMATED_QUICK_ACTION_SIZE
				+ count($quickAction->getActionSteps()) * self::ESTIMATED_ACTION_STEP_SIZE;
		}

		return $bytes;
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
	public function exportQuickActions(IUser $user,
		IExportDestination $exportDestination,
		OutputInterface $output): void {
		$output->writeln(
			$this->l10n->t(
				'Exporting quick actions for user %s',
				[$user->getUID()]
			), OutputInterface::VERBOSITY_VERBOSE
		);

		$quickActions = $this->quickActionsService->findAll($user->getUID());

		try {
			$exportDestination->addFileContents(self::QUICK_ACTIONS_FILE,
				json_encode($quickActions, JSON_THROW_ON_ERROR));
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
	 * @throws \OCA\Mail\Exception\ServiceException
	 */
	public function importQuickActions(IUser $user,
		IImportSource $importSource,
		OutputInterface $output,
		array $accountMapping,
		array $mailboxMapping,
		array $tagMapping): void {
		$output->writeln(
			$this->l10n->t(
				'Importing quick actions for user %s',
				[$user->getUID()]
			), OutputInterface::VERBOSITY_VERBOSE
		);

		if (count($accountMapping) === 0) {
			$output->writeln(
				$this->l10n->t(
					'Missing account mapping when importing quick actions for user %s. Continue...',
					[$user->getUID()]
				), OutputInterface::VERBOSITY_VERBOSE
			);

			return;
		}

		try {
			$quickActionsFileContent = $importSource->getFileContents(self::QUICK_ACTIONS_FILE);
		} catch (UserMigrationException) {
			$output->writeln(
				$this->l10n->t(
					'Quick actions for user %s not found. Continue...',
					[$user->getUID()]
				), OutputInterface::VERBOSITY_VERBOSE
			);

			return;
		}

		try {
			$quickActions = json_decode($quickActionsFileContent, true, flags: JSON_THROW_ON_ERROR);
			$this->validateQuickActions($quickActions);
		} catch (JsonException|UserMigrationException) {
			$output->writeln(
				$this->l10n->t(
					'Quick actions configuration for user %s is invalid and will be skipped. Continue...',
					[$user->getUID()]
				), OutputInterface::VERBOSITY_VERBOSE
			);

			return;
		}

		foreach ($quickActions as $quickAction) {
			$oldAccountId = $quickAction['accountId'];

			if (!array_key_exists($oldAccountId, $accountMapping)) {
				$output->writeln(
					$this->l10n->t(
						'Skipping quick action %s because account %s was not imported. Continue...',
						[$quickAction['name'], (string)$oldAccountId]
					), OutputInterface::VERBOSITY_VERBOSE
				);

				continue;
			}
			$actionSteps = $this->mapActionSteps($quickAction['actionSteps'], $mailboxMapping, $tagMapping);
			if ($actionSteps === null) {
				$output->writeln(
					$this->l10n->t(
						'Skipping quick action %s because it references a tag or mailbox that was not imported. Continue...',
						[$quickAction['name']]
					), OutputInterface::VERBOSITY_VERBOSE
				);

				continue;
			}

			try {
				$this->atomic(function () use ($quickAction, $accountMapping, $oldAccountId, $actionSteps): void {
					$createdQuickAction = $this->quickActionsService->create($quickAction['name'],
						$accountMapping[$oldAccountId]);

					foreach ($actionSteps as $actionStep) {
						$this->quickActionsService->createActionStep($actionStep['name'], $actionStep['order'],
							$createdQuickAction->getId(), $actionStep['tagId'], $actionStep['mailboxId']);
					}
				}, $this->connection);
			} catch (ServiceException $exception) {
				$this->logger->warning('Skipping a quick action that could not be recreated', [
					'exception' => $exception,
				]);
				$output->writeln(
					$this->l10n->t(
						'Skipping quick action %s because it could not be recreated. Continue...',
						[$quickAction['name']]
					), OutputInterface::VERBOSITY_VERBOSE
				);
			}
		}
	}

	/**
	 * Maps the exported tag and mailbox ids of every action step to the ids they
	 * got on import. Yields null as soon as one step cannot be recreated: action
	 * step orders have to be gapless, so a quick action missing a step in the
	 * middle is rejected by the service anyway.
	 *
	 * @param array<int, int> $mailboxMapping
	 * @param array<int, int> $tagMapping
	 * @return list<array{name: string, order: int, tagId: int|null, mailboxId: int|null}>|null
	 */
	private function mapActionSteps(array $actionSteps, array $mailboxMapping, array $tagMapping): ?array {
		$mappedActionSteps = [];

		foreach ($actionSteps as $actionStep) {
			// A newer instance may export step types this one cannot create.
			if (!in_array($actionStep['name'], QuickActionsService::AVAILABLE_ACTION_STEPS, true)) {
				return null;
			}
			if ($actionStep['tagId'] !== null && !isset($tagMapping[$actionStep['tagId']])) {
				return null;
			}
			if ($actionStep['mailboxId'] !== null && !isset($mailboxMapping[$actionStep['mailboxId']])) {
				return null;
			}

			$mappedActionSteps[] = [
				'name' => $actionStep['name'],
				'order' => $actionStep['order'],
				'tagId' => $tagMapping[$actionStep['tagId']] ?? null,
				'mailboxId' => $mailboxMapping[$actionStep['mailboxId']] ?? null,
			];
		}

		usort($mappedActionSteps, static fn (array $a, array $b): int => $a['order'] <=> $b['order']);

		return $mappedActionSteps;
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

			$accountIdIsValid = $quickActionArrayIsValid
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
				|| !$accountIdIsValid
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
