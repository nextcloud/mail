<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Governance\Db\LabelScope;
use OCA\Governance\Model\BaseLabelModel;
use OCA\Governance\Service\ResolverService;
use OCP\App\IAppManager;
use OCP\IConfig;
use Psr\Container\ContainerInterface;
use function class_exists;

class GovernanceLabelService {
	public const HEADER = 'X-NC-Governance-Label';

	/** @var array<int, array<string, array>> */
	private array $labelsCache = [];

	public function __construct(
		private IAppManager $appManager,
		private ContainerInterface $container,
		private IConfig $config,
	) {
	}

	public function isGovernanceAvailable(): bool {
		return $this->appManager->isEnabledForAnyone('governance')
			&& class_exists(ResolverService::class);
	}

	/**
	 * List all governance labels with a MAILS scope (or all scopes), with their metadata.
	 *
	 * @return array<string, array{id: string, type: string, name: string, priority: int, description: string, color: string, scopes: list<string>}> indexed by label ID
	 */
	public function getLabels(bool $allScopes = false): array {
		if (!$this->isGovernanceAvailable()) {
			return [];
		}

		if (isset($this->labelsCache[(int)$allScopes])) {
			return $this->labelsCache[(int)$allScopes];
		}

		/** @var ResolverService $resolver */
		$resolver = $this->container->get(ResolverService::class);
		// Index by label ID explicitly: governance returns renumbered integer
		// keys because PHP casts the numeric snowflake IDs to int array keys
		$labels = [];
		foreach ($allScopes ? LabelScope::cases() : [LabelScope::MAILS] as $scope) {
			foreach ($resolver->getAllLabelsForScope($scope) as $label) {
				$labels[$label->id] = $this->serializeLabel($label);
			}
		}

		return $this->labelsCache[(int)$allScopes] = $labels;
	}

	/**
	 * Build the mail header value marking a message with a governance label.
	 */
	public function buildHeaderValue(string $labelId): string {
		return sprintf(
			'id=%s; origin=%s',
			$labelId,
			$this->config->getSystemValueString('instanceid'),
		);
	}

	/**
	 * Parse a X-NC-Governance-Label header value and return the label ID if
	 * it originates from this instance and the label still exists.
	 */
	public function resolveHeaderLabelId(?string $headerValue): ?string {
		if ($headerValue === null || $headerValue === '') {
			return null;
		}

		if (preg_match('/^\s*id=([^;\s]+)\s*;\s*origin=(\S+)\s*$/', $headerValue, $matches) !== 1) {
			return null;
		}
		[, $labelId, $origin] = $matches;

		if ($origin !== $this->config->getSystemValueString('instanceid')) {
			return null;
		}

		return $this->getLabel($labelId, true) !== null ? $labelId : null;
	}

	/**
	 * @return array{id: string, type: string, name: string, priority: int, description: string, color: string, scopes: list<string>}|null
	 */
	public function getLabel(string $id, bool $allScopes = false): ?array {
		return $this->getLabels($allScopes)[$id] ?? null;
	}

	/**
	 * @return array{id: string, type: string, name: string, priority: int, description: string, color: string, scopes: list<string>}
	 */
	private function serializeLabel(BaseLabelModel $label): array {
		return [
			'id' => $label->id,
			'type' => $label->getType(),
			'name' => $label->displayName,
			'priority' => $label->priority,
			'description' => $label->userDescription,
			'color' => $label->color,
			'scopes' => array_map(static fn ($scope) => $scope->getValue(), $label->scopes),
		];
	}
}
