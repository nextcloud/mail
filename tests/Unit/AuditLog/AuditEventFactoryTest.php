<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\AuditLog;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\AuditLog\AuditEventFactory;
use OCA\Mail\AuditLog\Operation;

class AuditEventFactoryTest extends TestCase {
	public function testCreate(): void {
		$event = AuditEventFactory::create(Operation::AccountDeleted, 'Mail account %s of "%s" was deleted', [
			'accountId' => 1,
			'owner' => 'alice',
		]);

		$this->assertSame('Mail account %s of "%s" was deleted', $event->getLogMessage());
		$this->assertSame(['accountId' => 1, 'owner' => 'alice'], $event->getParameters());
		$this->assertFalse($event->getObfuscateParameters());
		$this->assertTrue(AuditEvents::hasOperation($event, 'mail.account.deleted'));
	}
}
