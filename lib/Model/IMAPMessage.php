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
use Horde_Imap_Client_Data_Envelope;
use Horde_Imap_Client_Data_Fetch;
use Horde_Imap_Client_DateTime;
use Horde_Imap_Client_Fetch_Query;
use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Socket;
use Horde_Mime_Headers;
use Horde_Mime_Headers_MessageId;
use Horde_Mime_Part;
use JsonSerializable;
use OC;
use OCA\Mail\AddressList;
use OCA\Mail\Db\LocalAttachment;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Tag;
use OCA\Mail\Service\Html;
use OCP\AppFramework\Db\DoesNotExistException;
use OCP\Files\File;
use OCP\Files\SimpleFS\ISimpleFile;
use ReturnTypeWillChange;
use function fclose;
use function in_array;
use function mb_convert_encoding;
use function mb_strcut;
use function trim;

class IMAPMessage implements IMessage, JsonSerializable {
	use ConvertAddresses;

	/**
	 * @var string[]
	 */
	private $attachmentsToIgnore = ['signature.asc', 'smime.p7s'];

	/** @var Html|null */
	private $htmlService;

	/**
	 * @param Horde_Imap_Client_Socket|null $conn
	 * @param Horde_Imap_Client_Mailbox|string $mailBox
	 * @param int $messageId
	 * @param Horde_Imap_Client_Data_Fetch|null $fetch
	 * @param bool $loadHtmlMessage
	 * @param Html|null $htmlService
	 *
	 * @throws DoesNotExistException
	 */
	public function __construct($conn,
								$mailBox,
								int $messageId,
								Horde_Imap_Client_Data_Fetch $fetch = null,
								bool $loadHtmlMessage = false,
								Html $htmlService = null) {
		$this->conn = $conn;
		$this->mailBox = $mailBox;
		$this->messageId = $messageId;
		$this->loadHtmlMessage = $loadHtmlMessage;

		$this->htmlService = $htmlService;
		if (is_null($htmlService)) {
			$urlGenerator = OC::$server->getURLGenerator();
			$request = OC::$server->getRequest();
			$this->htmlService = new Html($urlGenerator, $request);
		}

		if ($fetch === null) {
			$this->loadMessageBodies();
		} else {
			$this->fetch = $fetch;
		}
	}

	// output all the following:
	public $header = null;
	public $htmlMessage = '';
	public $plainMessage = '';
	public $attachments = [];
	public $inlineAttachments = [];
	public $scheduling = [];
	private $loadHtmlMessage = false;
	private $hasHtmlMessage = false;

	/**
	 * @var Horde_Imap_Client_Socket
	 */
	private $conn;

	/**
	 * @var Horde_Imap_Client_Mailbox
	 */
	private $mailBox;
	private $messageId;

	/**
	 * @var Horde_Imap_Client_Data_Fetch
	 */
	private $fetch;

	public static function generateMessageId(): string {
		return Horde_Mime_Headers_MessageId::create('nextcloud-mail-generated')->value;
	}

	/**
	 * @return int
	 */
	public function getUid(): int {
		return $this->fetch->getUid();
	}

