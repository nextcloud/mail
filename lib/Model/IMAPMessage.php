<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Model;

use Exception;
use Horde_Imap_Client;
use Horde_Imap_Client_DateTime;
use Horde_Mime_Headers_MessageId;
use Horde_Mime_Part;
use JsonSerializable;
use OCA\Mail\AddressList;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Tag;
use OCA\Mail\ResponseDefinitions;
use OCA\Mail\Service\Html;
use OCP\Files\File;
use OCP\Files\SimpleFS\ISimpleFile;
use ReturnTypeWillChange;
use function in_array;
use function mb_convert_encoding;
use function mb_strcut;
use function trim;

/**
 * @psalm-import-type MailIMAPFullMessage from ResponseDefinitions
 */
class IMAPMessage implements IMessage, JsonSerializable {
	use ConvertAddresses;

	public function __construct(
		private int $messageId,
		private string $realMessageId,
		/** @var string[] */
		private array $flags,
		private AddressList $from,
		private AddressList $to,
		private AddressList $cc,
		private AddressList $bcc,
		private AddressList $replyTo,
		private string $subject,
		public string $plainMessage,
		public string $htmlMessage,
		private bool $hasHtmlMessage,
		public array $attachments,
		public array $inlineAttachments,
		private bool $hasAttachments,
		public array $scheduling,
		private Horde_Imap_Client_DateTime $imapDate,
		private string $rawReferences,
		private string $dispositionNotificationTo,
		private bool $hasDkimSignature,
		private array $phishingDetails,
		private ?string $unsubscribeUrl,
		private bool $isOneClickUnsubscribe,
		private ?string $unsubscribeMailto,
		private string $rawInReplyTo,
		private bool $isEncrypted,
		private bool $isSigned,
		private bool $signatureIsValid,
		private Html $htmlService,
		private bool $isPgpMimeEncrypted,
	) {
	}

	public static function generateMessageId(): string {
		return Horde_Mime_Headers_MessageId::create('nextcloud-mail-generated')->value;
	}

	public function getUid(): int {
		return $this->messageId;
	}

	/**
	 * @deprecated  Seems unused
	 */
	#[\Override]
	public function getFlags(): array {
		return [
			'seen' => in_array(Horde_Imap_Client::FLAG_SEEN, $this->flags),
			'flagged' => in_array(Horde_Imap_Client::FLAG_FLAGGED, $this->flags),
			'answered' => in_array(Horde_Imap_Client::FLAG_ANSWERED, $this->flags),
			'deleted' => in_array(Horde_Imap_Client::FLAG_DELETED, $this->flags),
			'draft' => in_array(Horde_Imap_Client::FLAG_DRAFT, $this->flags),
			'forwarded' => in_array(Horde_Imap_Client::FLAG_FORWARDED, $this->flags),
			'hasAttachments' => $this->hasAttachments,
			'mdnsent' => in_array(Horde_Imap_Client::FLAG_MDNSENT, $this->flags, true),
			'important' => in_array(Tag::LABEL_IMPORTANT, $this->flags, true)
		];
	}

	/**
	 * @deprecated  Seems unused
	 * @param string[] $flags
	 *
	 * @throws Exception
	 */
	#[\Override]
	public function setFlags(array $flags): never {
		// TODO: implement
		throw new Exception('Not implemented');
	}

	private function getRawReferences(): string {
		return $this->rawReferences;
	}

	private function getRawInReplyTo(): string {
		return $this->rawInReplyTo;
	}

	public function getDispositionNotificationTo(): string {
		return $this->dispositionNotificationTo;
	}

	#[\Override]
	public function getFrom(): AddressList {
		return $this->from;
	}

	/**
	 *
	 * @throws Exception
	 *
	 */
	#[\Override]
	public function setFrom(AddressList $from): never {
		throw new Exception('IMAP message is immutable');
	}

	#[\Override]
	public function getTo(): AddressList {
		return $this->to;
	}

	/**
	 *
	 * @throws Exception
	 *
	 */
	#[\Override]
	public function setTo(AddressList $to): never {
		throw new Exception('IMAP message is immutable');
	}

	#[\Override]
	public function getCC(): AddressList {
		return $this->cc;
	}

	/**
	 *
	 * @throws Exception
	 *
	 */
	#[\Override]
	public function setCC(AddressList $cc): never {
		throw new Exception('IMAP message is immutable');
	}

	#[\Override]
	public function getBCC(): AddressList {
		return $this->bcc;
	}

	/**
	 *
	 * @throws Exception
	 *
	 */
	#[\Override]
	public function setBcc(AddressList $bcc): never {
		throw new Exception('IMAP message is immutable');
	}

	#[\Override]
	public function getMessageId(): string {
		return $this->realMessageId;
	}

	#[\Override]
	public function getSubject(): string {
		return $this->subject;
	}

	/**
	 *
	 * @throws Exception
	 *
	 */
	#[\Override]
	public function setSubject(string $subject): never {
		throw new Exception('IMAP message is immutable');
	}

