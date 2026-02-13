<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Service;

use OCA\Mail\Account;
use OCA\Mail\Contracts\IMailTransmission;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Util\ServerVersion;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Calendar\IManager;
use Psr\Log\LoggerInterface;
use Throwable;

use function array_filter;

class IMipService {
	private AccountService $accountService;
	private IManager $calendarManager;
	private LoggerInterface $logger;
	private MailboxMapper $mailboxMapper;
	private MailManager $mailManager;
	private MessageMapper $messageMapper;
	private ServerVersion $serverVersion;
	private IMAPClientFactory $imapClientFactory;
	private ImapMessageMapper $imapMessageMapper;
	private IMailTransmission $mailTransmission;
	private AttachmentService $attachmentService;

	public function __construct(
		AccountService $accountService,
		IManager $manager,
		LoggerInterface $logger,
		MailboxMapper $mailboxMapper,
		MailManager $mailManager,
		MessageMapper $messageMapper,
		ServerVersion $serverVersion,
		IMAPClientFactory $imapClientFactory,
		ImapMessageMapper $imapMessageMapper,
		IMailTransmission $mailTransmission,
		AttachmentService $attachmentService,
	) {
		$this->accountService = $accountService;
		$this->calendarManager = $manager;
		$this->logger = $logger;
		$this->mailboxMapper = $mailboxMapper;
		$this->mailManager = $mailManager;
		$this->messageMapper = $messageMapper;
		$this->serverVersion = $serverVersion;
		$this->imapClientFactory = $imapClientFactory;
		$this->imapMessageMapper = $imapMessageMapper;
		$this->mailTransmission = $mailTransmission;
		$this->attachmentService = $attachmentService;
	}

