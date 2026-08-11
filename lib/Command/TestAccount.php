<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Command;

use Horde_Imap_Client_Exception;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

final class TestAccount extends Command {
	private const ARGUMENT_ACCOUNT_ID = 'account-id';

	public function __construct(
		private AccountService $accountService,
		private ProtocolFactory $protocolFactory,
		private LoggerInterface $logger,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this->setName('mail:account:test');
		$this->setAliases(['mail:account:diagnose']);
		$this->setDescription('Test the connection for a mail account (IMAP or JMAP)');
		$this->addArgument(self::ARGUMENT_ACCOUNT_ID, InputArgument::REQUIRED, 'The ID of the mail account');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$accountId = (int)$input->getArgument(self::ARGUMENT_ACCOUNT_ID);

		try {
			$account = $this->accountService->findById($accountId);
		} catch (DoesNotExistException $e) {
			$output->writeln("<error>Account $accountId does not exist</error>");
			return 1;
		}

		$protocol = $account->getMailAccount()->getProtocol();
		$output->writeln("Account $accountId uses protocol: <info>$protocol</info>");

		return match ($protocol) {
			MailAccount::PROTOCOL_IMAP => $this->testImap($account, $output),
			MailAccount::PROTOCOL_JMAP => $this->testJmap($account, $output),
			default => $this->unsupportedProtocol($protocol, $output),
		};
	}

	private function testImap(\OCA\Mail\Account $account, OutputInterface $output): int {
		$output->writeln('Testing IMAP connection...');

		$mailAccount = $account->getMailAccount();
		$sslMode = $mailAccount->getInboundSslMode();
		$scheme = ($sslMode === 'none') ? 'imap' : 'imaps';
		$host = $mailAccount->getInboundHost() ?? '(not set)';
		$port = $mailAccount->getInboundPort();
		$output->writeln('Server: <info>' . $scheme . '://' . $host . ':' . $port . '</info>');

		if ($account->getMailAccount()->getInboundPassword() === null) {
			$output->writeln('<error>No IMAP password set. The user may need to log in to set it.</error>');
			return 1;
		}

		try {
			$imapClient = $this->protocolFactory->imapClient($account);
		} catch (\Exception $e) {
			$output->writeln('<error>Could not create IMAP client: ' . $e->getMessage() . '</error>');
			return 2;
		}

		try {
			$imapClient->login();
			$output->writeln('<info>Login successful</info>');

			$capabilities = array_keys(
				json_decode($imapClient->capability->serialize(), true)
			);
			sort($capabilities);
			$output->writeln('Capabilities: <info>' . implode(', ', $capabilities) . '</info>');

			$output->writeln('<info>IMAP connection test passed</info>');
			return 0;
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->error('IMAP connection test failed for account ' . $account->getId() . ': ' . $e->getMessage(), [
				'exception' => $e,
			]);
			$output->writeln('<error>IMAP connection test failed: ' . $e->getMessage() . '</error>');
			return 2;
		} finally {
			$imapClient->logout();
		}
	}

	private function testJmap(\OCA\Mail\Account $account, OutputInterface $output): int {
		$output->writeln('Testing JMAP connection...');

		$mailAccount = $account->getMailAccount();
		$sslMode = $mailAccount->getInboundSslMode();
		$scheme = ($sslMode === 'none') ? 'http' : 'https';
		$host = $mailAccount->getInboundHost() ?? '(not set)';
		$port = $mailAccount->getInboundPort();
		$path = $mailAccount->getPath() ?? '/.well-known/jmap';
		$output->writeln('Server: <info>' . $scheme . '://' . $host . ':' . $port . $path . '</info>');

		try {
			$client = $this->protocolFactory->jmapClient($account);
			$session = $client->connect();
		} catch (\Exception $e) {
			$this->logger->error('JMAP connection test failed for account ' . $account->getId() . ': ' . $e->getMessage(), [
				'exception' => $e,
			]);
			$output->writeln('<error>JMAP connection test failed: ' . $e->getMessage() . '</error>');
			return 2;
		}

		if (!$client->sessionStatus()) {
			$output->writeln('<error>JMAP session discovery failed. Check the server and credentials.</error>');
			return 2;
		}

		$output->writeln('<info>JMAP session established</info>');
		$output->writeln('Username: <info>' . $session->username() . '</info>');
		$output->writeln('API URL: <info>' . $session->commandUrl() . '</info>');
		$output->writeln('State: <info>' . $session->state() . '</info>');

		$capabilities = [];
		foreach ($session->capabilities() as $capability) {
			$capabilities[] = $capability->id();
		}
		sort($capabilities);
		$output->writeln('Capabilities: <info>' . implode(', ', $capabilities) . '</info>');

		$output->writeln('<info>JMAP connection test passed</info>');
		return 0;
	}

	private function unsupportedProtocol(string $protocol, OutputInterface $output): int {
		$output->writeln("<error>Unsupported protocol: $protocol</error>");
		return 1;
	}
}
