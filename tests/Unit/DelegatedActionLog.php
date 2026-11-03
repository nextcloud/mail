<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit;

use Closure;
use OCA\Mail\AuditLog\Operation;
use PHPUnit\Framework\Assert;

final class DelegatedActionLog {
	/**
	 * Callback for a mocked DelegationService::logDelegatedAction that asserts the
	 * message the audit log renders from the format string and parameters
	 */
	public static function rendersAs(string $expected): Closure {
		return static function (string $currentUserId, string $effectiveUserId, Operation $operation, string $logMessage, array $parameters = []) use ($expected): void {
			Assert::assertSame(
				$expected,
				vsprintf($logMessage, array_values(['delegate' => $currentUserId] + $parameters + ['owner' => $effectiveUserId])),
			);
		};
	}
}
