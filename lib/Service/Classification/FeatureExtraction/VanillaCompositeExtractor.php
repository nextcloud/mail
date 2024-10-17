<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service\Classification\FeatureExtraction;

class VanillaCompositeExtractor extends CompositeExtractor {
	public function __construct(ImportantMessagesExtractor $ex1,
		ReadMessagesExtractor $ex2,
		RepliedMessagesExtractor $ex3,
		SentMessagesExtractor $ex4) {
		parent::__construct([
			$ex1,
			$ex2,
			$ex3,
			$ex4,
		]);
	}
}
