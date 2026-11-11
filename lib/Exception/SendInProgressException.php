<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Exception;

/**
 * Thrown when an outbox message is already being sent by another process.
 *
 * The send request of the web client and the OutboxWorkerJob can pick up the
 * same outbox message at the same time, see https://github.com/nextcloud/mail/issues/6194
 */
class SendInProgressException extends ServiceException {
}