	public function process(): void {
		$messages = $this->messageMapper->findIMipMessagesAscending();
		if ($messages === []) {
			$this->logger->debug('No iMIP messages to process.');
			return;
		}

		// Collect all mailboxes in memory
		// possible perf improvement - make this one IN query
		// and JOIN with accounts table
		// although this might not make much of a difference
		// since there are very few messages to process
		$mailboxIds = array_unique(array_map(static fn (Message $message) => $message->getMailboxId(), $messages));

		$mailboxes = array_map(function (int $mailboxId) {
			try {
				return $this->mailboxMapper->findById($mailboxId);
			} catch (DoesNotExistException|ServiceException $e) {
				return null;
			}
		}, $mailboxIds);
		$existingMailboxes = array_filter($mailboxes);

		// Collect all accounts in memory
		$accountIds = array_unique(array_map(static fn (Mailbox $mailbox) => $mailbox->getAccountId(), $existingMailboxes));

		$accounts = array_combine($accountIds, array_map(function (int $accountId) {
			try {
				return $this->accountService->findById($accountId);
			} catch (DoesNotExistException $e) {
				return null;
			}
		}, $accountIds));

		foreach ($existingMailboxes as $mailbox) {
			/** @var Account $account */
			$account = $accounts[$mailbox->getAccountId()];
			$filteredMessages = array_filter($messages, static fn ($message) => $message->getMailboxId() === $mailbox->getId());

			if ($filteredMessages === []) {
				continue;
			}

			// Check for accounts or mailboxes that no longer exist,
			// no processing for drafts, sent items, junk or archive
			if ($account === null
				|| $account->getMailAccount()->getArchiveMailboxId() === $mailbox->getId()
				|| $account->getMailAccount()->getSnoozeMailboxId() === $mailbox->getId()
				|| $account->getMailAccount()->getTrashMailboxId() === $mailbox->getId()
				|| $account->getMailAccount()->getSentMailboxId() === $mailbox->getId()
				|| $account->getMailAccount()->getDraftsMailboxId() === $mailbox->getId()
				|| $mailbox->isSpecialUse(\Horde_Imap_Client::SPECIALUSE_ARCHIVE)
			) {
				$processedMessages = array_map(static function (Message $message) {
					$message->setImipProcessed(true);
					return $message;
				}, $filteredMessages); // Silently drop from passing to DAV and mark as processed, so we won't run into these messages again.
				$this->messageMapper->updateImipData(...$processedMessages);
				continue;
			}

			try {
				$imapMessages = $this->mailManager->getImapMessagesForScheduleProcessing($account, $mailbox, array_map(static fn ($message) => $message->getUid(), $filteredMessages));
			} catch (ServiceException $e) {
				$this->logger->error('Could not get IMAP messages form IMAP server', ['exception' => $e]);
				continue;
			}

			$userId = $account->getUserId();
			$recipient = $account->getEmail();
			$imipCreate = $account->getImipCreate();
			$systemVersion = $this->serverVersion->getMajorVersion();

			foreach ($filteredMessages as $message) {
				/** @var IMAPMessage $imapMessage */
				$imapMessage = current(array_filter($imapMessages, static fn (IMAPMessage $imapMessage) => $message->getUid() === $imapMessage->getUid()));
				if (empty($imapMessage->scheduling)) {
					// No scheduling info, maybe the DB is wrong
					$message->setImipProcessed(true);
					$message->setImipError(true);
					continue;
				}

				$sender = $imapMessage->getFrom()->first()?->getEmail();
				if ($sender === null) {
					$message->setImipProcessed(true);
					$message->setImipError(true);
					continue;
				}

				try {
					// an IMAP message could contain more than one iMIP object
					foreach ($imapMessage->scheduling as $schedulingInfo) {
						$processed = false;
						if ($systemVersion < 33) {
							$principalUri = 'principals/users/' . $userId;
							if ($schedulingInfo['method'] === 'REQUEST') {
								$processed = $this->calendarManager->handleIMipRequest($principalUri, $sender, $recipient, $schedulingInfo['contents']);
							} elseif ($schedulingInfo['method'] === 'REPLY') {
								$processed = $this->calendarManager->handleIMipReply($principalUri, $sender, $recipient, $schedulingInfo['contents']);
							} elseif ($schedulingInfo['method'] === 'CANCEL') {
								$replyTo = $imapMessage->getReplyTo()->first()?->getEmail();
								$processed = $this->calendarManager->handleIMipCancel($principalUri, $sender, $replyTo, $recipient, $schedulingInfo['contents']);
							}
						} else {
							if (!method_exists($this->calendarManager, 'handleIMip')) {
								$this->logger->error('iMIP handling is not supported by server version installed.');
								continue;
							}
							$processed = $this->calendarManager->handleIMip(
								$userId,
								$schedulingInfo['contents'],
								[
									'recipient' => $recipient,
									'absent' => $imipCreate ? 'create' : 'ignore',
									'absentCreateStatus' => 'tentative',
								],
							);
						}

						$message->setImipProcessed($processed);
						$message->setImipError(!$processed);
					}
				} catch (Throwable $e) {
					$this->logger->error('iMIP message processing failed', [
						'exception' => $e,
						'messageId' => $message->getId(),
						'mailboxId' => $mailbox->getId(),
					]);
					$message->setImipProcessed(true);
					$message->setImipError(true);

					$affectedUsers = [$account->getEmail()];
					// add original sender ($replyTo) and admin email to affected users?
					/**
					$replyTo = $imapMessage->getReplyTo()->first()?->getEmail();
 					if ($replyTo !== null && $replyTo !== $account->getEmail()) {
					 	$affectedUsers[] = $replyTo;
					}
					*/

					// Send error notification via email
					try {
						$this->sendErrorNotification($account, $mailbox, $message, $imapMessage, $sender, $affectedUsers, $e);
					} catch (Throwable $notificationException) {
						$this->logger->error('Failed to send error notification', [
							'exception' => $notificationException,
							'messageId' => $message->getId(),
							'affectedUsers' => $affectedUsers,
						]);
					}
				}
			}
			$this->messageMapper->updateImipData(...$filteredMessages);
		}
	}

