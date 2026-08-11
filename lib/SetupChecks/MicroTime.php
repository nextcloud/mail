<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\SetupChecks;

class MicroTime {

	public function getNumeric(): float {
		return (float)microtime(true);
	}

}
