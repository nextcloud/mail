<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\JMAP;

use Horde_Imap_Client_DateTime;
use JmapClient\Responses\Mail\MailParameters as MailParametersResponse;
use JmapClient\Responses\Mail\MailPart as MailPartResponse;
use OCA\Mail\Address;
use OCA\Mail\AddressList;
use OCA\Mail\Db\Message;
use OCA\Mail\Db\Tag;
use OCA\Mail\Model\IMAPMessage;
use OCA\Mail\Service\Html;

class JmapMessageAdapter {
	private const RESERVED_KEYWORDS = [
		'$seen',
		'$flagged',
		'$answered',
		'$deleted',
		'$draft',
		'$forwarded',
		'$mdnsent',
		'$junk',
		'$notjunk',
		'$phishing',
		'$important',
		Tag::LABEL_IMPORTANT,
	];

	public function __construct(
		private readonly Html $htmlService,
	) {
	}

	public function convertToDatabaseMessage(MailParametersResponse $source): Message {
		$keywords = $source->keywords();
		$updatedAt = $source->parameter('updatedAt');

		$message = new Message();
		$message->setRemoteId($source->id());
		$message->setMessageId($this->firstString($source->messageId()));
		$message->setInReplyTo($this->firstString($source->inReplyTo()));
		$message->setReferences($this->normalizeReferenceValue($source->references()));
		$message->setThreadRootId($source->thread());
		$message->setSubject($source->subject() ?? '');
		$message->setSentAt($this->parseTimestamp($source->sent() ?? $source->received() ?? null));
		$message->setFlagAnswered($source->answered() ?? false);
		$message->setFlagDeleted($source->keyword('$deleted') ?? false);
		$message->setFlagDraft($source->draft() ?? false);
		$message->setFlagFlagged($source->flagged() ?? false);
		$message->setFlagSeen($source->seen() ?? false);
		$message->setFlagForwarded($source->forwarded() ?? false);
		$message->setFlagJunk($source->junk() ?? false);
		$message->setFlagNotjunk($source->notjunk() ?? false);
		$message->setFlagImportant(($source->keyword('$label1') ?? false) || ($source->keyword(Tag::LABEL_IMPORTANT) ?? false));
		$message->setFlagMdnsent($source->keyword('$mdnsent') ?? false);
		$message->setPreviewText($source->bodyTextPreview());
		$message->setFlagAttachments($source->hasAttachment() ?? false);
		$message->setStructureAnalyzed(true);
		$message->setUpdatedAt($this->parseTimestamp($updatedAt ?? $source->sent() ?? null));

		$message->setFrom($this->convertAddressList($source->from() ?? $source->sender()));
		$message->setTo($this->convertAddressList($source->to() ?? []));
		$message->setCc($this->convertAddressList($source->cc() ?? []));
		$message->setBcc($this->convertAddressList($source->bcc() ?? []));
		$message->setTags($this->convertTags(is_array($keywords) ? $keywords : []));

		return $message;
	}

	public function convertToModelMessage(MailParametersResponse $source, int $uid, bool $loadBody): IMAPMessage {
		// extract body, attachments and other related properties from the structure
		[
			'plainBody' => $plainBody,
			'htmlBody' => $htmlBody,
			'attachments' => $attachments,
			'inlineAttachments' => $inlineAttachments,
			'isEncrypted' => $isEncrypted,
			'isSigned' => $isSigned,
			'isPgpMimeEncrypted' => $isPgpMimeEncrypted,
			'scheduling' => $scheduling,
		] = $this->extractStructureData($source, $uid, $loadBody);
		$dispositionNotificationTo = $this->firstHeaderValue($source, 'Disposition-Notification-To') ?? '';
		$hasDkimSignature = $this->firstHeaderValue($source, 'DKIM-Signature') !== null;
		[$unsubscribeUrl, $unsubscribeMailto] = $this->extractUnsubscribeTargets($source);
		$isOneClickUnsubscribe = $unsubscribeUrl !== null
			&& str_contains(strtolower($this->firstHeaderValue($source, 'List-Unsubscribe-Post') ?? ''), 'one-click');
		$flags = array_keys($source->keywords());

		return new IMAPMessage(
			$uid,
			$this->firstString($source->messageId()) ?? '',
			$flags,
			$this->convertAddressList($source->from() ?? $source->sender()),
			$this->convertAddressList($source->to() ?? []),
			$this->convertAddressList($source->cc() ?? []),
			$this->convertAddressList($source->bcc() ?? []),
			$this->convertAddressList($source->replyTo() ?? []),
			(string)($source->subject() ?? ''),
			$plainBody,
			$htmlBody,
			$htmlBody !== '',
			$attachments,
			$inlineAttachments,
			$attachments !== [] || $inlineAttachments !== [],
			$scheduling,
			new Horde_Imap_Client_DateTime('@' . $this->parseTimestamp($source->received() ?? $source->sent() ?? null)),
			$this->normalizeRawMessageIdList($source->references()),
			$dispositionNotificationTo,
			$hasDkimSignature,
			[],
			$unsubscribeUrl,
			$isOneClickUnsubscribe,
			$unsubscribeMailto,
			$this->firstString($source->inReplyTo()) ?? '',
			$isEncrypted,
			$isSigned,
			false,
			$this->htmlService,
			$isPgpMimeEncrypted,
		);
	}

