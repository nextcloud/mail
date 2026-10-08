<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\JMAP;

use ChristophWurst\Nextcloud\Testing\TestCase;
use JmapClient\Responses\Quota\QuotaParameters;
use OCA\Mail\Account;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\JMAP\JmapMessageAdapter;
use OCA\Mail\JMAP\JmapMessageConnector;
use OCA\Mail\Service\JMAP\JmapOperationsService;
use OCP\EventDispatcher\IEventDispatcher;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class JmapMessageConnectorTest extends TestCase {
	private JmapOperationsService&MockObject $jmapOperationsService;
	private JmapMessageConnector $connector;
	private Account&MockObject $account;

	protected function setUp(): void {
		parent::setUp();

		$this->jmapOperationsService = $this->createMock(JmapOperationsService::class);
		$this->account = $this->createMock(Account::class);

		$this->connector = new JmapMessageConnector(
			$this->jmapOperationsService,
			$this->createMock(JmapMessageAdapter::class),
			$this->createMock(MessageMapper::class),
			$this->createMock(MailboxMapper::class),
			$this->createMock(IEventDispatcher::class),
			$this->createMock(LoggerInterface::class),
		);
	}

	private static function quota(array $properties): QuotaParameters {
		return new QuotaParameters(array_merge([
			'id' => 'quota',
			'resourceType' => 'octets',
			'used' => 100,
			'hardLimit' => 1000,
			'scope' => 'account',
			'name' => 'Storage',
			'types' => ['Email'],
		], $properties));
	}

	public function testGetQuotaWithoutQuotas(): void {
		$this->jmapOperationsService->expects(self::once())
			->method('connect')
			->with($this->account)
			->willReturn(true);
		$this->jmapOperationsService->expects(self::once())
			->method('quotaFetch')
			->willReturn([]);

		$quota = $this->connector->getQuota($this->account);

		self::assertNull($quota);
	}

	public function testGetQuotaReturnsEmailStorageQuota(): void {
		$this->jmapOperationsService->method('quotaFetch')
			->willReturn([self::quota(['used' => 2048, 'hardLimit' => 4096])]);

		$quota = $this->connector->getQuota($this->account);

		self::assertNotNull($quota);
		self::assertSame(2048, $quota->getUsage());
		self::assertSame(4096, $quota->getLimit());
	}

	public function testGetQuotaIgnoresUnrelatedQuotas(): void {
		$this->jmapOperationsService->method('quotaFetch')
			->willReturn([
				self::quota(['resourceType' => 'count', 'used' => 999, 'hardLimit' => 1000]),
				self::quota(['types' => ['Calendar'], 'used' => 999, 'hardLimit' => 1000]),
				self::quota(['types' => null]),
				self::quota(['hardLimit' => 0]),
				self::quota(['hardLimit' => null]),
				self::quota(['used' => null]),
			]);

		$quota = $this->connector->getQuota($this->account);

		self::assertNull($quota);
	}

	public function testGetQuotaSelectsQuotaClosestToLimit(): void {
		$this->jmapOperationsService->method('quotaFetch')
			->willReturn([
				self::quota(['scope' => 'account', 'used' => 100, 'hardLimit' => 1000]),
				self::quota(['scope' => 'domain', 'used' => 900, 'hardLimit' => 1000]),
				self::quota(['scope' => 'global', 'used' => 5000, 'hardLimit' => 100000]),
			]);

		$quota = $this->connector->getQuota($this->account);

		self::assertNotNull($quota);
		self::assertSame(900, $quota->getUsage());
		self::assertSame(1000, $quota->getLimit());
	}

	public function testGetQuotaPropagatesServiceException(): void {
		$this->jmapOperationsService->method('quotaFetch')
			->willThrowException(new ServiceException('request failed'));

		$this->expectException(ServiceException::class);

		$this->connector->getQuota($this->account);
	}
}
