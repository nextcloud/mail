<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Model;

use JsonSerializable;
use ReturnTypeWillChange;

class Classifier implements JsonSerializable {
	public const TYPE_IMPORTANCE = 'importance';

	private int $accountId;
	private string $type;
	private string $estimator;
	private int $persistenceVersion;
	private int $trainingSetSize;
	private int $validationSetSize;
	private float $recallImportant;
	private float $precisionImportant;
	private float $f1ScoreImportant;
	private int $duration;
	private int $createdAt;

	public function getAccountId(): int {
		return $this->accountId;
	}

	public function setAccountId(int $accountId): void {
		$this->accountId = $accountId;
	}

	public function getType(): string {
		return $this->type;
	}

	public function setType(string $type): void {
		$this->type = $type;
	}

	public function getEstimator(): string {
		return $this->estimator;
	}

	public function setEstimator(string $estimator): void {
		$this->estimator = $estimator;
	}

	public function getPersistenceVersion(): int {
		return $this->persistenceVersion;
	}

	public function setPersistenceVersion(int $persistenceVersion): void {
		$this->persistenceVersion = $persistenceVersion;
	}

	public function getTrainingSetSize(): int {
		return $this->trainingSetSize;
	}

	public function setTrainingSetSize(int $trainingSetSize): void {
		$this->trainingSetSize = $trainingSetSize;
	}

	public function getValidationSetSize(): int {
		return $this->validationSetSize;
	}

	public function setValidationSetSize(int $validationSetSize): void {
		$this->validationSetSize = $validationSetSize;
	}

	public function getRecallImportant(): float {
		return $this->recallImportant;
	}

	public function setRecallImportant(float $recallImportant): void {
		$this->recallImportant = $recallImportant;
	}

	public function getPrecisionImportant(): float {
		return $this->precisionImportant;
	}

	public function setPrecisionImportant(float $precisionImportant): void {
		$this->precisionImportant = $precisionImportant;
	}

	public function getF1ScoreImportant(): float {
		return $this->f1ScoreImportant;
	}

	public function setF1ScoreImportant(float $f1ScoreImportant): void {
		$this->f1ScoreImportant = $f1ScoreImportant;
	}

	public function getDuration(): int {
		return $this->duration;
	}

	public function setDuration(int $duration): void {
		$this->duration = $duration;
	}

	public function getCreatedAt(): int {
		return $this->createdAt;
	}

	public function setCreatedAt(int $createdAt): void {
		$this->createdAt = $createdAt;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'accountId' => $this->accountId,
			'type' => $this->type,
			'estimator' => $this->estimator,
			'persistenceVersion' => $this->persistenceVersion,
			'trainingSetSize' => $this->trainingSetSize,
			'validationSetSize' => $this->validationSetSize,
			'recallImportant' => $this->recallImportant,
			'precisionImportant' => $this->precisionImportant,
			'f1ScoreImportant' => $this->f1ScoreImportant,
			'duration' => $this->duration,
			'createdAt' => $this->createdAt,
		];
	}
}
