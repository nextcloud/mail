<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Mail\Controller;

use OCA\Mail\Contracts\IDkimService;
use OCA\Mail\Db\LocalMessage;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Exception\SmimeDecryptException;
use OCA\Mail\Exception\UploadException;
use OCA\Mail\Http\TrapError;
use OCA\Mail\IMAP\IMAPClientFactory;
use OCA\Mail\Model\SmimeData;
use OCA\Mail\ResponseDefinitions;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\AliasesService;
use OCA\Mail\Service\Attachment\AttachmentService;
use OCA\Mail\Service\Attachment\UploadedFile;
use OCA\Mail\Service\ItineraryService;
use OCA\Mail\Service\MailManager;
use OCA\Mail\Service\OutboxService;
use OCA\Mail\Service\TrustedSenderService;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\ApiRoute;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\UserRateLimit;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\OCSController;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\IRequest;
use OCP\IURLGenerator;
use Psr\Log\LoggerInterface;
use Throwable;
use function array_map;
use function array_merge;

/**
 * @psalm-import-type MailMessageApiResponse from ResponseDefinitions
 * @psalm-import-type MailMessageApiAttachment from ResponseDefinitions
 */
class MessageApiController extends OCSController {

	private ?string $userId;

	public function __construct(
		string $appName,
		?string $userId,
		IRequest $request,
		private AccountService $accountService,
		private AliasesService $aliasesService,
		private AttachmentService $attachmentService,
		private OutboxService $outboxService,
		private MailManager $mailManager,
		private IMAPClientFactory $clientFactory,
		private LoggerInterface $logger,
		private ITimeFactory $time,
		private IURLGenerator $urlGenerator,
		private IDkimService $dkimService,
		private ItineraryService $itineraryService,
		private TrustedSenderService $trustedSenderService,
	) {
		parent::__construct($appName, $request);
		$this->userId = $userId;
	}

