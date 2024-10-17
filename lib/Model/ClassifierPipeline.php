<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Model;

use Rubix\ML\Estimator;
use Rubix\ML\Transformers\Transformer;

class ClassifierPipeline {
	private Estimator $estimator;

	/** @var Transformer[] */
	private array $transformers;

	/**
	 * @param Estimator $estimator
	 * @param Transformer[] $transformers
	 */
	public function __construct(Estimator $estimator, array $transformers) {
		$this->estimator = $estimator;
		$this->transformers = $transformers;
	}

	public function getEstimator(): Estimator {
		return $this->estimator;
	}

	/**
	 * @return Transformer[]
	 */
	public function getTransformers(): array {
		return $this->transformers;
	}
}
