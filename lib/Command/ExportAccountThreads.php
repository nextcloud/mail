<?php

declare(strict_types=1);

/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 */

namespace OCA\Mail\Command;

use OCA\Mail\Db\MessageMapper;
use OCA\Mail\IMAP\Threading\DatabaseMessage;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use function json_encode;

class ExportAccountThreads extends Command {
	private const ARGUMENT_ACCOUNT_ID = 'account-id';
	private const OPTION_REDACT = 'redact';

	private AccountService $accountService;
	private ISecureRandom $random;
	private IHasher $hasher;
	private MessageMapper $messageMapper;

	public function __construct(AccountService $service,
								ISecureRandom $random,
								IHasher $hasher,
								MessageMapper $messageMapper) {
		parent::__construct();

		$this->accountService = $service;
		$this->random = $random;
		$this->hasher = $hasher;
		$this->messageMapper = $messageMapper;
	}

	protected function configure(): void {
		$this->setName('mail:account:export-threads');
		$this->setDescription('Exports a user\'s account threads');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
		$this->addOption(self::OPTION_REDACT, 'r', InputOption::VALUE_NONE);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>Account $accountId does not exist</error>");
			return 1;
		}

		$threads = $this->messageMapper->findThreadingData($account);
		if ($input->getOption(self::OPTION_REDACT)) {
			$salt = $this->random->generate(32);
			$output->writeln(json_encode(
				array_map(function (DatabaseMessage $message) use ($salt) {
					return $message->redact(
						function (string $str) use ($salt) {
							return hash('md5', $str . $salt) . "@redacted";
						}
					);
				}, $threads),
				JSON_PRETTY_PRINT
			));
		} else {
			$output->writeln(
				json_encode(
					$threads,
					JSON_PRETTY_PRINT
				)
			);
		}

		return 0;
	}
}