	/**
	 * @param array<int, array<string, mixed>> $messages
	 */
	public function countUnreadMessages(array $messages): int {
		$count = 0;
		foreach ($messages as $message) {
			if (($message['keywords']['$seen'] ?? false) !== true) {
				$count++;
			}
		}

		return $count;
	}

	private function convertAddressList(array $entries): AddressList {
		$addresses = [];
		foreach ($entries as $entry) {
			$email = is_array($entry) ? ($entry['email'] ?? null) : null;
			if (!is_string($email) || $email === '') {
				continue;
			}
			$addresses[] = Address::fromRaw((string)($entry['name'] ?? $email), $email);
		}

		return new AddressList($addresses);
	}

	/**
	 * @param array<string, bool> $keywords
	 * @return Tag[]
	 */
	private function convertTags(array $keywords): array {
		$tags = [];
		foreach ($keywords as $keyword => $value) {
			if (!is_string($keyword) || $keyword === '' || $value !== true || $this->isReservedKeyword($keyword)) {
				continue;
			}

			$tag = new Tag();
			$tag->setImapLabel($keyword);
			$tag->setDisplayName($keyword);
			$tag->setColor('');
			$tag->setIsDefaultTag(false);
			$tags[] = $tag;
		}

		return $tags;
	}

	private function isReservedKeyword(string $keyword): bool {
		return in_array($keyword, self::RESERVED_KEYWORDS, true);
	}

	private function parseTimestamp(mixed $value): int {
		if (is_string($value) && $value !== '') {
			$timestamp = strtotime($value);
			if ($timestamp !== false) {
				return $timestamp;
			}
		}

		return time();
	}

	private function firstString(mixed $value): ?string {
		if (is_string($value) && $value !== '') {
			return $value;
		}
		if (is_array($value)) {
			foreach ($value as $entry) {
				if (is_string($entry) && $entry !== '') {
					return $entry;
				}
			}
		}

		return null;
	}

	private function normalizeReferenceValue(mixed $references): ?string {
		if ($references === null) {
			return null;
		}
		if (is_string($references)) {
			return $references;
		}
		if (is_array($references)) {
			return json_encode(array_values(array_filter($references, static fn (mixed $value): bool => is_string($value) && $value !== '')));
		}

		return null;
	}

	private function normalizeRawMessageIdList(mixed $references): string {
		if (!is_array($references)) {
			return '';
		}

		return implode(' ', array_filter($references, static fn (mixed $value): bool => is_string($value) && $value !== ''));
	}

	private function firstHeaderValue(MailParametersResponse $source, string $name): ?string {
		$value = $source->header($name, 'asText');
		return $this->firstString($value);
	}