	/**
	 * Send error notification email when iMIP processing fails
	 */
	private function sendErrorNotification(
		Account $account,
		Mailbox $mailbox,
		Message $message,
		IMAPMessage $imapMessage,
		string $sender,
		array $affectedUsers,
		Throwable $exception,
	): void {
		// Fetch the raw message content from IMAP
		$client = $this->imapClientFactory->getClient($account);
		try {
			$rawMessage = $this->imapMessageMapper->getFullText(
				$client,
				$mailbox->getName(),
				$message->getUid(),
				$account->getUserId(),
				false // Don't decrypt, send raw message
			);

			if ($rawMessage === null) {
				throw new ServiceException('Could not fetch raw message content');
			}
		} finally {
			$client->logout();
		}

		$localMessage = new LocalMessage();
		$localMessage->setType(LocalMessage::TYPE_OUTGOING);
		$localMessage->setAccountId($account->getId());
		$localMessage->setSubject('[ERROR] Calendar invitation processing failed: ' . ($imapMessage->getSubject() ?: 'No Subject'));
		$localMessage->setBodyHtml(null);
		$localMessage->setBodyPlain($this->buildErrorNotificationBody($account, $message, $imapMessage, $sender, $affectedUsers, $exception));
		$localMessage->setHtml(false);

		// Build recipient list - include all affected users
		$recipients = [];
		foreach ($affectedUsers as $userEmail) {
			$recipient = new Recipient();
			$recipient->setType(Recipient::TYPE_TO);
			$recipient->setEmail($userEmail);
			$recipient->setLabel($userEmail);
			$recipients[] = $recipient;
		}
		$localMessage->setRecipients($recipients);

		// Create attachment from the raw message
		$attachment = $this->attachmentService->addFileFromString(
			$account->getUserId(),
			$this->sanitizeFilename($imapMessage->getSubject() ?? 'original-message') . '.eml',
			'message/rfc822',
			$rawMessage
		);
		$localMessage->setAttachments([$attachment]);

		// Send using the account's SMTP settings
		$this->mailTransmission->sendMessage($account, $localMessage);

		$this->logger->info('Error notification sent for failed iMIP message', [
			'messageId' => $message->getId(),
			'from' => $account->getEmail(),
			'recipients' => $affectedUsers,
			'subject' => $imapMessage->getSubject(),
		]);
	}

	/**
	 * Sanitize a filename by removing invalid characters
	 *
	 * @param string $filename The filename to sanitize
	 * @return string The sanitized filename
	 */
	private function sanitizeFilename(string $filename): string {
		// Remove or replace characters that are invalid in filenames
		$filename = preg_replace('/[^\w\s\-_.]/u', '_', $filename);
		$filename = trim($filename, '._');
		return empty($filename) ? 'original-message' : $filename;
	}

	/**
	 * Build the body of the error notification email
	 *
	 * @param Account $account The account that received the message
	 * @param Message $message The message entity
	 * @param IMAPMessage $imapMessage The IMAP message
	 * @param string $sender The sender email address
	 * @param array $affectedUsers List of affected user emails
	 * @param Throwable $exception The exception that caused the error
	 * @return string The notification body
	 */
	private function buildErrorNotificationBody(Account $account, Message $message, IMAPMessage $imapMessage, string $sender, array $affectedUsers, Throwable $exception): string {
		$schedulingMethods = [];
		foreach ($imapMessage->scheduling as $schedulingInfo) {
			$schedulingMethods[] = $schedulingInfo['method'] ?? 'UNKNOWN';
		}

		$lines = [
			'Calendar Invitation Processing Error',
			'====================================',
			'',
			'We were unable to automatically process a calendar invitation in your email.',
			'',
			'WHAT YOU NEED TO DO:',
			'Please manually add this event to your calendar.',
			'',
			'The original email with the calendar invitation is attached to this message.',
			'You can open the attached .eml file and add the event to your calendar from there.',
			'',
			'If you have any questions, please contact the system administrator.',
			'',
			'====================================',
			'',
			'Email Details:',
			'- Subject: ' . ($imapMessage->getSubject() ?? '(no subject)'),
			'- From: ' . $sender,
			'- To: ' . $account->getEmail(),
			'- Date: ' . $imapMessage->getSentDate()->format('Y-m-d H:i:s'),
			'',
			'Calendar Event Type:',
			'- Method: ' . implode(', ', $schedulingMethods),
			'',
			'Timestamp: ' . date('Y-m-d H:i:s'),
			'',
			'Technical Details:',
			'- Message ID: ' . $message->getId(),
			'- Account: ' . $account->getEmail(),
			'- Recipients notified: ' . implode(', ', $affectedUsers),
			'- Error: ' . $exception->getMessage(),
			'',
			'',
			'====================================',
			'',
			'This is an automated message please do not reply.',
			'',
		];

		return implode("\n", $lines);
	}
}
