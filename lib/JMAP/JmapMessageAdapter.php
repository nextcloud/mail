<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\JMAP;

use Horde_Imap_Client_DateTime;
use JmapClient\Responses\Mail\MailParameters as MailParametersResponse;
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
		// TODO: the following properties need to be added to the jmap client as functions
		$keywords = $source->parameter('keywords') ?? [];
		$references = $source->parameter('references');
		$preview = $source->parameter('preview');
		$sender = $source->parameter('sender') ?? [];
		$inReplyTo = $source->parameter('inReplyTo');
		$messageId = $source->parameter('messageId');
		$hasAttachment = $source->parameter('hasAttachment');
		$updatedAt = $source->parameter('updatedAt');

		$message = new Message();
		$message->setRemoteId($source->id());
		$message->setMessageId($this->firstString($messageId));
		$message->setInReplyTo($this->firstString($inReplyTo));
		$message->setReferences($this->normalizeReferenceValue($references));
		$message->setThreadRootId($source->thread());
		$message->setSubject($source->subject() ?? '');
		$message->setSentAt($this->parseTimestamp($source->sent() ?? $source->received() ?? null));
		$message->setFlagAnswered($keywords['$answered'] ?? false);
		$message->setFlagDeleted($keywords['$deleted'] ?? false);
		$message->setFlagDraft($keywords['$draft'] ?? false);
		$message->setFlagFlagged($keywords['$flagged'] ?? false);
		$message->setFlagSeen($keywords['$seen'] ?? false);
		$message->setFlagForwarded($keywords['$forwarded'] ?? false);
		$message->setFlagJunk($keywords['$junk'] ?? false);
		$message->setFlagNotjunk($keywords['$notjunk'] ?? false);
		$message->setFlagImportant(($keywords['$label1'] ?? false) || ($keywords[Tag::LABEL_IMPORTANT] ?? false));
		$message->setFlagMdnsent($keywords['$mdnsent'] ?? false);
		$message->setPreviewText(is_string($preview) ? $preview : null);
		$message->setFlagAttachments(is_bool($hasAttachment) ? $hasAttachment : false);
		$message->setStructureAnalyzed(true);
		$message->setUpdatedAt($this->parseTimestamp($updatedAt ?? $source->sent() ?? null));

		$message->setFrom($this->convertAddressList($source->from() ?? $sender));
		$message->setTo($this->convertAddressList($source->to() ?? []));
		$message->setCc($this->convertAddressList($source->cc() ?? []));
		$message->setBcc($this->convertAddressList($source->bcc() ?? []));
		$message->setTags($this->convertTags(is_array($keywords) ? $keywords : []));

		return $message;
	}

	public function convertToModelMessage(MailParametersResponse $source, int $uid, bool $loadBody): IMAPMessage {
		[$plainBody, $htmlBody, $attachments, $inlineAttachments] = $this->extractBodiesAndAttachments($source, $uid, $loadBody);
		[$isEncrypted, $isSigned, $isPgpMimeEncrypted, $scheduling] = $this->extractStructureMetadata($source);
		$dispositionNotificationTo = $this->firstHeaderValue($source, 'header:Disposition-Notification-To:asText') ?? '';
		$hasDkimSignature = $this->firstHeaderValue($source, 'header:DKIM-Signature:asText') !== null;
		[$unsubscribeUrl, $unsubscribeMailto] = $this->extractUnsubscribeTargets($source);
		$isOneClickUnsubscribe = $unsubscribeUrl !== null
			&& str_contains(strtolower($this->firstHeaderValue($source, 'header:List-Unsubscribe-Post:asText') ?? ''), 'one-click');
		$flags = array_keys($source->parameter('keywords') ?? []);

		return new IMAPMessage(
			$uid,
			$this->firstString($source->parameter('messageId') ?? null) ?? '',
			$flags,
			$this->convertAddressList($source->from() ?? $source->parameter('sender') ?? []),
			$this->convertAddressList($source->to() ?? []),
			$this->convertAddressList($source->cc() ?? []),
			$this->convertAddressList($source->bcc() ?? []),
			$this->convertAddressList($source->parameter('replyTo') ?? []),
			(string)($source->subject() ?? ''),
			$plainBody,
			$htmlBody,
			$htmlBody !== '',
			$attachments,
			$inlineAttachments,
			$attachments !== [] || $inlineAttachments !== [],
			$scheduling,
			new Horde_Imap_Client_DateTime('@' . $this->parseTimestamp($source->parameter('receivedAt') ?? $source->parameter('sentAt') ?? null)),
			$this->normalizeRawMessageIdList($source->parameter('references') ?? null),
			$dispositionNotificationTo,
			$hasDkimSignature,
			[],
			$unsubscribeUrl,
			$isOneClickUnsubscribe,
			$unsubscribeMailto,
			$this->firstString($source->parameter('inReplyTo') ?? null) ?? '',
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

	private function firstHeaderValue(MailParametersResponse $source, string $key): ?string {
		$value = $source->parameter($key);
		return $this->firstString($value);
	}

	/**
	 * @return array{0:bool,1:bool,2:bool,3:array<int, array<string, mixed>>}
	 */
	private function extractStructureMetadata(MailParametersResponse $source): array {
		$isEncrypted = false;
		$isSigned = false;
		$isPgpMimeEncrypted = false;
		$scheduling = [];

		$walk = function (array $part) use (&$walk, &$isEncrypted, &$isSigned, &$isPgpMimeEncrypted, &$scheduling): void {
			$type = strtolower((string)($part['type'] ?? ''));
			if ($type === 'multipart/encrypted' || $type === 'application/pkcs7-mime' || $type === 'application/x-pkcs7-mime') {
				$isEncrypted = true;
			}
			if ($type === 'multipart/signed' || $type === 'application/pkcs7-signature' || $type === 'application/x-pkcs7-signature' || $type === 'application/pgp-signature') {
				$isSigned = true;
			}
			if ($type === 'application/pgp-encrypted') {
				$isEncrypted = true;
				$isPgpMimeEncrypted = true;
			}
			if ($type === 'text/calendar') {
				$scheduling[] = [
					'id' => $part['partId'] ?? null,
					'mime' => $type,
					'fileName' => $part['name'] ?? null,
					'method' => is_array($part['parameters'] ?? null) ? ($part['parameters']['method'] ?? null) : null,
				];
			}

			foreach ($part['subParts'] ?? [] as $subPart) {
				if (is_array($subPart)) {
					$walk($subPart);
				}
			}
		};

		$bodyStructure = $source->parameter('bodyStructure');
		if (is_array($bodyStructure)) {
			$walk($bodyStructure);
		}

		return [$isEncrypted, $isSigned, $isPgpMimeEncrypted, $scheduling];
	}

	/**
	 * @return array{0:?string,1:?string}
	 */
	private function extractUnsubscribeTargets(MailParametersResponse $source): array {
		$headerValue = $this->firstHeaderValue($source, 'header:List-Unsubscribe:asText');
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
	 * @return array{0:string,1:string,2:array,3:array}
	 */
	private function extractBodiesAndAttachments(MailParametersResponse $source, int $uid, bool $loadBody): array {
		if (!$loadBody) {
			return ['', '', [], []];
		}

		$bodyValues = $source->parameter('bodyValues');
		$bodyValues = is_array($bodyValues) ? $bodyValues : [];
		$plainBody = '';
		$htmlBody = '';
		$attachments = [];
		$inlineAttachments = [];

		$walk = function (array $part) use (&$walk, &$plainBody, &$htmlBody, &$attachments, &$inlineAttachments, $bodyValues, $uid): void {
			$type = strtolower((string)($part['type'] ?? ''));
			$partId = $part['partId'] ?? null;
			$value = is_string($partId) && isset($bodyValues[$partId]['value']) && is_string($bodyValues[$partId]['value'])
				? $bodyValues[$partId]['value']
				: '';

			if ($type === 'text/plain' && $plainBody === '') {
				$plainBody = $value;
			}
			if ($type === 'text/html' && $htmlBody === '') {
				$htmlBody = $value;
			}

			$disposition = strtolower((string)($part['disposition'] ?? ''));
			if ($disposition === 'attachment' || $disposition === 'inline') {
				$entry = [
					'id' => $partId,
					'messageId' => $uid,
					'fileName' => $part['name'] ?? null,
					'mime' => $type !== '' ? $type : 'application/octet-stream',
					'size' => (int)($part['size'] ?? 0),
					'cid' => $part['cid'] ?? null,
					'disposition' => $part['disposition'] ?? null,
				];
				if ($disposition === 'inline') {
					$inlineAttachments[] = $entry;
				} else {
					$attachments[] = $entry;
				}
			}

			foreach ($part['subParts'] ?? [] as $subPart) {
				if (is_array($subPart)) {
					$walk($subPart);
				}
			}
		};

		$bodyStructure = $source->parameter('bodyStructure');
		if (is_array($bodyStructure)) {
			$walk($bodyStructure);
		}

		$preview = $source->parameter('preview');
		if ($plainBody === '' && is_string($preview)) {
			$plainBody = $preview;
		}

		return [$plainBody, $htmlBody, $attachments, $inlineAttachments];
	}
}
