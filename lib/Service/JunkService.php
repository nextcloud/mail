<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\BackgroundJob\SpamReportJob;
use OCA\Mail\Contracts\IMailManager;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Exception\ServiceException;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\BackgroundJob\IJobList;
use Psr\Log\LoggerInterface;

/**
 * Bulk counterpart of the junk listeners of a single message
 */
class JunkService {
	// Keeps the job arguments well below the length limit of the jobs table
	private const REPORT_UIDS_PER_JOB = 250;

	public function __construct(
		private IMailManager $mailManager,
		private MailboxMapper $mailboxMapper,
		private AntiSpamService $antiSpamService,
		private IJobList $jobList,
		private LoggerInterface $logger,
	) {
	}

	/**
	 * @param int[] $uids
	 * @return bool whether the messages were moved to another mailbox
	 *
	 * @throws ServiceException
	 */
	public function markMessages(Account $account, Mailbox $mailbox, array $uids, bool $junk): bool {
		if ($uids === []) {
			return false;
		}

		$this->mailManager->flagMessages($account, $mailbox, $uids, '$junk', $junk);
		$this->mailManager->flagMessages($account, $mailbox, $uids, '$notjunk', !$junk);

		$destination = $this->findDestination($account, $mailbox, $junk);
		if ($destination === null) {
			$this->queueReports($account, $mailbox, $uids, $junk);
			return false;
		}

		$mapping = $this->mailManager->moveMessages($account, $mailbox, $uids, $destination);
		if (count($mapping) < count($uids) && $this->getReportEmail($junk) !== '') {
			$this->logger->warning('The IMAP server did not report the new UIDs of all moved messages, their spam reports are skipped', [
				'account_id' => $account->getId(),
				'skipped' => count($uids) - count($mapping),
			]);
		}
		$this->queueReports($account, $destination, array_values($mapping), $junk);
		return true;
	}

	private function findDestination(Account $account, Mailbox $mailbox, bool $junk): ?Mailbox {
		$junkMailboxId = $account->getMailAccount()->getJunkMailboxId();
		if ($junkMailboxId === null) {
			return null;
		}

		try {
			if ($junk) {
				return $junkMailboxId === $mailbox->getId() ? null : $this->mailboxMapper->findById($junkMailboxId);
			}
			return $mailbox->getName() === 'INBOX' ? null : $this->mailboxMapper->find($account, 'INBOX');
		} catch (DoesNotExistException $e) {
			$this->logger->debug('Junk or inbox mailbox does not exist, messages stay in place', [
				'exception' => $e,
				'account_id' => $account->getId(),
			]);
			return null;
		}
	}

	private function getReportEmail(bool $junk): string {
		return $junk ? $this->antiSpamService->getSpamEmail() : $this->antiSpamService->getHamEmail();
	}

	/**
	 * @param int[] $uids
	 */
	private function queueReports(Account $account, Mailbox $mailbox, array $uids, bool $junk): void {
		if ($uids === [] || $this->getReportEmail($junk) === '') {
			return;
		}

		foreach (array_chunk($uids, self::REPORT_UIDS_PER_JOB) as $chunk) {
			$this->jobList->add(SpamReportJob::class, [
				'accountId' => $account->getId(),
				'mailboxId' => $mailbox->getId(),
				'uids' => $chunk,
				'flag' => $junk ? '$junk' : '$notjunk',
			]);
		}
	}
}
