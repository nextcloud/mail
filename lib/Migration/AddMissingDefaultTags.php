<?php

declare(strict_types=1);

/**
 * @copyright 2021 Anna Larch <anna@nextcloud.com>
 *
 * @author 2021 Anna Larch <anna@nextcloud.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @link https://github.com/nextcloud/mail/issues/25
 * @link https://github.com/nextcloud/mail/issues/4780
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

	public function getName() {
		return 'Restore default tags that are missing';
	}

	/**
	 * @return void
	 */
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
