<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\IMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\IMAP\PortProbe;
use OCP\IConfig;
use OCP\Security\IRemoteHostValidator;
use Psr\Log\LoggerInterface;

class PortProbeTest extends TestCase {
	public function testRejectedHostIsNotContacted(): void {
		$config = $this->createMock(IConfig::class);
		$config->expects(self::never())->method('getSystemValue');
		$hostValidator = $this->createMock(IRemoteHostValidator::class);
		$hostValidator->expects(self::once())
			->method('isValid')
			->with('localhost')
			->willReturn(false);
		$probe = new PortProbe($config, $hostValidator, $this->createMock(LoggerInterface::class));

		self::assertFalse($probe->canConnect('localhost', 993));
	}
}
