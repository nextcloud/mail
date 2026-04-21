<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\IMAP;

use Horde_Exception;
use Horde_Imap_Client;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use Horde_Mail_Transport_Null;
use Horde_Mail_Transport_Smtphorde;
use Horde_Mime_Exception;
use Horde_Mime_Headers;
use Horde_Mime_Headers_Addresses;
use Horde_Mime_Headers_Date;
use Horde_Mime_Headers_MessageId;
use Horde_Mime_Headers_Subject;
use Horde_Mime_Mail;
use Horde_Mime_Mdn;
use Horde_Smtp_Exception;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\Contracts\ITransmissionConnector;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\MimeMessage;
use OCA\Mail\Service\TransmissionService;
use OCA\Mail\SMTP\SmtpClientFactory;
use OCA\Mail\Support\PerformanceLogger;
use OCP\AppFramework\Db\DoesNotExistException;
use Psr\Log\LoggerInterface;
use Throwable;

class ImapTransmissionConnector implements ITransmissionConnector {
	private const RETRIABLE_CODES = [
		Horde_Smtp_Exception::INSUFFICIENT_STORAGE,
		Horde_Smtp_Exception::OVERQUOTA,
		Horde_Smtp_Exception::LOGIN_REQUIREAUTHENTICATION,
	];

	public function __construct(
		private ProtocolFactory $protocolFactory,
		private TransmissionService $transmissionService,
		private AliasesService $aliasesService,
		private SmtpClientFactory $smtpClientFactory,
		private MimeMessage $mimeMessage,
		private MessageMapper $messageMapper,
		private MailboxMapper $mailboxMapper,
		private PerformanceLogger $performanceLogger,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function sendMessage(Account $account, LocalMessage $message, Mailbox $sentMailbox): void {
		// If this is a retry of a failed copy-to-sent, skip SMTP and only re-attempt the copy.
		if ($message->getStatus() === LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL) {
			$raw = $message->getRaw();
			if ($raw !== null) {
				$client = $this->protocolFactory->imapClient($account);
				try {
					$this->messageMapper->save($client, $sentMailbox, $raw, []);
					$message->setStatus(LocalMessage::STATUS_PROCESSED);
				} catch (\Throwable $e) {
					$this->logger->error('Retry copy-to-sent failed: ' . $e->getMessage(), ['exception' => $e]);
					$message->setStatus(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);
				} finally {
					$client->logout();
				}
			} else {
				$message->setStatus(LocalMessage::STATUS_ERROR);
			}
			return;
		}
		$to = $this->transmissionService->getAddressList($message, Recipient::TYPE_TO);
		$cc = $this->transmissionService->getAddressList($message, Recipient::TYPE_CC);
		$bcc = $this->transmissionService->getAddressList($message, Recipient::TYPE_BCC);
		$attachments = $this->transmissionService->getAttachments($message);

		$name = $account->getName();
		$emailAddress = $account->getEMailAddress();

		if ($message->getAliasId() !== null) {
			try {
				$alias = $this->aliasesService->find($message->getAliasId(), $account->getUserId());
				$name = ($alias->getName() ?? $name);
				$emailAddress = $alias->getAlias();
			} catch (DoesNotExistException) {
				$this->logger->debug('The assigned alias no longer exists. Falling back to the default name and email address. It is likely that the alias was deleted or deprovisioned in the meantime.', [
					'aliasId' => $message->getAliasId(),
					'accountId' => $account->getId(),
				]);
			}
		}

		$from = Address::fromRaw($name, $emailAddress);

		$attachmentParts = [];
		foreach ($attachments as $attachment) {
			$part = $this->transmissionService->handleAttachment($account, $attachment);
			if ($part !== null) {
				$attachmentParts[] = $part;
			}
		}

		$transport = $this->smtpClientFactory->create($account);

		$headers = $this->buildHeaders($from, $to, $cc, $bcc, $message->getSubject());

		if (($inReplyTo = $message->getInReplyToMessageId()) !== null) {
			$headers['References'] = $inReplyTo;
			$headers['In-Reply-To'] = $inReplyTo;
		}

		if ($message->getRequestMdn()) {
			$headers[\Horde_Mime_Mdn::MDN_HEADER] = $from->toHorde();
		}

		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);

		$mimePart = $this->mimeMessage->build(
			$message->getBodyPlain(),
			$message->getBodyHtml(),
			$message->isPgpMime() === true,
			$attachmentParts,
		);

		try {
			$mimePart = $this->transmissionService->getSignMimePart($message, $account, $mimePart);
			$mimePart = $this->transmissionService->getEncryptMimePart($message, $to, $cc, $bcc, $account, $mimePart);
		} catch (ServiceException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return;
		}

		$mail->setBasePart($mimePart);

		try {
			$mail->send($transport, false, false);
			$message->setRaw($mail->getRaw(false));
			$message->setStatus(LocalMessage::STATUS_RAW);
		} catch (Horde_Mime_Exception $e) {
			if ($e->getPrevious() instanceof Horde_Smtp_Exception) {
				/** @var Horde_Smtp_Exception $previousException */
				$previousException = $e->getPrevious();
				$this->logger->error('SMTP error: ' . $e->getMessage(), [
					'exception' => $e,
					'smtpErrorCode' => $previousException->getSmtpCode(),
				]);
			} else {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
			}

			if (in_array($e->getCode(), self::RETRIABLE_CODES, true)) {
				$message->setStatus(LocalMessage::STATUS_SMPT_SEND_FAIL);
				return;
			}

			try {
				$message->setRaw($mail->getRaw(false));
			} catch (Throwable) {
				// Having the raw message is nice for troubleshooting, but should not fail hard.
			}
			$message->setStatus(LocalMessage::STATUS_ERROR);
			return;
		} finally {
			if ($transport instanceof Horde_Mail_Transport_Smtphorde) {
				try {
					$transport->getSMTPObject()->logout();
				} catch (Throwable) {
					// Handle silently as this is a resource usage optimization
				}
			}
		}

		// Copy to Sent mailbox after successful SMTP send
		$raw = $message->getRaw();
		if ($raw !== null) {
			$client = $this->protocolFactory->imapClient($account);
			try {
				$this->messageMapper->save($client, $sentMailbox, $raw, []);
				$message->setStatus(LocalMessage::STATUS_PROCESSED);
			} catch (\Throwable $e) {
				$this->logger->error('Copy to sent mailbox failed: ' . $e->getMessage(), ['exception' => $e]);
				$message->setStatus(LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL);
			} finally {
				$client->logout();
			}
		}
	}