	/**
	 * @deprecated  Seems unused
	 * @return array
	 */
	public function getFlags(): array {
		$flags = $this->fetch->getFlags();
		return [
			'seen' => in_array(Horde_Imap_Client::FLAG_SEEN, $flags),
			'flagged' => in_array(Horde_Imap_Client::FLAG_FLAGGED, $flags),
			'answered' => in_array(Horde_Imap_Client::FLAG_ANSWERED, $flags),
			'deleted' => in_array(Horde_Imap_Client::FLAG_DELETED, $flags),
			'draft' => in_array(Horde_Imap_Client::FLAG_DRAFT, $flags),
			'forwarded' => in_array(Horde_Imap_Client::FLAG_FORWARDED, $flags),
			'hasAttachments' => $this->hasAttachments($this->fetch->getStructure()),
			'mdnsent' => in_array(Horde_Imap_Client::FLAG_MDNSENT, $flags, true),
			'important' => in_array(Tag::LABEL_IMPORTANT, $flags, true)
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

	/**
	 * @return Horde_Imap_Client_Data_Envelope
	 */
	public function getEnvelope() {
		return $this->fetch->getEnvelope();
	}

	private function getRawReferences(): string {
		/** @var resource $headersStream */
		$headersStream = $this->fetch->getHeaderText('0', Horde_Imap_Client_Data_Fetch::HEADER_STREAM);
		$parsedHeaders = Horde_Mime_Headers::parseHeaders($headersStream);
		fclose($headersStream);
		$references = $parsedHeaders->getHeader('references');
		if ($references === null) {
			return '';
		}
		return $references->value_single;
	}

	private function getRawInReplyTo(): string {
		return $this->fetch->getEnvelope()->in_reply_to;
	}

	public function getDispositionNotificationTo(): string {
		/** @var resource $headersStream */
		$headersStream = $this->fetch->getHeaderText('0', Horde_Imap_Client_Data_Fetch::HEADER_STREAM);
		$parsedHeaders = Horde_Mime_Headers::parseHeaders($headersStream);
		fclose($headersStream);
		$header = $parsedHeaders->getHeader('disposition-notification-to');
		if ($header === null) {
			return '';
		}
		return $header->value_single;
	}

	/**
	 * @return AddressList
	 */
	public function getFrom(): AddressList {
		return AddressList::fromHorde($this->getEnvelope()->from);
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

	/**
	 * @return AddressList
	 */
	public function getTo(): AddressList {
		return AddressList::fromHorde($this->getEnvelope()->to);
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

	/**
	 * @return AddressList
	 */
	public function getCC(): AddressList {
		return AddressList::fromHorde($this->getEnvelope()->cc);
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

	/**
	 * @return AddressList
	 */
	public function getBCC(): AddressList {
		return AddressList::fromHorde($this->getEnvelope()->bcc);
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

	/**
	 * Get the ID if available
	 *
	 * @return string
	 */
	public function getMessageId(): string {
		return $this->getEnvelope()->message_id;
	}

	/**
	 * @return string
	 */
	public function getSubject(): string {
		// Try a soft conversion first (some installations, eg: Alpine linux,
		// have issues with the '//IGNORE' option)
		$subject = $this->getEnvelope()->subject;
		$utf8 = iconv('UTF-8', 'UTF-8', $subject);
		if ($utf8 !== false) {
			return $utf8;
		}
		return iconv("UTF-8", "UTF-8//IGNORE", $subject);
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

	/**
	 * @return Horde_Imap_Client_DateTime
	 */
	public function getSentDate(): Horde_Imap_Client_DateTime {
		return $this->fetch->getImapDate();
	}

	/**
	 * @return int
	 */
	public function getSize(): int {
		return $this->fetch->getSize();
	}

	/**
	 * @param Horde_Mime_Part $part
	 *
	 * @return bool
	 */
	private function hasAttachments($part) {
		foreach ($part->getParts() as $p) {
			if ($p->isAttachment() || $p->getType() === 'message/rfc822') {
				return true;
			}
			if ($this->hasAttachments($p)) {
				return true;
			}
		}

		return false;
	}

	private function loadMessageBodies(): void {
		$fetch_query = new Horde_Imap_Client_Fetch_Query();
		$fetch_query->envelope();
		$fetch_query->structure();
		$fetch_query->flags();
		$fetch_query->size();
		$fetch_query->imapDate();
		$fetch_query->headerText([
			'cache' => true,
			'peek' => true,
		]);

		// $list is an array of Horde_Imap_Client_Data_Fetch objects.
		$ids = new Horde_Imap_Client_Ids($this->messageId);
		$headers = $this->conn->fetch($this->mailBox, $fetch_query, ['ids' => $ids]);
		/** @var Horde_Imap_Client_Data_Fetch $fetch */
		$fetch = $headers[$this->messageId];
		if (is_null($fetch)) {
			throw new DoesNotExistException("This email ($this->messageId) can't be found. Probably it was deleted from the server recently. Please reload.");
		}

		// set $this->fetch to get to, from ...
		$this->fetch = $fetch;

		// analyse the body part
		$structure = $fetch->getStructure();

		// debugging below
		$structure_type = $structure->getPrimaryType();
		if ($structure_type === 'multipart') {
			$i = 1;
			foreach ($structure->getParts() as $p) {
				$this->getPart($p, $i++);
			}
		} else {
			if (!is_null($structure->findBody())) {
				// get the body from the server
				$partId = (int)$structure->findBody();
				$this->getPart($structure->getPart($partId), $partId);
			}
		}
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param mixed $partNo
	 *
	 * @throws DoesNotExistException
	 *
	 * @return void
	 */
	private function getPart(Horde_Mime_Part $p, $partNo): void {
		// iMIP messages
		// Handle text/calendar parts first because they might be attachments at the same time.
		// Otherwise, some of the following if-conditions might break the handling and treat iMIP
		// data like regular attachments.
		$allContentTypeParameters = $p->getAllContentTypeParameters();
		if ($p->getType() === 'text/calendar') {
			// Handle event data like a regular attachment
			// Outlook doesn't set a content disposition
			// We work around this by checking for the name only
			if ($p->getName() !== null) {
				$this->attachments[] = [
					'id' => $p->getMimeId(),
					'messageId' => $this->messageId,
					'fileName' => $p->getName(),
					'mime' => $p->getType(),
					'size' => $p->getBytes(),
					'cid' => $p->getContentId(),
					'disposition' => $p->getDisposition()
				];
			}

			// return if this is an event attachment only
			// the method parameter determines if this is a iMIP message
			if (!isset($allContentTypeParameters['method'])) {
				return;
			}

			if (in_array(strtoupper($allContentTypeParameters['method']), ['REQUEST', 'REPLY', 'CANCEL'])) {
				$this->scheduling[] = [
					'id' => $p->getMimeId(),
					'messageId' => $this->messageId,
					'method' => strtoupper($allContentTypeParameters['method']),
					'contents' => $this->loadBodyData($p, $partNo),
				];
				return;
			}
		}

		// Regular attachments
		if ($p->isAttachment() || $p->getType() === 'message/rfc822') {
			$this->attachments[] = [
				'id' => $p->getMimeId(),
				'messageId' => $this->messageId,
				'fileName' => $p->getName(),
				'mime' => $p->getType(),
				'size' => $p->getBytes(),
				'cid' => $p->getContentId(),
				'disposition' => $p->getDisposition()
			];
			return;
		}

		// Inline attachments
		// Horde doesn't consider parts with content-disposition set to inline as
		// attachment so we need to use another way to get them.
		// We use these inline attachments to render a message's html body in $this->getHtmlBody()
		$filename = $p->getName();
		if ($p->getType() === 'message/rfc822' || isset($filename)) {
			if (in_array($filename, $this->attachmentsToIgnore)) {
				return;
			}
			$this->inlineAttachments[] = [
				'id' => $p->getMimeId(),
				'messageId' => $this->messageId,
				'fileName' => $filename,
				'mime' => $p->getType(),
				'size' => $p->getBytes(),
				'cid' => $p->getContentId()
			];
			return;
		}

		if ($p->getPrimaryType() === 'multipart') {
			$this->handleMultiPartMessage($p, $partNo);
			return;
		}

		if ($p->getType() === 'text/plain') {
			$this->handleTextMessage($p, $partNo);
			return;
		}

		if ($p->getType() === 'text/html') {
			$this->handleHtmlMessage($p, $partNo);
			return;
		}

		// EMBEDDED MESSAGE
		// Many bounce notifications embed the original message as type 2,
		// but AOL uses type 1 (multipart), which is not handled here.
		// There are no PHP functions to parse embedded messages,
		// so this just appends the raw source to the main message.
		if ($p[0] === 'message') {
			$data = $this->loadBodyData($p, $partNo);
			$this->plainMessage .= trim($data) . "\n\n";
		}
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
				function ($a) use ($cid) {
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

	/**
	 * @param Horde_Mime_Part $part
	 * @param mixed $partNo
	 *
	 * @throws DoesNotExistException
	 *
	 * @return void
	 */
	private function handleMultiPartMessage(Horde_Mime_Part $part, $partNo): void {
		$i = 1;
		foreach ($part->getParts() as $p) {
			$this->getPart($p, "$partNo.$i");
			$i++;
		}
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param mixed $partNo
	 *
	 * @throws DoesNotExistException
	 *
	 * @return void
	 */
	private function handleTextMessage(Horde_Mime_Part $p, $partNo): void {
		$data = $this->loadBodyData($p, $partNo);
		$this->plainMessage .= trim($data) . "\n\n";
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param mixed $partNo
	 *
	 * @throws DoesNotExistException
	 *
	 * @return void
	 */
	private function handleHtmlMessage(Horde_Mime_Part $p, $partNo): void {
		$this->hasHtmlMessage = true;
		if ($this->loadHtmlMessage) {
			$data = $this->loadBodyData($p, $partNo);
			$this->htmlMessage .= $data . "<br><br>";
		}
	}

	/**
	 * @param Horde_Mime_Part $p
	 * @param mixed $partNo
	 *
	 * @return string
	 * @throws DoesNotExistException
	 * @throws Exception
	 */
	private function loadBodyData(Horde_Mime_Part $p, $partNo): string {
		// DECODE DATA
		$fetch_query = new Horde_Imap_Client_Fetch_Query();
		$ids = new Horde_Imap_Client_Ids($this->messageId);

		$fetch_query->bodyPart($partNo, [
			'peek' => true
		]);
		$fetch_query->bodyPartSize($partNo);
		$fetch_query->mimeHeader($partNo, [
			'peek' => true
		]);

		$headers = $this->conn->fetch($this->mailBox, $fetch_query, ['ids' => $ids]);
		/* @var $fetch Horde_Imap_Client_Data_Fetch */
		$fetch = $headers[$this->messageId];
		if (is_null($fetch)) {
			throw new DoesNotExistException("Mail body for this mail($this->messageId) could not be loaded");
		}

		$mimeHeaders = $fetch->getMimeHeader($partNo, Horde_Imap_Client_Data_Fetch::HEADER_PARSE);
		if ($enc = $mimeHeaders->getValue('content-transfer-encoding')) {
			$p->setTransferEncoding($enc);
		}

		$data = $fetch->getBodyPart($partNo);

		$p->setContents($data);
		$data = $p->getContents();

		if ($data === null) {
			return '';
		}

		// Only convert encoding if it is explicitly specified in the header because text/calendar
		// data is utf-8 by default.
		$charset = $p->getContentTypeParameter('charset');
		if ($charset !== null && strtoupper($charset) !== 'UTF-8') {
			$data = mb_convert_encoding($data, 'UTF-8', $charset);
		}
		return (string)$data;
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

	/**
	 * @return AddressList
	 */
	public function getReplyTo() {
		return AddressList::fromHorde($this->getEnvelope()->reply_to);
	}

	/**
	 * @param string $id
	 *
	 * @return void
	 */
	public function setReplyTo(string $id) {
		throw new Exception('not implemented');
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

		$flags = $this->fetch->getFlags();
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
		$tags = array_filter($flags, function ($flag) use ($allowed) {
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