	/**
	 * @return array{0:?string,1:?string}
	 */
	private function extractUnsubscribeTargets(MailParametersResponse $source): array {
		$headerValue = $this->firstHeaderValue($source, 'List-Unsubscribe');
		if ($headerValue === null) {
			return [null, null];
		}

		$unsubscribeUrl = null;
		$unsubscribeMailto = null;
		foreach (preg_split('/\s*,\s*/', $headerValue) ?: [] as $entry) {
			$target = trim($entry, " \t\n\r\0\x0B<>");
			if ($target === '') {
				continue;
			}

			$normalizedTarget = strtolower($target);
			if ($unsubscribeMailto === null && str_starts_with($normalizedTarget, 'mailto:')) {
				$unsubscribeMailto = $target;
				continue;
			}
			if ($unsubscribeUrl === null && (str_starts_with($normalizedTarget, 'https://') || str_starts_with($normalizedTarget, 'http://'))) {
				$unsubscribeUrl = $target;
			}
		}

		return [$unsubscribeUrl, $unsubscribeMailto];
	}

	/**
	 * @return array{
	 * 	plainBody:string,
	 * 	htmlBody:string,
	 * 	attachments:array<int, array<string, mixed>>,
	 * 	inlineAttachments:array<int, array<string, mixed>>,
	 * 	isEncrypted:bool,
	 * 	isSigned:bool,
	 * 	isPgpMimeEncrypted:bool,
	 * 	scheduling:array<int, array<string, mixed>>
	 * }
	 */
	private function extractStructureData(MailParametersResponse $source, int $uid, bool $loadBody): array {
		$state = [
			'plainBody' => '',
			'htmlBody' => '',
			'attachments' => [],
			'inlineAttachments' => [],
			'isEncrypted' => false,
			'isSigned' => false,
			'isPgpMimeEncrypted' => false,
			'scheduling' => [],
		];

		$walk = function (MailPartResponse $part) use (&$walk, &$state, $source, $uid, $loadBody): void {
			$partId = $part->id();
			$blobId = $part->blob();
			$type = strtolower((string)($part->type() ?? ''));
			$disposition = strtolower((string)($part->disposition() ?? ''));
			$content = is_string($partId) ? ($source->bodyPartValue($partId) ?? '') : '';

			if ($type === 'multipart/encrypted' || $type === 'application/pkcs7-mime' || $type === 'application/x-pkcs7-mime') {
				$state['isEncrypted'] = true;
			}
			if ($type === 'multipart/signed' || $type === 'application/pkcs7-signature' || $type === 'application/x-pkcs7-signature' || $type === 'application/pgp-signature') {
				$state['isSigned'] = true;
			}
			if ($type === 'application/pgp-encrypted') {
				$state['isEncrypted'] = true;
				$state['isPgpMimeEncrypted'] = true;
			}
			if ($type === 'text/calendar') {
				$state['scheduling'][] = [
					'id' => $partId,
					'mime' => $type,
					'fileName' => $part->name(),
					'method' => $this->extractCalendarMethod($content),
				];
			}

			if ($loadBody && $type === 'text/plain' && $state['plainBody'] === '') {
				$state['plainBody'] = $content;
			}
			if ($loadBody && $type === 'text/html' && $state['htmlBody'] === '') {
				$state['htmlBody'] = $content;
			}

			if ($loadBody && ($disposition === 'attachment' || $disposition === 'inline' || $type === 'text/calendar' || $type === 'application/ics')) {
				$entry = [
					'id' => $blobId,
					'messageId' => $uid,
					'fileName' => $part->name(),
					'mime' => $type !== '' ? $type : 'application/octet-stream',
					'size' => (int)($part->size() ?? 0),
					'cid' => $part->cid(),
					'disposition' => $part->disposition(),
				];
				if ($disposition === 'inline') {
					$state['inlineAttachments'][] = $entry;
				} else {
					$state['attachments'][] = $entry;
				}
			}

			foreach ($part->parts() ?? [] as $subPart) {
				if ($subPart instanceof MailPartResponse) {
					$walk($subPart);
				}
			}
		};

		$bodyStructure = $source->bodyPartStructure();
		if ($bodyStructure instanceof MailPartResponse) {
			$walk($bodyStructure);
		}

		$preview = $source->bodyTextPreview();
		if ($loadBody && $state['plainBody'] === '' && is_string($preview)) {
			$state['plainBody'] = $preview;
		}

		return $state;
	}

	private function extractCalendarMethod(string $content): ?string {
		if ($content === '') {
			return null;
		}

		if (preg_match('/^METHOD:([^\r\n;]+)/mi', $content, $matches) !== 1) {
			return null;
		}

		$method = trim($matches[1]);

		return $method !== '' ? $method : null;
	}
}
