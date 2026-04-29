<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Command;

use OCA\Mail\Db\MailAccount;
use OCA\Mail\Service\AccountService;
use OCP\Security\ICrypto;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

final class ExportAccount extends Command {
	public const ARGUMENT_USER_ID = 'user-id';
	public const ARGUMENT_OUTPUT_FORMAT = 'output';

	private AccountService $accountService;
	private ICrypto $crypto;

	public function __construct(AccountService $service, ICrypto $crypto) {
		parent::__construct();

		$this->accountService = $service;
		$this->crypto = $crypto;
	}

	/**
	 * @return void
	 */
	protected function configure() {
		$this->setName('mail:account:export');
		$this->setDescription('Exports a user\'s mail account(s)');
		$this->addArgument(self::ARGUMENT_USER_ID, InputArgument::REQUIRED);
		$this->addOption(self::ARGUMENT_OUTPUT_FORMAT, '', InputOption::VALUE_OPTIONAL);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$userId = $input->getArgument(self::ARGUMENT_USER_ID);

		$accounts = $this->accountService->findByUserId($userId);

		if ($input->getOption(self::ARGUMENT_OUTPUT_FORMAT) === 'json') {
			$output->writeln(json_encode($this->getAccountsData($accounts)));
		} elseif ($input->getOption(self::ARGUMENT_OUTPUT_FORMAT) === 'json_pretty') {
			$output->writeln(json_encode($this->getAccountsData($accounts), JSON_PRETTY_PRINT));
		} else {
			foreach ($accounts as $account) {
				$accountId = $account->getId();
				$output->writeln("<info>Account $accountId:</info>");
				$output->writeln('- E-Mail: ' . $account->getEmail());
				$output->writeln('- Name: ' . $account->getName());
				$provision = $this->getProvisionData($account);
				$output->writeln('- Provision: ' . $provision['status'] . ' ID: ' . $provision['id']);
				$this->writeProtocolDetails($account, $output);
			}
		}

		return 0;
	}

	private function getProvisionData($account): array {
		$provisioningId = $account->getMailAccount()->getProvisioningId();

		return [
			'status' => $provisioningId ? 'set' : 'none',
			'id' => $provisioningId ?: 'N/A',
		];
	}

	private function getProtocolData($account): array {
		$mailAccount = $account->getMailAccount();
		$protocol = $mailAccount->getProtocol();

		return match ($protocol) {
			MailAccount::PROTOCOL_JMAP => [
				'jmap' => [
					'user' => $mailAccount->getInboundUser(),
					'host' => $mailAccount->getInboundHost(),
					'port' => $mailAccount->getInboundPort(),
					'security' => $mailAccount->getInboundSslMode(),
					'path' => $mailAccount->getPath() ?? '/.well-known/jmap',
				],
			],
			MailAccount::PROTOCOL_IMAP => [
				'imap' => [
					'user' => $mailAccount->getInboundUser(),
					'host' => $mailAccount->getInboundHost(),
					'port' => $mailAccount->getInboundPort(),
					'security' => $mailAccount->getInboundSslMode(),
				],
				'smtp' => [
					'user' => $mailAccount->getOutboundUser(),
					'host' => $mailAccount->getOutboundHost(),
					'port' => $mailAccount->getOutboundPort(),
					'security' => $mailAccount->getOutboundSslMode(),
				],
			],
			default => [
				'unsupported' => [
					'protocol' => $protocol,
				],
			],
		};
	}

	private function getAccountsData($accounts) {
		$accountsData = [];

		foreach ($accounts as $account) {
			$mailAccount = $account->getMailAccount();
			$accountsData[] = [
				'id' => $account->getId(),
				'email' => $account->getEmail(),
				'name' => $account->getName(),
				'protocol' => $mailAccount->getProtocol(),
				'provision' => $this->getProvisionData($account),
				...$this->getProtocolData($account),
			];
		}

		return $accountsData;
	}

	private function writeProtocolDetails($account, OutputInterface $output): void {
		$mailAccount = $account->getMailAccount();
		$protocol = $mailAccount->getProtocol();

		switch ($protocol) {
			case MailAccount::PROTOCOL_JMAP:
				$port = $mailAccount->getInboundPort();
				$path = $mailAccount->getPath() ?? '/.well-known/jmap';
				$output->writeln('- Protocol: JMAP');
				$output->writeln('- JMAP user: ' . $mailAccount->getInboundUser());
				$output->writeln('- JMAP endpoint: ' . $mailAccount->getInboundHost() . ":$port$path, security: " . $mailAccount->getInboundSslMode());
				return;

			case MailAccount::PROTOCOL_IMAP:
				$output->writeln('- Protocol: IMAP');
				$output->writeln('- IMAP user: ' . $mailAccount->getInboundUser());
				$inboundPort = $mailAccount->getInboundPort();
				$output->writeln('- IMAP host: ' . $mailAccount->getInboundHost() . ":$inboundPort, security: " . $mailAccount->getInboundSslMode());
				$output->writeln('- SMTP user: ' . $mailAccount->getOutboundUser());
				$outboundPort = $mailAccount->getOutboundPort();
				$output->writeln('- SMTP host: ' . $mailAccount->getOutboundHost() . ":$outboundPort, security: " . $mailAccount->getOutboundSslMode());
				return;

			default:
				$output->writeln('- Protocol: ' . $protocol . ' (unsupported export format)');
		}
	}

}
