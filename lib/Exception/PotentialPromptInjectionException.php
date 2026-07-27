<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

/**
 * Thrown when untrusted mail content contains prompt delimiters that would
 * attempt to break out of the surrounding prompt template.
 */
class PotentialPromptInjectionException extends ServiceException {
}
