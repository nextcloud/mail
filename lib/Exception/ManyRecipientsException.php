<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

class ManyRecipientsException extends ClientException {
	public function __construct() {
		parent::__construct('Many recipients in TO and/or CC');
	}
}
