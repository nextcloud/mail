<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\UserMigration;

use Exception;
use OCA\Mail\Db\Alias;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCP\IL10N;
use OCP\IUser;
use OCP\Security\ICrypto;
use OCP\UserMigration\IExportDestination;
use OCP\UserMigration\IImportSource;
use OCP\UserMigration\IMigrator;
use Symfony\Component\Console\Output\OutputInterface;
use function array_map;
use function json_encode;

class MailAccountMigrator implements IMigrator {

	public function __construct(
		private AccountService $accountService,
		private AliasesService $aliasesService,
		private IL10N $l10n,
		private ICrypto $crypto,
	) {
	}

	public function export(IUser $user,
		IExportDestination $exportDestination,
		OutputInterface $output,
	): void {
		$accounts = $this->accountService->findByUserId($user->getUID());
		$index = [];
		foreach ($accounts as $account) {
			$accountFilePath = "mail/accounts/{$account->getId()}.json";
			$accountData = $account->jsonSerialize();

			if ($account->getMailAccount()->getAuthMethod() === 'password') {
				$encryptedInboundPassword = $account->getMailAccount()->getInboundPassword();
				$encryptedOutboundPassword = $account->getMailAccount()->getOutboundPassword();
				if ($encryptedInboundPassword !== null) {
					try {
						$accountData['inboundPassword'] = $this->crypto->decrypt($encryptedInboundPassword);
					} catch (Exception $e) {
						$output->writeln("Can not decrypt inbound password of account {$account->getId()}: " . $e->getMessage());
					}
					try {
						$accountData['outboundPassword'] = $this->crypto->decrypt($encryptedOutboundPassword);
					} catch (Exception $e) {
						$output->writeln("Can not decrypt outbound password of account {$account->getId()}: " . $e->getMessage());
					}
				}
			} else if ($account->getMailAccount()->getAuthMethod() === 'xoauth2') {
				$encryptedRefreshToken = $account->getMailAccount()->getOauthRefreshToken();
				$encryptedAccessToken = $account->getMailAccount()->getOauthAccessToken();
				try {
					$accountData['oauthRefreshToken'] = $this->crypto->decrypt($encryptedRefreshToken);
				} catch (Exception $e) {
					$output->writeln("Can not decrypt oauth refresh token of account {$account->getId()}: " . $e->getMessage());
				}
				try {
					$accountData['oauthAccessToken'] = $this->crypto->decrypt($encryptedAccessToken);
				} catch (Exception $e) {
					$output->writeln("Can not decrypt oauth access token of account {$account->getId()}: " . $e->getMessage());
				}
				$accountData['oauthTokenTtl'] = $account->getMailAccount()->getOauthTokenTtl();
			}

			// TODO: translate mailbox ids to mailbox names
			unset(
				$accountData['draftsMailboxId'],
				$accountData['sentMailboxId'],
				$accountData['trashMailboxId'],
				$accountData['archiveMailboxId'],
				$accountData['snoozeMailboxId'],
				$accountData['junkMailboxId'],
			);

			$aliases = $this->aliasesService->findAll(
				$account->getId(),
				$account->getUserId(), // todo: this adds overhead - add dedicated method to fetch by account id only
			);
			$accountData['aliases'] = array_map(function (Alias $alias) {
				$data = $alias->jsonSerialize();
				// todo: smime certificate id
				// todo: provisioning id
				return $data;
			}, $aliases);

			// TODO: sieve

			// TODO: smime certificates and their account/alias links

			$exportDestination->addFileContents($accountFilePath, json_encode($accountData));
			$index[$account->getId()] = $accountFilePath;
		}

		// TODO: preferences

		$exportDestination->addFileContents('mail/accounts/index.json', json_encode($index));
	}

	public function import(IUser $user, IImportSource $importSource, OutputInterface $output,): void {
		// TODO: Implement import() method.
	}

	public function getId(): string {
		return 'mail_account';
	}

	public function getDisplayName(): string {
		return $this->l10n->t('Mail');
	}

	public function getDescription(): string {
		return $this->l10n->t('Mail account parameters, aliases and preferences');
	}

	public function getVersion(): int {
		return 1;
	}

	public function canImport(IImportSource $importSource,): bool {
		return false;
	}
}
