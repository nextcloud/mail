<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Model;

use OCA\Mail\Service\Classification\FeatureExtraction\IExtractor;
use Rubix\ML\Estimator;
use Rubix\ML\Transformers\Transformer;

class ClassifierPipeline {
	/**
	 * @param Transformer[] $transformers
	 */
	public function __construct(
		private Estimator $estimator,
		private IExtractor $extractor,
		private array $transformers,
	) {
	}

	public function getEstimator(): Estimator {
		return $this->estimator;
	}

	public function getExtractor(): IExtractor {
		return $this->extractor;
	}

	/**
	 * @return Transformer[]
	 */
	public function getTransformers(): array {
		return $this->transformers;
	}
}
