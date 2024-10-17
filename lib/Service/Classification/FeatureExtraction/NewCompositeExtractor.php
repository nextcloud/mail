<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
