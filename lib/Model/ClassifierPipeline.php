<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
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
