<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\JMAP\Exception;

use OCA\Mail\Exception\ServiceException;

/**
 * Thrown when the JMAP server reports that a requested method is unknown.
 */
class JmapUnknownMethod extends ServiceException {
}