	#[\Override]
	public function saveMessage(Account $account, Mailbox $mailbox, LocalMessage $message, array $flags = []): void {
		$to = $this->transmissionService->getAddressList($message, Recipient::TYPE_TO);
		$cc = $this->transmissionService->getAddressList($message, Recipient::TYPE_CC);
		$bcc = $this->transmissionService->getAddressList($message, Recipient::TYPE_BCC);
		$attachments = $this->transmissionService->getAttachments($message);

		$perfLogger = $this->performanceLogger->start('save message to IMAP mailbox');

		$from = Address::fromRaw($account->getName(), $account->getEMailAddress());

		foreach ($attachments as $attachment) {
			$this->transmissionService->handleAttachment($account, $attachment);
		}

		$headers = $this->buildHeaders($from, $to, $cc, $bcc, $message->getSubject());

		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);
		if ($message->isHtml()) {
			$mail->setHtmlBody($message->getBodyHtml());
		} else {
			$mail->setBody($message->getBodyPlain());
		}
		$mail->addHeaderOb(Horde_Mime_Headers_MessageId::create());
		$perfLogger->step('build MIME message');

		// Map JMAP-style keyword flags to IMAP flags
		$imapFlags = [];
		foreach ($flags as $flag) {
			$imapFlag = match (strtolower($flag)) {
				'$draft' => Horde_Imap_Client::FLAG_DRAFT,
				'$seen' => Horde_Imap_Client::FLAG_SEEN,
				'$flagged' => Horde_Imap_Client::FLAG_FLAGGED,
				'$answered' => Horde_Imap_Client::FLAG_ANSWERED,
				'$deleted' => Horde_Imap_Client::FLAG_DELETED,
				default => null,
			};
			if ($imapFlag !== null) {
				$imapFlags[] = $imapFlag;
			}
		}