	public function getSentDate(): Horde_Imap_Client_DateTime {
		return $this->imapDate;
	}

	/**
	 * @return MailIMAPFullMessage
	 */
	public function getFullMessage(int $id, bool $loadBody = true): array {
		$mailBody = $this->plainMessage;
		$data = $this->jsonSerialize();

		if ($this->hasHtmlMessage && $loadBody) {
			$data['body'] = $this->getHtmlBody($id);
		}

		if ($this->hasHtmlMessage) {
			$data['hasHtmlBody'] = true;
			$data['attachments'] = $this->attachments;
			return $data;
		}

		$mailBody = $this->htmlService->convertLinks($mailBody);
		[$mailBody, $signature] = $this->htmlService->parseMailBody($mailBody);
		$data['signature'] = $signature;
		$data['attachments'] = array_merge($this->attachments, $this->inlineAttachments);
		if ($loadBody) {
			$data['body'] = $mailBody;
		}
		return $data;
	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'uid' => $this->getUid(),
			'messageId' => $this->getMessageId(),
			'from' => $this->getFrom()->jsonSerialize(),
			'to' => $this->getTo()->jsonSerialize(),
			'replyTo' => $this->getReplyTo()->jsonSerialize(),
			'cc' => $this->getCC()->jsonSerialize(),
			'bcc' => $this->getBCC()->jsonSerialize(),
			'subject' => $this->getSubject(),
			'dateInt' => $this->getSentDate()->getTimestamp(),
			'flags' => $this->getFlags(),
			'hasHtmlBody' => $this->hasHtmlMessage,
			'dispositionNotificationTo' => $this->getDispositionNotificationTo(),
			'hasDkimSignature' => $this->hasDkimSignature,
			'phishingDetails' => $this->phishingDetails,
			'unsubscribeUrl' => $this->unsubscribeUrl,
			'isOneClickUnsubscribe' => $this->isOneClickUnsubscribe,
			'unsubscribeMailto' => $this->unsubscribeMailto,
			'scheduling' => $this->scheduling,
			'isPgpMimeEncrypted' => $this->isPgpMimeEncrypted,
		];
	}

	public function getHtmlBody(int $id): string {
		return $this->htmlService->sanitizeHtmlMailBody($this->htmlMessage, [
			'id' => $id,
		], function ($cid) {
			$match = array_filter($this->inlineAttachments,
				static fn (array $a): bool => $a['cid'] === $cid);
			$match = array_shift($match);
			if ($match === null) {
				return null;
			}
			return $match['id'];
		});
	}

	public function getPlainBody(): string {
		return $this->plainMessage;
	}

	#[\Override]
	public function getContent(): string {
		return $this->getPlainBody();
	}

	#[\Override]
	public function setContent(string $content): never {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return Horde_Mime_Part[]
	 */
	#[\Override]
	public function getAttachments(): array {
		throw new Exception('not implemented');
	}

	#[\Override]
	public function addRawAttachment(string $name, string $content): void {
		throw new Exception('IMAP message is immutable');
	}

	#[\Override]
	public function addEmbeddedMessageAttachment(string $name, string $content): void {
		throw new Exception('IMAP message is immutable');
	}

	#[\Override]
	public function addAttachmentFromFiles(File $file): never {
		throw new Exception('IMAP message is immutable');
	}

	#[\Override]
	public function addLocalAttachment(LocalAttachment $attachment, ISimpleFile $file): never {
		throw new Exception('IMAP message is immutable');
	}

	#[\Override]
	public function getInReplyTo(): never {
		throw new Exception('not implemented');
	}

	#[\Override]
	public function setInReplyTo(string $id): never {
		throw new Exception('not implemented');
	}

	#[\Override]
	public function getReplyTo(): AddressList {
		return $this->replyTo;
	}

	/**
	 *
	 * @throws Exception
	 *
	 */
	#[\Override]
	public function setReplyTo(AddressList $replyTo): never {
		throw new Exception('IMAP message is immutable');
	}

	public function isEncrypted(): bool {
		return $this->isEncrypted;
	}

	public function isSigned(): bool {
		return $this->isSigned;
	}

	public function isSignatureValid(): bool {
		return $this->signatureIsValid;
	}

	public function getUnsubscribeUrl(): ?string {
		return $this->unsubscribeUrl;
	}

	public function isOneClickUnsubscribe(): bool {
		return $this->isOneClickUnsubscribe;
	}

	public function isPgpMimeEncrypted(): bool {
		return $this->isPgpMimeEncrypted;
	}

	/**
	 * Cast all values from an IMAP message into the correct DB format
	 */
	public function toDbMessage(int $mailboxId, MailAccount $account): Message {
		$msg = new Message();

		$messageId = $this->getMessageId();
		$msg->setMessageId($messageId);

		// Sometimes the message ID is missing or invalid and therefore not set.
		// Then we create one and set it.
		if ($msg->getMessageId() === null || trim($msg->getMessageId()) === '') {
			$messageId = self::generateMessageId();
			$msg->setMessageId($messageId);
		}

		$msg->setUid($this->getUid());
		$msg->setRawReferences($this->getRawReferences());
		$msg->setThreadRootId($messageId);
		$msg->setInReplyTo($this->getRawInReplyTo());
		$msg->setMailboxId($mailboxId);
		$msg->setFrom($this->getFrom());
		$msg->setTo($this->getTo());
		$msg->setCc($this->getCc());
		$msg->setBcc($this->getBcc());
		$msg->setSubject(mb_strcut($this->getSubject(), 0, 255));
		$msg->setSentAt($this->getSentDate()->getTimestamp());

		$flags = $this->flags;
		$msg->setFlagAnswered(in_array(Horde_Imap_Client::FLAG_ANSWERED, $flags, true));
		$msg->setFlagDeleted(in_array(Horde_Imap_Client::FLAG_DELETED, $flags, true));
		$msg->setFlagDraft(in_array(Horde_Imap_Client::FLAG_DRAFT, $flags, true));
		$msg->setFlagFlagged(in_array(Horde_Imap_Client::FLAG_FLAGGED, $flags, true));
		$msg->setFlagSeen(in_array(Horde_Imap_Client::FLAG_SEEN, $flags, true));
		$msg->setFlagForwarded(in_array(Horde_Imap_Client::FLAG_FORWARDED, $flags, true));
		$msg->setFlagJunk(
			in_array(Horde_Imap_Client::FLAG_JUNK, $flags, true)
			|| in_array('junk', $flags, true)
		);
		$msg->setFlagNotjunk(in_array(Horde_Imap_Client::FLAG_NOTJUNK, $flags, true) || in_array('nonjunk', $flags, true));// While this is not a standard IMAP Flag, Thunderbird uses it to mark "not junk"
		// @todo remove this as soon as possible @link https://github.com/nextcloud/mail/issues/25
		$msg->setFlagImportant(in_array('$important', $flags, true) || in_array('$labelimportant', $flags, true) || in_array(Tag::LABEL_IMPORTANT, $flags, true));
		$msg->setFlagAttachments(false);
		$msg->setFlagMdnsent(in_array(Horde_Imap_Client::FLAG_MDNSENT, $flags, true));
		if ($this->scheduling !== []) {
			$msg->setImipMessage(true);
		}

		$allowed = [
			Horde_Imap_Client::FLAG_ANSWERED,
			Horde_Imap_Client::FLAG_FLAGGED,
			Horde_Imap_Client::FLAG_FORWARDED,
			Horde_Imap_Client::FLAG_DELETED,
			Horde_Imap_Client::FLAG_DRAFT,
			Horde_Imap_Client::FLAG_JUNK,
			Horde_Imap_Client::FLAG_NOTJUNK,
			'nonjunk', // While this is not a standard IMAP Flag, Thunderbird uses it to mark "not junk"
			Horde_Imap_Client::FLAG_MDNSENT,
			Horde_Imap_Client::FLAG_RECENT,
			Horde_Imap_Client::FLAG_SEEN,
		];

		// remove all standard IMAP flags from $filters
		$tags = array_filter($flags, static fn (string $flag): bool => in_array($flag, $allowed, true) === false);

		if (($tags === []) === true) {
			return $msg;
		}
		// cast all leftover $flags to be used as tags
		$msg->setTags($this->generateTagEntites($tags, $account->getUserId()));
		return $msg;
	}

	/**
	 * Build tag entities from keywords sent by IMAP
	 *
	 * Will use IMAP keyword '$xxx' to create a value for
	 * display_name like 'xxx'
	 *
	 * @link https://github.com/nextcloud/mail/issues/25
	 * @link https://github.com/nextcloud/mail/issues/5150
	 *
	 * @param string[] $tags
	 * @return Tag[]
	 */
	private function generateTagEntites(array $tags, string $userId): array {
		$t = [];
		foreach ($tags as $keyword) {
			if ($keyword === '$important' || $keyword === 'important' || $keyword === '$labelimportant') {
				$keyword = Tag::LABEL_IMPORTANT;
			}
			if ($keyword === '$labelwork') {
				$keyword = Tag::LABEL_WORK;
			}
			if ($keyword === '$labelpersonal') {
				$keyword = Tag::LABEL_PERSONAL;
			}
			if ($keyword === '$labeltodo') {
				$keyword = Tag::LABEL_TODO;
			}
			if ($keyword === '$labellater') {
				$keyword = Tag::LABEL_LATER;
			}

			$displayName = str_replace(['_', '$'], [' ', ''], $keyword);
			$displayName = strtoupper($displayName);
			$displayName = mb_convert_encoding($displayName, 'UTF-8', 'UTF7-IMAP');
			$displayName = strtolower($displayName);
			$displayName = ucwords($displayName);

			$keyword = mb_strcut($keyword, 0, 64);
			$displayName = mb_strcut($displayName, 0, 128);

			$tag = new Tag();
			$tag->setImapLabel($keyword);
			$tag->setDisplayName($displayName);
			$tag->setUserId($userId);
			$t[] = $tag;
		}
		return $t;
	}
}
