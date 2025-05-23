<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Model;

use OCA\Mail\Service\Classification\FeatureExtraction\IExtractor;
use Rubix\ML\Estimator;

class ClassifierPipeline {
	public function __construct(
		private readonly Estimator $estimator,
		private readonly IExtractor $extractor,
	) {
	}

	public function getEstimator(): Estimator {
		return $this->estimator;
	}

	public function getExtractor(): IExtractor {
		return $this->extractor;
	}
}
