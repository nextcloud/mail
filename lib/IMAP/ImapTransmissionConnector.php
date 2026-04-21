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
use Horde_Mail_Rfc822_List;
use Horde_Mail_Transport_Null;
use Horde_Mail_Transport_Smtphorde;
use Horde_Mime_Exception;
use Horde_Mime_Headers;
use Horde_Mime_Headers_Addresses;
use Horde_Mime_Headers_ContentParam_ContentType;
use Horde_Mime_Headers_Date;
use Horde_Mime_Headers_MessageId;
use Horde_Mime_Headers_Subject;
use Horde_Mime_Mail;
use Horde_Mime_Mdn;
use Horde_Mime_Part;
use Horde_Smtp_Exception;
use OCA\Mail\Account;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Contracts\ITransmissionConnector;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Db\Mailbox;
use OCA\Mail\Db\MailboxMapper;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Recipient;
use OCA\Mail\Exception\AttachmentNotFoundException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SmimeEncryptException;
use OCA\Mail\Exception\SmimeSignException;
use OCA\Mail\Protocol\ProtocolFactory;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\MimeMessage;
use OCA\Mail\Service\SmimeService;
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
		private SmimeService $smimeService,
		private AttachmentService $attachmentService,
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
		[$to, $cc, $bcc, $attachments] = $this->getRecipientsAndAttachments($message);

		$name = $account->getName();
		$emailAddress = $account->getEMailAddress();

		$aliasId = $message->getAliasId();
		if ($aliasId !== null) {
			try {
				$alias = $this->aliasesService->find($aliasId, $account->getUserId());
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
			$part = $this->buildAttachmentMimePart($account, $attachment);
			if ($part !== null) {
				$attachmentParts[] = $part;
			}
		}

		$transport = $this->smtpClientFactory->create($account);

		$fromHorde = $from->toHorde();
		$toHorde = $to->toHorde();
		$ccHorde = $cc->toHorde();
		$bccHorde = $bcc->toHorde();

		// Build full headers for the Sent-folder copy (FCC), including Bcc so the
		// sender can see who was blind-copied when reviewing sent mail — the same
		// approach used by Horde IMP and other clients (Evolution, Thunderbird).
		$fccHeaders = new Horde_Mime_Headers();
		$fccHeaders->addHeaderOb(Horde_Mime_Headers_Date::create());
		$fccHeaders->addHeaderOb(Horde_Mime_Headers_MessageId::create());
		$fccHeaders->addHeaderOb(new Horde_Mime_Headers_Addresses('From', $fromHorde));
		$fccHeaders->addHeaderOb(new Horde_Mime_Headers_Addresses('To', $toHorde));
		if (count($cc) > 0) {
			$fccHeaders->addHeaderOb(new Horde_Mime_Headers_Addresses('Cc', $ccHorde));
		}
		if (count($bcc) > 0) {
			$fccHeaders->addHeaderOb(new Horde_Mime_Headers_Addresses('Bcc', $bccHorde));
		}
		if ($message->getSubject() !== null) {
			$fccHeaders->addHeader('Subject', $message->getSubject());
		}
		// The table (oc_local_messages) currently only allows for a single reply to message id
		// but we already set the 'references' header for an email so we could support multiple references
		// Get the previous message and then concatenate all its "References" message ids with this one
		if (($inReplyTo = $message->getInReplyToMessageId()) !== null) {
			$fccHeaders->addHeader('References', $inReplyTo);
			$fccHeaders->addHeader('In-Reply-To', $inReplyTo);
		}
		if ($message->getRequestMdn()) {
			$fccHeaders->addHeaderOb(new Horde_Mime_Headers_Addresses(Horde_Mime_Mdn::MDN_HEADER, $fromHorde));
		}

		// For SMTP delivery: strip Bcc so it never appears in the transmitted
		// message (RFC 5321). All three recipient lists are passed as SMTP
		// envelope recipients so every addressee still receives the mail.
		$sendHeaders = clone $fccHeaders;
		$sendHeaders->removeHeader('Bcc');

		$smtpRecipients = new Horde_Mail_Rfc822_List();
		$smtpRecipients->add($toHorde);
		$smtpRecipients->add($ccHorde);
		$smtpRecipients->add($bccHorde);
		$smtpRecipients->unique();

		$mimePart = $this->mimeMessage->build(
			$message->getBodyPlain(),
			$message->getBodyHtml(),
			$message->isPgpMime() === true,
			$attachmentParts,
		);

		try {
			$mimePart = $this->applySmimeSignature($message, $account, $mimePart);
			$mimePart = $this->applySmimeEncryption($message, $to, $cc, $bcc, $account, $mimePart);
		} catch (ServiceException $e) {
			$this->logger->error($e->getMessage(), ['exception' => $e]);
			return;
		}

		// Send the message
		try {
			$mimePart->send($smtpRecipients->writeAddress(), $sendHeaders, $transport);
			$message->setRaw($mimePart->toString([
				'encode' => Horde_Mime_Part::ENCODE_7BIT | Horde_Mime_Part::ENCODE_8BIT | Horde_Mime_Part::ENCODE_BINARY,
				'headers' => $fccHeaders,
				'stream' => false,
			]));
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
				$message->setRaw($mimePart->toString([
					'encode' => Horde_Mime_Part::ENCODE_7BIT | Horde_Mime_Part::ENCODE_8BIT | Horde_Mime_Part::ENCODE_BINARY,
					'headers' => $fccHeaders,
					'stream' => false,
				]));
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
		[$to, $cc, $bcc, $attachments] = $this->getRecipientsAndAttachments($message);

		$perfLogger = $this->performanceLogger->start('save message to IMAP mailbox');

		$from = Address::fromRaw($account->getName(), $account->getEMailAddress());

		$headers = [
			'From' => $from->toHorde(),
			'To' => $to->toHorde(),
			'Subject' => $message->getSubject(),
		];
		if (count($cc) > 0) {
			$headers['Cc'] = $cc->toHorde();
		}
		if (count($bcc) > 0) {
			$headers['Bcc'] = $bcc->toHorde();
		}

		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);
		foreach ($attachments as $attachment) {
			$part = $this->buildAttachmentMimePart($account, $attachment);
			if ($part !== null) {
				$mail->addMimePart($part);
			}
		}
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
	 * @return array{0: AddressList, 1: AddressList, 2: AddressList, 3: array}
	 */
	private function getRecipientsAndAttachments(LocalMessage $message): array {
		return [
			$this->transmissionService->getAddressList($message, Recipient::TYPE_TO),
			$this->transmissionService->getAddressList($message, Recipient::TYPE_CC),
			$this->transmissionService->getAddressList($message, Recipient::TYPE_BCC),
			$this->transmissionService->getAttachments($message),
		];
	}

	private function buildAttachmentMimePart(Account $account, array $attachment): ?Horde_Mime_Part {
		if (!isset($attachment['id'])) {
			$this->logger->warning('ignoring local attachment because its id is unknown');
			return null;
		}

		try {
			[$localAttachment, $file] = $this->attachmentService->getAttachment($account->getMailAccount()->getUserId(), (int)$attachment['id']);
			$part = new Horde_Mime_Part();
			$part->setCharset('us-ascii');

			if ($localAttachment->isDispositionAttachmentOrInline()) {
				$part->setDisposition($localAttachment->getDisposition());
				/*
				 * Setting a name implicitly adds a Content-Disposition header in Horde,
				 * which would override the intentional omission. Only set it for attachment/inline dispositions.
				 */
				$part->setName($localAttachment->getFileName());
			}

			if ($localAttachment->getContentId() !== null) {
				$part->setContentId($localAttachment->getContentId());
			}

			$part->setContents($file->getContent());
			/*
			 * Horde_Mime_Part.setType takes the mimetype (e.g. text/calendar)
			 * and discards additional parameters (like method=REQUEST).
			 *
			 * $part->setType('text/calendar; method=REQUEST')
			 * $part->getType() => text/calendar
			 */
			$contentTypeHeader = Horde_Mime_Headers_ContentParam_ContentType::create();
			$contentTypeHeader->decode($localAttachment->getMimeType());

			$part->setType($contentTypeHeader->value);
			foreach ($contentTypeHeader->params as $label => $data) {
				$part->setContentTypeParameter($label, $data);
			}

			return $part;
		} catch (AttachmentNotFoundException $e) {
			$this->logger->warning('Ignoring local attachment because it does not exist', ['exception' => $e]);
			return null;
		}
	}

	/**
	 * @throws ServiceException
	 */
	private function applySmimeSignature(LocalMessage $localMessage, Account $account, Horde_Mime_Part $mimePart): Horde_Mime_Part {
		if ($localMessage->getSmimeSign()) {
			if ($localMessage->getSmimeCertificateId() === null) {
				$localMessage->setStatus(LocalMessage::STATUS_SMIME_SIGN_NO_CERT_ID);
				throw new ServiceException('Could not send message: Requested S/MIME signature without certificate id');
			}

			try {
				$certificate = $this->smimeService->findCertificate(
					$localMessage->getSmimeCertificateId(),
					$account->getUserId(),
				);
				$mimePart = $this->smimeService->signMimePart($mimePart, $certificate);
			} catch (DoesNotExistException $e) {
				$localMessage->setStatus(LocalMessage::STATUS_SMIME_SIGN_CERT);
				throw new ServiceException(
					'Could not send message: Certificate does not exist: ' . $e->getMessage(),
					$e->getCode(),
					$e,
				);
			} catch (SmimeSignException|ServiceException $e) {
				$localMessage->setStatus(LocalMessage::STATUS_SMIME_SIGN_FAIL);
				throw new ServiceException(
					'Could not send message: Failed to sign MIME part: ' . $e->getMessage(),
					$e->getCode(),
					$e,
				);
			}
		}
		return $mimePart;
	}

	/**
	 * @throws ServiceException
	 */
	private function applySmimeEncryption(LocalMessage $localMessage, AddressList $to, AddressList $cc, AddressList $bcc, Account $account, Horde_Mime_Part $mimePart): Horde_Mime_Part {
		if ($localMessage->getSmimeEncrypt()) {
			if ($localMessage->getSmimeCertificateId() === null) {
				$localMessage->setStatus(LocalMessage::STATUS_SMIME_ENCRYPT_NO_CERT_ID);
				throw new ServiceException('Could not send message: Requested S/MIME signature without certificate id');
			}

			try {
				$addressList = $to
					->merge($cc)
					->merge($bcc);
				$certificates = $this->smimeService->findCertificatesByAddressList($addressList, $account->getUserId());

				$senderCertificate = $this->smimeService->findCertificate($localMessage->getSmimeCertificateId(), $account->getUserId());
				$certificates[] = $senderCertificate;

				$mimePart = $this->smimeService->encryptMimePart($mimePart, $certificates);
			} catch (DoesNotExistException $e) {
				$localMessage->setStatus(LocalMessage::STATUS_SMIME_ENCRYPT_CERT);
				throw new ServiceException(
					'Could not send message: Certificate does not exist: ' . $e->getMessage(),
					$e->getCode(),
					$e,
				);
			} catch (SmimeEncryptException|ServiceException $e) {
				$localMessage->setStatus(LocalMessage::STATUS_SMIME_ENCRYT_FAIL);
				throw new ServiceException(
					'Could not send message: Failed to encrypt MIME part: ' . $e->getMessage(),
					$e->getCode(),
					$e,
				);
			}
		}
		return $mimePart;
	}
}
