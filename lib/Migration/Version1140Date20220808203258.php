<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCA\Mail\Db\MessageMapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;
use function method_exists;

class Version1140Date20220808203258 extends SimpleMigrationStep {
	private LoggerInterface $logger;
	private MessageMapper $messageMapper;

	public function __construct(MessageMapper $messageMapper,
		LoggerInterface $logger) {
		$this->logger = $logger;
		$this->messageMapper = $messageMapper;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	#[\Override]
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		if (!method_exists($this->messageMapper, 'resetPreviewDataFlag')) {
			$this->logger->warning('Service method missing due to in process upgrade');
			return;
		}
		$this->messageMapper->resetPreviewDataFlag();
	}
}
