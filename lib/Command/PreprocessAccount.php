<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\PreprocessingService;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function memory_get_peak_usage;

class PreprocessAccount extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';

	private AccountService $accountService;
	private PreprocessingService $preprocessingService;
	private LoggerInterface $logger;

	public function __construct(AccountService $service,
		PreprocessingService $preprocessingService,
		LoggerInterface $logger) {
		parent::__construct();

		$this->accountService = $service;
		$this->preprocessingService = $preprocessingService;
		$this->logger = $logger;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:account:preprocess');
		$this->setDescription('Preprocess all mailboxes of an IMAP account');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>Account $accountId does not exist</error>");
			return 1;
		}

		$this->preprocessingService->process(4294967296, $account);

		$mbs = (int)(memory_get_peak_usage() / 1024 / 1024);
		$output->writeln('<info>' . $mbs . 'MB of memory used</info>');

		return 0;
	}
}
