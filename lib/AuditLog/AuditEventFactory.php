<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\AuditLog;

use OCP\Log\Audit\CriticalActionPerformedEvent;

final class AuditEventFactory {
	/**
	 * The operation is only passed on to servers whose CriticalActionPerformedEvent supports it
	 *
	 * @param array<string, mixed> $parameters values for the placeholders of $message, in order, optionally followed by more context
	 */
	public static function create(Operation $operation, string $message, array $parameters = []): CriticalActionPerformedEvent {
		$arguments = [$message, $parameters, false];
		if (method_exists(CriticalActionPerformedEvent::class, 'getOperation')) {
			$arguments[] = $operation->value;
		}
		return new CriticalActionPerformedEvent(...$arguments);
	}
}