		$client = $this->protocolFactory->imapClient($account);
		try {
			$transport = new Horde_Mail_Transport_Null();
			$mail->send($transport, false, false);
			$perfLogger->step('encode MIME message');
			$this->messageMapper->save($client, $mailbox, $mail->getRaw(false), $imapFlags);
			$perfLogger->step('save message on IMAP');
		} catch (Horde_Exception $e) {
			throw new ServiceException('Could not save message to IMAP mailbox', 0, $e);
		} finally {
			$client->logout();
		}

		$perfLogger->end();
	}

	#[\Override]
	public function sendMdn(Account $account, Mailbox $mailbox, Message $message): void {
		$query = new Horde_Imap_Client_Fetch_Query();
		$query->flags();
		$query->uid();
		$query->imapDate();
		$query->headerText([
			'cache' => true,
			'peek' => true,
		]);

		$imapClient = $this->protocolFactory->imapClient($account);
		try {
			/** @var Horde_Imap_Client_Data_Fetch[] $fetchResults */
			$fetchResults = iterator_to_array($imapClient->fetch($mailbox->getName(), $query, [
				'ids' => new Horde_Imap_Client_Ids([$message->getUid()]),
			]), false);
		} finally {
			$imapClient->logout();
		}

		if (count($fetchResults) < 1) {
			throw new ServiceException("Message \"{$message->getId()}\" not found.");
		}

		$imapDate = $fetchResults[0]->getImapDate();
		/** @var Horde_Mime_Headers $mdnHeaders */
		$mdnHeaders = $fetchResults[0]->getHeaderText('0', Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
		/** @var Horde_Mime_Headers_Addresses|null $dispositionNotificationTo */
		$dispositionNotificationTo = $mdnHeaders->getHeader('disposition-notification-to');
		/** @var Horde_Mime_Headers_Addresses|null $originalRecipient */
		$originalRecipient = $mdnHeaders->getHeader('original-recipient');

		if ($dispositionNotificationTo === null) {
			throw new ServiceException("Message \"{$message->getId()}\" has no disposition-notification-to header.");
		}

		$headers = new Horde_Mime_Headers();
		$headers->addHeaderOb($dispositionNotificationTo);

		if ($originalRecipient instanceof Horde_Mime_Headers_Addresses) {
			$headers->addHeaderOb($originalRecipient);
		}

		$headers->addHeaderOb(new Horde_Mime_Headers_Subject(null, $message->getSubject()));
		$headers->addHeaderOb(new Horde_Mime_Headers_Addresses('From', $message->getFrom()->toHorde()));
		$headers->addHeaderOb(new Horde_Mime_Headers_Addresses('To', $message->getTo()->toHorde()));
		$headers->addHeaderOb(new Horde_Mime_Headers_MessageId(null, $message->getMessageId()));
		$headers->addHeaderOb(new Horde_Mime_Headers_Date(null, $imapDate->format('r')));

		$smtpClient = $this->smtpClientFactory->create($account);

		$mdn = new Horde_Mime_Mdn($headers);
		try {
			$mdn->generate(
				true,
				true,
				'displayed',
				$account->getMailAccount()->getOutboundHost(),
				$smtpClient,
				[
					'from_addr' => $account->getEMailAddress(),
					'charset' => 'UTF-8',
				]
			);
		} catch (Horde_Mime_Exception $e) {
			throw new ServiceException("Unable to send mdn for message \"{$message->getId()}\" caused by: {$e->getMessage()}", 0, $e);
		}
	}

	/**
	 * @return array{
	 *     From: \Horde_Mail_Rfc822_Address,
	 *     To: \Horde_Mail_Rfc822_List,
	 *     Subject: string|null,
	 *     Cc?: \Horde_Mail_Rfc822_List,
	 *     Bcc?: \Horde_Mail_Rfc822_List,
	 * }
	 */
	private function buildHeaders(Address $from, \OCA\Mail\AddressList $to, \OCA\Mail\AddressList $cc, \OCA\Mail\AddressList $bcc, ?string $subject): array {
		$headers = [
			'From' => $from->toHorde(),
			'To' => $to->toHorde(),
			'Subject' => $subject,
		];

		if (count($cc) > 0) {
			$headers['Cc'] = $cc->toHorde();
		}
		if (count($bcc) > 0) {
			$headers['Bcc'] = $bcc->toHorde();
		}

		return $headers;
	}
}
