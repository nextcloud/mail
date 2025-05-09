<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service;

use Horde_Imap_Client_Exception;
use Horde_Mime_Exception;
use Horde_Mime_Mail;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MessageMapper;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\IMAP\MessageMapper as ImapMessageMapper;
use OCA\Mail\Model\Message;
use OCA\Mail\Service\DataUri\DataUriParser;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\IConfig;
use Psr\Log\LoggerInterface;

class AntiSpamService {
	private const NAME = 'antispam_reporting';
	private const MESSAGE_TYPE = 'message/rfc822';

	public function __construct(
		private IConfig $config,
		private MessageMapper $dbMessageMapper,
		private MailManager $mailManager,
		private IMAPClientFactory $imapClientFactory,
		private SmtpClientFactory $smtpClientFactory,
		private ImapMessageMapper $messageMapper,
		private LoggerInterface $logger,
	) {
	}

	public function getSpamEmail(): string {
		return $this->config->getAppValue('mail', self::NAME . '_spam');
	}

	public function getHamEmail(): string {
		return $this->config->getAppValue('mail', self::NAME . '_ham');
	}

	public function getSpamSubject(): string {
		return 'Learn as Junk';
	}

	public function getHamSubject(): string {
		return 'Learn as Not Junk';
	}

	public function setSpamEmail(string $email): void {
		$this->config->setAppValue('mail', self::NAME . '_spam', $email);
	}

	public function setHamEmail(string $email): void {
		$this->config->setAppValue('mail', self::NAME . '_ham', $email);
	}

	public function deleteConfig(): void {
		$this->config->deleteAppValue('mail', self::NAME . '_spam');
		$this->config->deleteAppValue('mail', self::NAME . '_ham');
	}

	/**
	 * @param Account $account
	 * @param Mailbox $mailbox
	 * @param int $uid
	 * @param string $flag
	 * @throws ServiceException
	 */
	public function sendReportEmail(Account $account, Mailbox $mailbox, int $uid, string $flag): void {
		$reportEmail = ($flag === '$junk') ? $this->getSpamEmail() : $this->getHamEmail();
		if ($reportEmail === '') {
			return;
		}
		$subject = ($flag === '$junk') ? $this->getSpamSubject() : $this->getHamSubject();

		// Message to attach not found
		$messageId = $this->dbMessageMapper->getIdForUid($mailbox, $uid);
		if ($messageId === null) {
			throw new ServiceException('Could not find reported message');
		}

		if ($account->getMailAccount()->getSentMailboxId() === null) {
			throw new ServiceException('Could not find sent mailbox');
		}

		$message = new Message();
		$from = new AddressList([
			Address::fromRaw($account->getName(), $account->getEMailAddress()),
		]);
		$to = new AddressList([
			Address::fromRaw($reportEmail, $reportEmail),
		]);
		$message->setTo($to);
		$message->setSubject($subject);
		$message->setFrom($from);
		$message->setContent($subject);

		// Gets original of other message
		$userId = $account->getMailAccount()->getUserId();
		try {
			$attachmentMessage = $this->mailManager->getMessage($userId, $messageId);
		} catch (DoesNotExistException $e) {
			$this->logger->error('Could not find reported email with message ID #' . $messageId, ['exception' => $e]);
			return;
		}

		$mailbox = $this->mailManager->getMailbox($userId, $attachmentMessage->getMailboxId());

		$client = $this->imapClientFactory->getClient($account);
		try {
			$fullText = $this->messageMapper->getFullText(
				$client,
				$mailbox->getName(),
				$attachmentMessage->getUid(),
				$userId
			);
		} finally {
			$client->logout();
		}

		$message->addEmbeddedMessageAttachment(
			$attachmentMessage->getSubject() . '.eml',
			$fullText
		);

		$transport = $this->smtpClientFactory->create($account);
		// build mime body
		$headers = [
			'From' => $message->getFrom()->first()->toHorde(),
			'To' => $message->getTo()->toHorde(),
			'Cc' => $message->getCC()->toHorde(),
			'Bcc' => $message->getBCC()->toHorde(),
			'Subject' => $message->getSubject(),
		];

		if (($inReplyTo = $message->getInReplyTo()) !== null) {
			$headers['References'] = $inReplyTo;
			$headers['In-Reply-To'] = $inReplyTo;
		}

		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);

		$mimeMessage = new MimeMessage(
			new DataUriParser()
		);
		$mimePart = $mimeMessage->build(
			null,
			$message->getContent(),
			$message->getAttachments()
		);

		$mail->setBasePart($mimePart);

		// Send the message
		try {
			$mail->send($transport, false, false);
		} catch (Horde_Mime_Exception $e) {
			throw new ServiceException(
				'Could not send message: ' . $e->getMessage(),
				$e->getCode(),
				$e
			);
		}

		$sentMailboxId = $account->getMailAccount()->getSentMailboxId();
		if ($sentMailboxId === null) {
			$this->logger->warning("No sent mailbox exists, can't save sent message");
			return;
		}

		// Save the message in the sent mailbox
		try {
			$sentMailbox = $this->mailManager->getMailbox(
				$account->getUserId(),
				$sentMailboxId
			);
		} catch (ClientException $e) {
			$this->logger->error('Sent mailbox could not be found', [
				'exception' => $e,
			]);
			return;
		}

		$client = $this->imapClientFactory->getClient($account);
		try {
			$this->messageMapper->save(
				$client,
				$sentMailbox,
				$mail->getRaw(false)
			);
		} catch (Horde_Imap_Client_Exception $e) {
			$this->logger->error('Could not move report email to sent mailbox, but the report email was sent. Reported email was id: #' . $messageId, ['exception' => $e]);
		} finally {
			$client->logout();
		}
	}

}
