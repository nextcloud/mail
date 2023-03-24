<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service\Classification\FeatureExtraction;

class NewCompositeExtractor extends CompositeExtractor {
	private SubjectExtractor $subjectExtractor;

	public function __construct(VanillaCompositeExtractor $ex1,
								SubjectExtractor $ex2) {
		parent::__construct([$ex1, $ex2]);
		$this->subjectExtractor = $ex2;
	}

	public function getSubjectExtractor(): SubjectExtractor {
		return $this->subjectExtractor;
	}
}
