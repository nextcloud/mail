<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Command;

use OCA\Mail\Service\CleanupService;
use OCA\Mail\Support\ConsoleLoggerDecorator;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class CleanUp extends Command {
	public function __construct(
		private readonly CleanupService $cleanupService,
		private readonly LoggerInterface $logger
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:clean-up');
		$this->setDescription('clean up all orphaned data');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$logger = new ConsoleLoggerDecorator($this->logger, $output);

		$this->cleanupService->cleanUp($logger);

		return 0;
	}
}
