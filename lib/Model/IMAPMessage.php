<?php

declare(strict_types=1);

/**
 * @author Alexander Weidinger <alexwegoo@gmail.com>
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Christoph Wurst <wurst.christoph@gmail.com>
 * @author Jan-Christoph Borchardt <hey@jancborchardt.net>
 * @author Robin McCorkell <rmccorkell@karoshi.org.uk>
 * @author Thomas Mueller <thomas.mueller@tmit.eu>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
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
use OCA\Mail\Service\Html;
use OCP\Files\File;
use OCP\Files\SimpleFS\ISimpleFile;
use ReturnTypeWillChange;
use function in_array;
use function mb_convert_encoding;
use function mb_strcut;
use function trim;

class IMAPMessage implements IMessage, JsonSerializable {
	use ConvertAddresses;

	private Html $htmlService;

	/** @var string[] */
	private array $flags;

	private int $messageId;
	private string $realMessageId;
	private AddressList $from;
	private AddressList $to;
	private AddressList $cc;
	private AddressList $bcc;
	private AddressList $replyTo;
	private string $subject;
	public string $plainMessage;
	public string $htmlMessage;
	public array $attachments;
	public array $inlineAttachments;
	private bool $hasAttachments;
	public array $scheduling;
	private bool $hasHtmlMessage;
	private Horde_Imap_Client_DateTime $imapDate;
	private string $rawReferences;
	private string $dispositionNotificationTo;
	private string $rawInReplyTo;
	private bool $isEncrypted;
	private bool $isSigned;
	private bool $signatureIsValid;

	public function __construct(int $uid,
								string $messageId,
								array $flags,
								AddressList $from,
								AddressList $to,
								AddressList $cc,
								AddressList $bcc,
								AddressList $replyTo,
								string $subject,
								string $plainMessage,
								string $htmlMessage,
								bool $hasHtmlMessage,
								array $attachments,
								array $inlineAttachments,
								bool $hasAttachments,
								array $scheduling,
								Horde_Imap_Client_DateTime $imapDate,
								string $rawReferences,
								string $dispositionNotificationTo,
								string $rawInReplyTo,
								bool $isEncrypted,
								bool $isSigned,
								bool $signatureIsValid,
								Html $htmlService) {
		$this->messageId = $uid;
		$this->realMessageId = $messageId;
		$this->flags = $flags;
		$this->from = $from;
		$this->to = $to;
		$this->cc = $cc;
		$this->bcc = $bcc;
		$this->replyTo = $replyTo;
		$this->subject = $subject;
		$this->plainMessage = $plainMessage;
		$this->htmlMessage = $htmlMessage;
		$this->hasHtmlMessage = $hasHtmlMessage;
		$this->attachments = $attachments;
		$this->inlineAttachments = $inlineAttachments;
		$this->hasAttachments = $hasAttachments;
		$this->scheduling = $scheduling;
		$this->imapDate = $imapDate;
		$this->rawReferences = $rawReferences;
		$this->dispositionNotificationTo = $dispositionNotificationTo;
		$this->rawInReplyTo = $rawInReplyTo;
		$this->isEncrypted = $isEncrypted;
		$this->isSigned = $isSigned;
		$this->signatureIsValid = $signatureIsValid;
		$this->htmlService = $htmlService;
	}

	public static function generateMessageId(): string {
		return Horde_Mime_Headers_MessageId::create('nextcloud-mail-generated')->value;
	}

	/**
	 * @return int
	 */
	public function getUid(): int {
		return $this->messageId;
	}

	/**
	 * @deprecated  Seems unused
	 * @return array
	 */
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
	 *
	 * @return void
	 */
	public function setFlags(array $flags) {
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

	public function getFrom(): AddressList {
		return $this->from;
	}

	/**
	 * @param AddressList $from
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function setFrom(AddressList $from) {
		throw new Exception('IMAP message is immutable');
	}

	public function getTo(): AddressList {
		return $this->to;
	}

	/**
	 * @param AddressList $to
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function setTo(AddressList $to) {
		throw new Exception('IMAP message is immutable');
	}

	public function getCC(): AddressList {
		return $this->cc;
	}

	/**
	 * @param AddressList $cc
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function setCC(AddressList $cc) {
		throw new Exception('IMAP message is immutable');
	}

	public function getBCC(): AddressList {
		return $this->bcc;
	}

	/**
	 * @param AddressList $bcc
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function setBcc(AddressList $bcc) {
		throw new Exception('IMAP message is immutable');
	}

	public function getMessageId(): string {
		return $this->realMessageId;
	}

	public function getSubject(): string {
		return $this->subject;
	}

	/**
	 * @param string $subject
	 *
	 * @throws Exception
	 *
	 * @return void
	 */
	public function setSubject(string $subject) {
		throw new Exception('IMAP message is immutable');
	}

	public function getSentDate(): Horde_Imap_Client_DateTime {
		return $this->imapDate;
	}

	/**
	 * @param int $id
	 *
	 * @return array
	 */
	public function getFullMessage(int $id): array {
		$mailBody = $this->plainMessage;
		$data = $this->jsonSerialize();
		if ($this->hasHtmlMessage) {
			$data['hasHtmlBody'] = true;
			$data['body'] = $this->getHtmlBody($id);
			$data['attachments'] = $this->attachments;
		} else {
			$mailBody = $this->htmlService->convertLinks($mailBody);
			[$mailBody, $signature] = $this->htmlService->parseMailBody($mailBody);
			$data['body'] = $mailBody;
			$data['signature'] = $signature;
			$data['attachments'] = array_merge($this->attachments, $this->inlineAttachments);
		}

		return $data;
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'uid' => $this->getUid(),
			'messageId' => $this->getMessageId(),
			'from' => $this->getFrom()->jsonSerialize(),
			'to' => $this->getTo()->jsonSerialize(),
			'cc' => $this->getCC()->jsonSerialize(),
			'bcc' => $this->getBCC()->jsonSerialize(),
			'subject' => $this->getSubject(),
			'dateInt' => $this->getSentDate()->getTimestamp(),
			'flags' => $this->getFlags(),
			'hasHtmlBody' => $this->hasHtmlMessage,
			'dispositionNotificationTo' => $this->getDispositionNotificationTo(),
			'scheduling' => $this->scheduling,
		];
	}

	/**
	 * @param int $id
	 *
	 * @return string
	 */
	public function getHtmlBody(int $id): string {
		return $this->htmlService->sanitizeHtmlMailBody($this->htmlMessage, [
			'id' => $id,
		], function ($cid) {
			$match = array_filter($this->inlineAttachments,
				static function ($a) use ($cid) {
					return $a['cid'] === $cid;
				});
			$match = array_shift($match);
			if ($match === null) {
				return null;
			}
			return $match['id'];
		});
	}

	/**
	 * @return string
	 */
	public function getPlainBody(): string {
		return $this->plainMessage;
	}

	public function getContent(): string {
		return $this->getPlainBody();
	}

	/**
	 * @return void
	 */
	public function setContent(string $content) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return Horde_Mime_Part[]
	 */
	public function getAttachments(): array {
		throw new Exception('not implemented');
	}

	/**
	 * @param string $name
	 * @param string $content
	 *
	 * @return void
	 */
	public function addRawAttachment(string $name, string $content): void {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @param string $name
	 * @param string $content
	 *
	 * @return void
	 */
	public function addEmbeddedMessageAttachment(string $name, string $content): void {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @param File $file
	 *
	 * @return void
	 */
	public function addAttachmentFromFiles(File $file) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @param LocalAttachment $attachment
	 * @param ISimpleFile $file
	 *
	 * @return void
	 */
	public function addLocalAttachment(LocalAttachment $attachment, ISimpleFile $file) {
		throw new Exception('IMAP message is immutable');
	}

	/**
	 * @return string|null
	 */
	public function getInReplyTo() {
		throw new Exception('not implemented');
	}

	/**
	 * @param string $id
	 *
	 * @return void
	 */
	public function setInReplyTo(string $id) {
		throw new Exception('not implemented');
	}

	public function getReplyTo(): AddressList {
		return $this->replyTo;
	}

	/**
	 * @param string $id
	 *
	 * @return void
	 */
	public function setReplyTo(string $id) {
		throw new Exception('not implemented');
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

	/**
	 * Cast all values from an IMAP message into the correct DB format
	 *
	 * @param integer $mailboxId
	 * @return Message
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
			in_array(Horde_Imap_Client::FLAG_JUNK, $flags, true) ||
			in_array('junk', $flags, true)
		);
		$msg->setFlagNotjunk(in_array(Horde_Imap_Client::FLAG_NOTJUNK, $flags, true) || in_array('nonjunk', $flags, true));// While this is not a standard IMAP Flag, Thunderbird uses it to mark "not junk"
		// @todo remove this as soon as possible @link https://github.com/nextcloud/mail/issues/25
		$msg->setFlagImportant(in_array('$important', $flags, true) || in_array('$labelimportant', $flags, true) || in_array(Tag::LABEL_IMPORTANT, $flags, true));
		$msg->setFlagAttachments(false);
		$msg->setFlagMdnsent(in_array(Horde_Imap_Client::FLAG_MDNSENT, $flags, true));
		if (!empty($this->scheduling)) {
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
		$tags = array_filter($flags, static function ($flag) use ($allowed) {
			return in_array($flag, $allowed, true) === false;
		});

		if (empty($tags) === true) {
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
