<?php

declare(strict_types=1);

/**
 * @author Anna Larch <anna.larch@nextcloud.com>
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

use OCA\Mail\Db\MailAccountMapper;
use OCA\Mail\Db\TagMapper;
use Psr\Log\LoggerInterface;
use OCP\AppFramework\Db\DoesNotExistException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddMissingTags extends Command {
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
			if (empty($accounts)) {
				$output->writeLn('<error>No accounts exist</error>');
				return 1;
			}
		} else {
			try {
				$account = $this->mapper->findById($accountId);
				$accounts = [$account];
				$output->writeLn("<info>Found account with email: " . $account->getEmail() . "</info>");
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
