<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\Service;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\Service\GovernanceLabelService;
use OCP\App\IAppManager;
use OCP\IConfig;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Container\ContainerInterface;

class GovernanceLabelServiceTest extends TestCase {
	private IAppManager&MockObject $appManager;
	private ContainerInterface&MockObject $container;
	private IConfig&MockObject $config;
	private GovernanceLabelService $service;

	protected function setUp(): void {
		parent::setUp();

		$this->appManager = $this->createMock(IAppManager::class);
		$this->container = $this->createMock(ContainerInterface::class);
		$this->config = $this->createMock(IConfig::class);
		$this->service = new GovernanceLabelService(
			$this->appManager,
			$this->container,
			$this->config,
		);
	}

	public function testGetLabelsWhenGovernanceIsNotEnabled(): void {
		$this->appManager->method('isEnabledForAnyone')
			->with('governance')
			->willReturn(false);
		$this->container->expects(self::never())
			->method('get');

		$labels = $this->service->getLabels();

		self::assertSame([], $labels);
	}

	public function testGetLabelWhenGovernanceIsNotEnabled(): void {
		$this->appManager->method('isEnabledForAnyone')
			->with('governance')
			->willReturn(false);

		$label = $this->service->getLabel('123');

		self::assertNull($label);
	}

	public function testBuildHeaderValue(): void {
		$this->config->method('getSystemValueString')
			->with('instanceid')
			->willReturn('abc123');

		$value = $this->service->buildHeaderValue('42');

		self::assertSame('id=42; origin=abc123', $value);
	}

	public function testResolveHeaderLabelId(): void {
		$this->config->method('getSystemValueString')
			->with('instanceid')
			->willReturn('abc123');
		$service = $this->getMockBuilder(GovernanceLabelService::class)
			->setConstructorArgs([$this->appManager, $this->container, $this->config])
			->onlyMethods(['getLabel'])
			->getMock();
		$service->method('getLabel')
			->with('42')
			->willReturn(['id' => '42']);

		$labelId = $service->resolveHeaderLabelId('id=42; origin=abc123');

		self::assertSame('42', $labelId);
	}

	public function testResolveHeaderLabelIdWithForeignOrigin(): void {
		$this->config->method('getSystemValueString')
			->with('instanceid')
			->willReturn('abc123');

		$labelId = $this->service->resolveHeaderLabelId('id=42; origin=otherinstance');

		self::assertNull($labelId);
	}

	public function testResolveHeaderLabelIdWithUnknownLabel(): void {
		$this->config->method('getSystemValueString')
			->with('instanceid')
			->willReturn('abc123');
		$this->appManager->method('isEnabledForAnyone')
			->with('governance')
			->willReturn(false);

		$labelId = $this->service->resolveHeaderLabelId('id=42; origin=abc123');

		self::assertNull($labelId);
	}

	public function testResolveHeaderLabelIdWithGarbage(): void {
		$labelId = $this->service->resolveHeaderLabelId('not a valid header');

		self::assertNull($labelId);
	}

	public function testResolveHeaderLabelIdWithNull(): void {
		$labelId = $this->service->resolveHeaderLabelId(null);

		self::assertNull($labelId);
	}
}
