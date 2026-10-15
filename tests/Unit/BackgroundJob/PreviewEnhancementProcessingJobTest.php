<?php

declare(strict_types=1);

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Tests\Unit\BackgroundJob;

use ChristophWurst\Nextcloud\Testing\TestCase;
use OCA\Mail\BackgroundJob\PreviewEnhancementProcessingJob;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\PreprocessingService;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJobList;
use OCP\IUserManager;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

class PreviewEnhancementProcessingJobTest extends TestCase {

	private ITimeFactory&MockObject $time;
	private IUserManager&MockObject $userManager;
	private AccountService&MockObject $accountService;
	private PreprocessingService&MockObject $preprocessingService;
	private LoggerInterface&MockObject $logger;
	private IJobList&MockObject $jobList;
	private PreviewEnhancementProcessingJob $job;

	protected function setUp(): void {
		parent::setUp();

		$this->time = $this->createMock(ITimeFactory::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->accountService = $this->createMock(AccountService::class);
		$this->preprocessingService = $this->createMock(PreprocessingService::class);
		$this->logger = $this->createMock(LoggerInterface::class);
		$this->jobList = $this->createMock(IJobList::class);

		$this->job = new PreviewEnhancementProcessingJob(
			$this->time,
			$this->userManager,
			$this->accountService,
			$this->preprocessingService,
			$this->logger,
			$this->jobList,
		);
	}

	public function testRunsDailyAndDeferrableUnderLoad(): void {
		self::assertSame(24 * 3600, $this->job->getInterval());
		self::assertFalse($this->job->isTimeSensitive());
	}
}
