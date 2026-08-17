<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Listener;

use ChristophWurst\Nextcloud\Testing\TestCase;
use Doctrine\DBAL\Platforms\AbstractPlatform;
use OCA\Mail\Listener\OptionalIndicesListener;
use OCP\DB\Events\AddMissingIndicesEvent;
use OCP\EventDispatcher\Event;
use OCP\IConfig;
use OCP\IDBConnection;
use PHPUnit\Framework\MockObject\MockObject;

class OptionalIndicesListenerTest extends TestCase {

	private IConfig&MockObject $config;
	private IDBConnection&MockObject $connection;
	private OptionalIndicesListener $listener;

	protected function setUp(): void {
		parent::setUp();

		$this->config = $this->createMock(IConfig::class);
		$this->connection = $this->createMock(IDBConnection::class);

		$this->listener = new OptionalIndicesListener($this->config, $this->connection);
	}

	public function testHandleIgnoresUnrelatedEvents(): void {
		$this->config->expects(self::never())->method('getSystemValue');
		$event = $this->createMock(Event::class);

		$this->listener->handle($event);
	}

	public function testHandleRegistersOptionalIndices(): void {
		$this->config->method('getSystemValue')
			->with('version', '0.0.0')
			->willReturn('34.0.0');
		$this->connection->method('getDatabasePlatform')
			->willReturn($this->createMock(AbstractPlatform::class));
		$event = new AddMissingIndicesEvent();

		$this->listener->handle($event);

		$indexNames = array_column($event->getMissingIndices(), 'indexName');
		self::assertContains('mail_msg_mb_del_snt_idx', $indexNames);
	}
}
