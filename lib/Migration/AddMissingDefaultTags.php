<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use function sprintf;

class AddMissingDefaultTags implements IRepairStep {
	public function __construct(
		private readonly \OCA\Mail\Db\MailAccountMapper $accountMapper,
		private readonly \OCA\Mail\Db\TagMapper $tagMapper
	) {
	}

	#[\Override]
	public function getName(): string {
		return 'Restore default tags that are missing';
	}

	#[\Override]
	public function run(IOutput $output): void {
		$output->info('Looking up default tags');
		$accounts = $this->accountMapper->getAllAccounts();

		$output->info(sprintf('%d accounts to check found', count($accounts)));
		$output->startProgress(count($accounts));
		foreach ($accounts as $account) {
			$this->tagMapper->createDefaultTags($account);
			$output->advance();
		}
		$output->finishProgress();
	}
}
