<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\TagMapper;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class AddMissingTags extends Command {
	public const ARGUMENT_ACCOUNT_ID = 'account-id';

	private LoggerInterface $logger;
	private TagMapper $tagMapper;
	private MailAccountMapper $mapper;

	public function __construct(MailAccountMapper $mapper,
		TagMapper $tagMapper,
		LoggerInterface $logger) {
		parent::__construct();

		$this->mapper = $mapper;
		$this->tagMapper = $tagMapper;
		$this->logger = $logger;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:repair:tags');
		$this->setDescription('Create default tags for account. If no account ID given, all tag entries will be repaired');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::OPTIONAL);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);

		if ($accountId === 0) {
			$accounts = $this->mapper->getAllAccounts();
			$output->writeln(sprintf('%d accounts to check found', count($accounts)));
			if ($accounts === []) {
				$output->writeLn('<error>No accounts exist</error>');
				return 1;
			}
		} else {
			try {
				$account = $this->mapper->findById($accountId);
				$accounts = [$account];
				$output->writeLn('<info>Found account with email: ' . $account->getEmail() . '</info>');
			} catch (DoesNotExistException $e) {
				$output->writeLn('<info>This account does not exist</info>');
			}
		}

		$progress = new ProgressBar($output);
		foreach ($accounts as $account) {
			$this->tagMapper->createDefaultTags($account);
			$progress->advance();
		}

		$progress->finish();
		$output->writeln('');
		$output->writeln('Patched default tags for ' . count($accounts));
		return 0;
	}
}
