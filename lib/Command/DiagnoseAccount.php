<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Command;

use Horde_Imap_Client;
use Horde_Imap_Client_Exception;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Socket;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use function array_reduce;
use function json_decode;
use function sort;

class DiagnoseAccount extends Command {
	private const ARGUMENT_ACCOUNT_ID = 'account-id';

	private AccountService $accountService;
	private IMAPClientFactory $clientFactory;
	private LoggerInterface $logger;

	public function __construct(AccountService $service,
								IMAPClientFactory $clientFactory,
								LoggerInterface $logger) {
		parent::__construct();

		$this->accountService = $service;
		$this->clientFactory = $clientFactory;
		$this->logger = $logger;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:account:diagnose');
		$this->setDescription('Diagnose a user\'s IMAP connection');
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

		if ($account->getMailAccount()->getInboundPassword() === null) {
			$output->writeln('<error>No IMAP passwort set. The user might have to log into their account to set it.</error>');
		}
		$imapClient = $this->clientFactory->getClient($account);
		try {
			$this->printCapabilitiesStats($output, $imapClient);
			$this->printMailboxesMessagesStats($output, $imapClient);
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->error('Could not get account statistics: ' . $e, [
				'exception' => $e,
			]);
			$output->writeln("<error>Horde error occurred: " . $e->getMessage() . ". See nextcloud.log for more details.</error>");
			return 2;
		} finally {
			$imapClient->logout();
		}

		return 0;
	}

	/**
	 * @param OutputInterface $output
	 * @param Horde_Imap_Client_Socket $imapClient
	 *
	 * @throws Horde_Imap_Client_Exception
	 */
	private function printCapabilitiesStats(OutputInterface $output,
											Horde_Imap_Client_Socket $imapClient): void {
		$output->writeln("IMAP capabilities:");
		// Once logged in more capabilities are advertised
		$imapClient->login();
		$capabilities = array_keys(
			json_decode(
				$imapClient->capability->serialize(),
				true
			)
		);
		sort($capabilities);
		foreach ($capabilities as $capability) {
			$output->writeln("- $capability");
		}
		$output->writeln("");
	}

	/**
	 * @param OutputInterface $output
	 * @param Horde_Imap_Client_Socket $imapClient
	 *
	 * @throws Horde_Imap_Client_Exception
	 */
	protected function printMailboxesMessagesStats(OutputInterface $output,
												   Horde_Imap_Client_Socket $imapClient): void {
		$mailboxes = $imapClient->listMailboxes('*', Horde_Imap_Client::MBOX_ALL, [
			'flat' => true,
		]);
		$messages = array_reduce($mailboxes, static function (int $c, Horde_Imap_Client_Mailbox $mb) use ($imapClient) {
			$status = $imapClient->status($mb, Horde_Imap_Client::STATUS_MESSAGES);
			return $c + $status['messages'];
		}, 0);
		$output->writeln("Account has " . $messages . " messages in " . count($mailboxes) . " mailboxes");
	}
}
