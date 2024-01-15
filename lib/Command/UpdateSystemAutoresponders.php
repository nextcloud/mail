<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2024 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Command;

use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Service\OutOfOfficeService;
use OCP\IUserManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class UpdateSystemAutoresponders extends Command {
	public function __construct(
		private MailAccountMapper $mailAccountMapper,
		private IUserManager $userManager,
		private OutOfOfficeService $outOfOfficeService,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:repair:system-autoresponders');
		$this->setDescription('Update sieve scripts of all accounts that follow the system out-of-office period');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		foreach ($this->mailAccountMapper->findAllWhereOooFollowsSystem() as $mailAccount) {
			$accountId = $mailAccount->getId();
			$userId = $mailAccount->getUserId();
			$output->writeln("<info>Updating account $accountId of user $userId</info>");

			$userId = $mailAccount->getUserId();
			$user = $this->userManager->get($userId);
			if ($user === null) {
				$output->writeln("<comment>User $userId does not exist. Skipping ...</comment>");
				continue;
			}

			$state = $this->outOfOfficeService->updateFromSystem($mailAccount, $user);
			if ($state === null) {
				$output->writeln(
					"Disabled autoresponder of account $accountId",
					OutputInterface::VERBOSITY_VERBOSE,
				);
			} else {
				$output->writeln(
					"Updated autoresponder of account $accountId",
					OutputInterface::VERBOSITY_VERBOSE,
				);
			}
		}

		return 0;
	}
}
