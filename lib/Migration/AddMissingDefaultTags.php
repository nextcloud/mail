<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\TagMapper;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use function sprintf;

class AddMissingDefaultTags implements IRepairStep {
	/** @var TagMapper */
	private $tagMapper;

	/** @var MailAccountMapper */
	private $accountMapper;


	public function __construct(MailAccountMapper $accountMapper,
		TagMapper $tagMapper) {
		$this->accountMapper = $accountMapper;
		$this->tagMapper = $tagMapper;
	}

	#[\Override]
	public function getName() {
		return 'Restore default tags that are missing';
	}

	/**
	 * @return void
	 */
	#[\Override]
	public function run(IOutput $output) {
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
