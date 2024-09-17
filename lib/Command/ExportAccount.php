<?php﻿
﻿
﻿/**﻿
﻿ * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors﻿
﻿ * SPDX-License-Identifier: AGPL-3.0-only﻿
﻿ */﻿
﻿
﻿namespace OCA\Mail\Command;﻿
﻿
﻿use OCA\Mail\Service\AccountService;﻿
﻿use OCP\Security\ICrypto;﻿
﻿use Symfony\Component\Console\Command\Command;﻿
﻿use Symfony\Component\Console\Input\InputArgument;﻿
﻿use Symfony\Component\Console\Input\InputInterface;﻿
﻿use Symfony\Component\Console\Input\InputOption;﻿
﻿use Symfony\Component\Console\Output\OutputInterface;﻿
﻿
﻿class ExportAccount extends Command {﻿
﻿	public const ARGUMENT_USER_ID = 'user-id';﻿
﻿	public const ARGUMENT_OUTPUT_FORMAT = 'output';﻿
﻿
﻿	private AccountService $accountService;﻿
﻿	private ICrypto $crypto;﻿
﻿
﻿	public function __construct(AccountService $service, ICrypto $crypto) {﻿
﻿		parent::__construct();﻿
﻿
﻿		$this->accountService = $service;﻿
﻿		$this->crypto = $crypto;﻿
﻿	}﻿
﻿
﻿	/**﻿
﻿	 * @return void﻿
﻿	 */﻿
﻿	protected function configure() {﻿
﻿		$this->setName('mail:account:export');﻿
﻿		$this->setDescription('Exports a user\'s IMAP account(s)');﻿
﻿		$this->addArgument(self::ARGUMENT_USER_ID, InputArgument::REQUIRED);﻿
﻿		$this->addOption(self::ARGUMENT_OUTPUT_FORMAT, '', InputOption::VALUE_OPTIONAL);﻿
﻿	}﻿
﻿
﻿	private function getAccountsData($accounts) {﻿
﻿		$accountsData = [];﻿
﻿
﻿		foreach ($accounts as $account) {﻿
﻿			$accountsData[] = [﻿
﻿				'id' => $account->getId(),﻿
﻿				'email' => $account->getEmail(),﻿
﻿				'name' => $account->getName(),﻿
﻿				'provision' => [﻿
﻿					'status' => $account->getProvisioningId() ? 'set' : 'none',﻿
﻿					'id' => $account->getProvisioningId() ?: 'N/A'﻿
﻿				],﻿
﻿				'imap' => [﻿
﻿					'user' => $account->getInboundUser(),﻿
﻿					'host' => $account->getInboundHost(),﻿
﻿					'port' => $account->getInboundPort(),﻿
﻿					'security' => $account->getInboundSslMode()﻿
﻿				],﻿
﻿				'smtp' => [﻿
﻿					'user' => $account->getOutboundUser(),﻿
﻿					'host' => $account->getOutboundHost(),﻿
﻿					'port' => $account->getOutboundPort(),﻿
﻿					'security' => $account->getOutboundSslMode()﻿
﻿				]﻿
﻿			];﻿
﻿		}﻿
﻿
﻿		return $accountsData;﻿
﻿	}﻿
﻿
﻿	protected function execute(InputInterface $input, OutputInterface $output): int {﻿
﻿		$userId = $input->getArgument(self::ARGUMENT_USER_ID);﻿
﻿
﻿		$accounts = $this->accountService->findByUserId($userId);﻿
﻿
﻿		if ($input->getOption(self::ARGUMENT_OUTPUT_FORMAT) === 'json') {﻿
﻿			$output->writeln(json_encode($this->getAccountsData($accounts)));﻿
﻿		} elseif ($input->getOption(self::ARGUMENT_OUTPUT_FORMAT) === 'json_pretty') {﻿
﻿			$output->writeln(json_encode($this->getAccountsData($accounts), JSON_PRETTY_PRINT));﻿
﻿		} else {﻿
﻿			foreach ($accounts as $account) {﻿
﻿				$output->writeln('<info>Account ' . $account->getId() . ':</info>');﻿
﻿				$output->writeln('- E-Mail: ' . $account->getEmail());﻿
﻿				$output->writeln('- Name: ' . $account->getName());﻿
﻿				$output->writeln('- Provision: ' . ($account->getProvisioningId() ? 'set' : 'none'). ' ID: ' . ($account->getProvisioningId() ? $account->getProvisioningId():'N/A'));﻿
﻿				$output->writeln('- IMAP user: ' . $account->getInboundUser());﻿
﻿				$output->writeln('- IMAP host: ' . $account->getInboundHost() . ':' . $account->getInboundPort() . ', security: ' . $account->getInboundSslMode());﻿
﻿				$output->writeln('- SMTP user: ' . $account->getOutboundUser());﻿
﻿				$output->writeln('- SMTP host: ' . $account->getOutboundHost() . ':' . $account->getOutboundPort() . ', security: ' . $account->getOutboundSslMode());﻿
﻿			}﻿
﻿		}﻿
﻿
﻿		return 0;﻿
﻿	}﻿
﻿}﻿