	/**
	 * Send an email though a mail account that has been configured with Nextcloud Mail
	 *
	 * @param int $accountId The mail account to use for SMTP
	 * @param string $fromEmail The "From" email address or alias email address
	 * @param string $subject The subject
	 * @param string $body The message body
	 * @param bool $isHtml If the message body contains HTML
	 * @param list<array{ label?: string, email: string}> $to An array of "To" recipients in the format ['label' => 'Name', 'email' => 'Email Address'] or ['email' => 'Email Address']
	 * @param list<array{ label?: string, email: string}> $cc An optional array of 'CC' recipients in the format ['label' => 'Name', 'email' => 'Email Address'] or ['email' => 'Email Address']
	 * @param list<array{ label?: string, email: string}> $bcc An optional array of 'BCC' recipients in the format ['label' => 'Name', 'email' => 'Email Address'] or ['email' => 'Email Address']
	 * @param ?string $references An optional string of an RFC2392 "message-id" to set the "Reply-To" and "References" header on sending
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_ACCEPTED|Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, string, array{}>
	 *
	 * 200: The email was sent
	 * 202: The email was sent but could not be copied to the 'Sent' mailbox
	 * 202: The email was accepted but not sent by the SMTP server and will be automatically retried
	 * 400: No recipients
	 * 400: Recipient fromat invalid
	 * 400: A recipient array contained no email addresse
	 * 400: Recipient email address malformed
	 * 400: Message could not be processed
	 * 403: No "Sent" mailbox set for account
	 * 404: User was not logged in
	 * 404: Account not found
	 * 404: Alias email not found
	 * 500: Attachments could not be processed
	 * 500: SMTP error
	 */
	#[ApiRoute(verb: 'POST', url: '/message/send')]
	#[UserRateLimit(limit: 5, period: 100)]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function send(
		int $accountId,
		string $fromEmail,
		string $subject,
		string $body,
		bool $isHtml,
		array $to,
		array $cc = [],
		array $bcc = [],
		?string $references = null,
	): DataResponse {
		if ($this->userId === null) {
			return new DataResponse('Account not found.', Http::STATUS_NOT_FOUND);
		}

		try {
			$mailAccount = $this->accountService->find($this->userId, $accountId);
		} catch (ClientException $e) {
			$this->logger->error("Mail account #$accountId not found", ['exception' => $e]);
			return new DataResponse('Account not found.', Http::STATUS_NOT_FOUND);
		}

		if ($fromEmail !== $mailAccount->getEmail()) {
			try {
				$alias = $this->aliasesService->findByAliasAndUserId($fromEmail, $this->userId);
			} catch (DoesNotExistException $e) {
				$this->logger->error("Alias $fromEmail for mail account $accountId not found", ['exception' => $e]);
				// Cannot send from this email as it is not configured as an alias
				return new DataResponse("Could not find alias $fromEmail. Please check the logs.", Http::STATUS_NOT_FOUND);
			}
		}

		if (empty($to)) {
			return new DataResponse('Recipients cannot be empty.', Http::STATUS_BAD_REQUEST);
		}


		try {
			$messageAttachments = $this->handleAttachments();
		} catch (UploadException $e) {
			return new DataResponse('Could not convert attachment(s) to local attachment(s). Please check the logs.', Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		$message = new LocalMessage();
		$message->setType(LocalMessage::TYPE_OUTGOING);
		$message->setAccountId($accountId);
		$message->setSubject($subject);
		if ($isHtml) {
			$message->setBodyPlain(null);
			$message->setBodyHtml($body);
			$message->setHtml(true);
		} else {
			$message->setBodyPlain($body);
			$message->setBodyHtml(null);
			$message->setHtml(false);
		}
		$message->setEditorBody($body);
		$message->setSendAt($this->time->getTime());
		$message->setType(LocalMessage::TYPE_OUTGOING);

		if (isset($alias)) {
			$message->setAliasId($alias->getId());
		}
		if (!empty($references)) {
			$message->setInReplyToMessageId($references);
		}
		if (!empty($messageAttachments)) {
			$message->setAttachments($messageAttachments);
		}

		$recipients = array_merge($to, $cc, $bcc);
		foreach ($recipients as $recipient) {
			if (!is_array($recipient)) {
				return new DataResponse('Recipient address must be an array.', Http::STATUS_BAD_REQUEST);
			}

			if (!isset($recipient['email'])) {
				return new DataResponse('Recipient address must contain an email address.', Http::STATUS_BAD_REQUEST);
			}

			$mightBeValidEmail = filter_var($recipient['email'], FILTER_VALIDATE_EMAIL);
			if ($mightBeValidEmail === false) {
				$email = $recipient['email'];
				return new DataResponse("Email address $email not valid.", Http::STATUS_BAD_REQUEST);
			}
		}

		$localAttachments = array_map(static function ($messageAttachment) {
			return ['type' => 'local', 'id' => $messageAttachment->getId()];
		}, $messageAttachments);
		$localMessage = $this->outboxService->saveMessage($mailAccount, $message, $to, $cc, $bcc, $localAttachments);

		try {
			$localMessage = $this->outboxService->sendMessage($localMessage, $mailAccount);
		} catch (ServiceException $e) {
			$this->logger->error('Processing error: could not send message', ['exception' => $e]);
			return new DataResponse('Processing error: could not send message. Please check the logs', Http::STATUS_BAD_REQUEST);
		} catch (Throwable $e) {
			$this->logger->error('SMTP error: could not send message', ['exception' => $e]);
			return new DataResponse('Fatal SMTP error: could not send message, and no resending is possible. Please check the mail server logs.', Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		return match ($localMessage->getStatus()) {
			LocalMessage::STATUS_PROCESSED => new DataResponse('', Http::STATUS_OK),
			LocalMessage::STATUS_NO_SENT_MAILBOX => new DataResponse('Configuration error: Cannot send message without sent mailbox.', Http::STATUS_FORBIDDEN),
			LocalMessage::STATUS_SMPT_SEND_FAIL => new DataResponse('SMTP error: could not send message. Message sending will be retried. Please check the logs.', Http::STATUS_INTERNAL_SERVER_ERROR),
			LocalMessage::STATUS_IMAP_SENT_MAILBOX_FAIL => new DataResponse('Email was sent but could not be copied to sent mailbox. Copying will be retried. Please check the logs.', Http::STATUS_ACCEPTED),
			default => new DataResponse('An error occured. Please check the logs.', Http::STATUS_INTERNAL_SERVER_ERROR),
		};
	}

	/**
	 * Get a mail message with its metadata
	 *
	 * @param int $id the message id
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_PARTIAL_CONTENT, MailMessageApiResponse, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, string, array{}>
	 *
	 * 200: Message found
	 * 206: Message could not be decrypted, no "body" data returned
	 * 404: User was not logged in
	 * 404: Message, Account or Mailbox not found
	 * 500: Could not connect to IMAP server
	 */
	#[BruteForceProtection('mailGetMessage')]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function get(int $id): DataResponse {
		if ($this->userId === null) {
			return new DataResponse('Account not found.', Http::STATUS_NOT_FOUND);
		}

		try {
			$message = $this->mailManager->getMessage($this->userId, $id);
			$mailbox = $this->mailManager->getMailbox($this->userId, $message->getMailboxId());
			$account = $this->accountService->find($this->userId, $mailbox->getAccountId());
		} catch (ClientException|DoesNotExistException $e) {
			$this->logger->error('Message, Account or Mailbox not found', ['exception' => $e->getMessage()]);
			return new DataResponse('Account not found.', Http::STATUS_NOT_FOUND);
		}

		$loadBody = true;
		$client = $this->clientFactory->getClient($account);
		try {
			$imapMessage = $this->mailManager->getImapMessage(
				$client,
				$account,
				$mailbox,
				$message->getUid(),
				true
			);
		} catch (ServiceException $e) {
			$this->logger->error('Could not connect to IMAP server', ['exception' => $e->getMessage()]);
			return new DataResponse('Could not connect to IMAP server. Please check your logs.', Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (SmimeDecryptException $e) {
			$this->logger->warning('Message could not be decrypted', ['exception' => $e->getMessage()]);
			$loadBody = false;
			$imapMessage = $this->mailManager->getImapMessage(
				$client,
				$account,
				$mailbox,
				$message->getUid()
			);
		} finally {
			$client->logout();
		}

		$json = $imapMessage->getFullMessage($id, $loadBody);
		$itineraries = $this->itineraryService->getCached($account, $mailbox, $message->getUid());
		if ($itineraries) {
			$json['itineraries'] = $itineraries->jsonSerialize();
		}
		$json['attachments'] = array_map(function ($a) use ($id) {
			return $this->enrichDownloadUrl(
				$id,
				$a
			);
		}, $json['attachments']);
		$json['id'] = $message->getId();
		$json['isSenderTrusted'] = $this->trustedSenderService->isSenderTrusted($this->userId, $message);

		$smimeData = new SmimeData();
		$smimeData->setIsEncrypted($message->isEncrypted() || $imapMessage->isEncrypted());
		if ($imapMessage->isSigned()) {
			$smimeData->setIsSigned(true);
			$smimeData->setSignatureIsValid($imapMessage->isSignatureValid());
		}
		$json['smime'] = $smimeData->jsonSerialize();

		$dkimResult = $this->dkimService->getCached($account, $mailbox, $message->getUid());
		if (is_bool($dkimResult)) {
			$json['dkimValid'] = $dkimResult;
		}

		$json['rawUrl'] = $this->urlGenerator->linkToOCSRouteAbsolute('mail.messageApi.getRaw', ['id' => $id]);

		if (!$loadBody) {
			return new DataResponse($json, Http::STATUS_PARTIAL_CONTENT);
		}

		return new DataResponse($json, Http::STATUS_OK);
	}

	/**
	 * Get the raw rfc2822 email
	 *
	 * @param int $id the id of the message
	 * @return DataResponse<Http::STATUS_OK, ?string, array{}>|DataResponse<Http::STATUS_NOT_FOUND, string, array{}>
	 *
	 * 200: Message found
	 * 404: User was not logged in
	 * 404: Message, Account or Mailbox not found
	 * 404: Could not find message on IMAP
	 */
	#[BruteForceProtection('mailGetRawMessage')]
	#[NoAdminRequired]
	#[NoCSRFRequired]
	public function getRaw(int $id): DataResponse {
		if ($this->userId === null) {
			return new DataResponse('Account not found.', Http::STATUS_NOT_FOUND);
		}

		try {
			$message = $this->mailManager->getMessage($this->userId, $id);
			$mailbox = $this->mailManager->getMailbox($this->userId, $message->getMailboxId());
			$account = $this->accountService->find($this->userId, $mailbox->getAccountId());
		} catch (ClientException|DoesNotExistException $e) {
			$this->logger->error('Message, Account or Mailbox not found', ['exception' => $e->getMessage()]);
			return new DataResponse('Message, Account or Mailbox not found', Http::STATUS_NOT_FOUND);
		}

		$client = $this->clientFactory->getClient($account);
		try {
			$source = $this->mailManager->getSource(
				$client,
				$account,
				$mailbox->getName(),
				$message->getUid()
			);
		} catch (ServiceException $e) {
			$this->logger->error('Message not found on IMAP, or mail server went away', ['exception' => $e->getMessage()]);
			return new DataResponse('Message not found', Http::STATUS_NOT_FOUND);
		} finally {
			$client->logout();
		}

		return new DataResponse($source, Http::STATUS_OK);
	}

	/**
	 * @param int $id the id of the message
	 * @param array $attachment
	 *
	 * @return array
	 */
	private function enrichDownloadUrl(int $id, array $attachment): array {
		$downloadUrl = $this->urlGenerator->linkToOCSRouteAbsolute('mail.messageApi.getAttachment',
			[
				'id' => $id,
				'attachmentId' => $attachment['id'],
			]);
		$attachment['downloadUrl'] = $downloadUrl;
		return $attachment;
	}

	/**
	 * Get a mail message's attachments
	 *
	 * @param int $id the mail id
	 * @param string $attachmentId the attachment id
	 * @return DataResponse<Http::STATUS_OK, MailMessageApiAttachment, array{}>|DataResponse<Http::STATUS_NOT_FOUND|Http::STATUS_INTERNAL_SERVER_ERROR, string, array{}>
	 *
	 * 200: Message found
	 * 404: User was not logged in
	 * 404: Message, Account or Mailbox not found
	 * 404: Could not find attachment
	 * 500: Could not process attachment
	 *
	 */
	#[NoCSRFRequired]
	#[NoAdminRequired]
	#[TrapError]
	public function getAttachment(int $id, string $attachmentId): DataResponse {
		try {
			$message = $this->mailManager->getMessage($this->userId, $id);
			$mailbox = $this->mailManager->getMailbox($this->userId, $message->getMailboxId());
			$account = $this->accountService->find($this->userId, $mailbox->getAccountId());
		} catch (DoesNotExistException|ClientException $e) {
			return new DataResponse('Message, Account or Mailbox not found', Http::STATUS_NOT_FOUND);
		}

		try {
			$attachment = $this->mailManager->getMailAttachment(
				$account,
				$mailbox,
				$message,
				$attachmentId,
			);
		} catch (\Horde_Imap_Client_Exception_NoSupportExtension|\Horde_Imap_Client_Exception|\Horde_Mime_Exception $e) {
			$this->logger->error('Error when trying to process the attachment', ['exception' => $e]);
			return new DataResponse('Error when trying to process the attachment', Http::STATUS_INTERNAL_SERVER_ERROR);
		} catch (ServiceException|DoesNotExistException $e) {
			$this->logger->error('Could not find attachment', ['exception' => $e]);
			return new DataResponse('Could not find attachment', Http::STATUS_NOT_FOUND);
		}

		// Body party and embedded messages do not have a name
		if ($attachment->getName() === null) {
			return new DataResponse([
				'name' => $attachmentId . '.eml',
				'mime' => $attachment->getType(),
				'size' => $attachment->getSize(),
				'content' => $attachment->getContent()
			]);
		}

		return new DataResponse([
			'name' => $attachment->getName(),
			'mime' => $attachment->getType(),
			'size' => $attachment->getSize(),
			'content' => $attachment->getContent()
		]);
	}

	/**
	 * @return array
	 * @throws UploadException
	 */
	private function handleAttachments(): array {
		$fileAttachments = $this->request->getUploadedFile('attachments');
		$hasAttachments = isset($fileAttachments['name']);
		if (!$hasAttachments) {
			return [];
		}

		$messageAttachments = [];
		foreach ($fileAttachments['name'] as $attachmentKey => $attachmentName) {
			$filedata = [
				'name' => $attachmentName,
				'type' => $fileAttachments['type'][$attachmentKey],
				'size' => $fileAttachments['size'][$attachmentKey],
				'tmp_name' => $fileAttachments['tmp_name'][$attachmentKey],
			];
			$file = new UploadedFile($filedata);
			try {
				$attachment = $this->attachmentService->addFile($this->userId, $file);
				$messageAttachments[] = $attachment;
			} catch (UploadException $e) {
				$this->logger->error('Could not convert attachment to local attachment.', ['exception' => $e]);
				foreach ($messageAttachments as $attachment) {
					// Handle possible dangling local attachments
					$this->attachmentService->deleteAttachment($this->userId, $attachment->getId());
				}
				throw $e;
			}
		}

		return $messageAttachments;
	}
}
