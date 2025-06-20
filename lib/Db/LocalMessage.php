<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;
use function array_filter;

/**
 * @method int getType()
 * @method void setType(int $type)
 * @method int getAccountId()
 * @method void setAccountId(int $accountId)
 * @method int|null getAliasId()
 * @method void setAliasId(?int $aliasId)
 * @method int|null getSendAt()
 * @method void setSendAt(?int $sendAt)
 * @method string getSubject()
 * @method void setSubject(string $subject)
 * @method string getBodyPlain()
 * @method void setBodyPlain(?string $bodyPlain)
 * @method string getBodyHtml()
 * @method void setBodyHtml(?string $bodyHtml)
 * @method string|null getEditorBody()
 * @method void setEditorBody(?string $body)
 * @method bool isHtml()
 * @method void setHtml(bool $html)
 * @method bool|null isFailed()
 * @method void setFailed(bool $failed)
 * @method string|null getInReplyToMessageId()
 * @method void setInReplyToMessageId(?string $inReplyToId)
 * @method int|null getUpdatedAt()
 * @method setUpdatedAt(?int $updatedAt)
 * @method bool|null isPgpMime()
 * @method setPgpMime(bool $pgpMime)
 * @method bool|null getSmimeSign()
 * @method setSmimeSign(bool $smimeSign)
 * @method int|null getSmimeCertificateId()
 * @method setSmimeCertificateId(?int $smimeCertificateId)
 * @method bool|null getSmimeEncrypt()
 * @method setSmimeEncrypt(bool $smimeEncryt)
 * @method int|null getStatus();
 * @method setStatus(?int $status);
 * @method string|null getRaw()
 * @method setRaw(string|null $raw)
 * @method bool getRequestMdn()
 * @method setRequestMdn(bool $mdn)
 */
class LocalMessage extends Entity implements JsonSerializable {
	public const TYPE_OUTGOING = 0;
	public const TYPE_DRAFT = 1;

	public const STATUS_RAW = 0;
	public const STATUS_NO_SENT_MAILBOX = 1;
	public const STATUS_SMIME_SIGN_NO_CERT_ID = 2;
	public const STATUS_SMIME_SIGN_CERT = 3;
	public const STATUS_SMIME_SIGN_FAIL = 4;
	public const STATUS_SMIME_ENCRYPT_NO_CERT_ID = 5;
	public const STATUS_SMIME_ENCRYPT_CERT = 6;
	public const STATUS_SMIME_ENCRYT_FAIL = 7;
	public const STATUS_TOO_MANY_RECIPIENTS = 8;
	public const STATUS_RATELIMIT = 9;
	public const STATUS_SMPT_SEND_FAIL = 10;
	public const STATUS_IMAP_SENT_MAILBOX_FAIL = 11;
	public const STATUS_PROCESSED = 12;
	public const STATUS_ERROR = 13;
	/**
	 * @var int<1,13>
	 * @psalm-var self::TYPE_*
	 */
	protected $type;

	/** @var int */
	protected $accountId;

	/** @var int|null */
	protected $aliasId;

	/** @var int */
	protected $sendAt;

	/** @var string */
	protected $subject;

	/** @var string|null */
	protected $bodyPlain;

	/** @var string|null */
	protected $bodyHtml;

	/** @var string|null */
	protected $editorBody;

	/** @var bool */
	protected $html;

	/** @var string|null */
	protected $inReplyToMessageId;

	/** @var array|null */
	protected $attachments;

	/** @var array|null */
	protected $recipients;

	/** @var bool|null */
	protected $failed;

	/** @var int|null */
	protected $updatedAt;

	/** @var bool|null */
	protected $pgpMime;

	/** @var bool|null */
	protected $smimeSign;

	/** @var int|null */
	protected $smimeCertificateId;

	/** @var bool|null */
	protected $smimeEncrypt;

	/**
	 * @var int|null
	 * @psalm-var int-mask-of<self::STATUS_*>
	 */
	protected $status;

	/** @var string|null */
	protected $raw;

	/** @var bool */
	protected $requestMdn;

	public function __construct() {
		$this->addType('type', 'integer');
		$this->addType('accountId', 'integer');
		$this->addType('aliasId', 'integer');
		$this->addType('sendAt', 'integer');
		$this->addType('html', 'boolean');
		$this->addType('failed', 'boolean');
		$this->addType('updatedAt', 'integer');
		$this->addType('pgpMime', 'boolean');
		$this->addType('smimeSign', 'boolean');
		$this->addType('smimeCertificateId', 'integer');
		$this->addType('smimeEncrypt', 'boolean');
		$this->addType('status', 'integer');
		$this->addType('requestMdn', 'boolean');

	}

	#[\Override]
	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'type' => $this->getType(),
			'accountId' => $this->getAccountId(),
			'aliasId' => $this->getAliasId(),
			'sendAt' => $this->getSendAt(),
			'updatedAt' => $this->getUpdatedAt(),
			'subject' => $this->getSubject(),
			'bodyPlain' => $this->getBodyPlain(),
			'bodyHtml' => $this->getBodyHtml(),
			'editorBody' => $this->getEditorBody(),
			'isHtml' => ($this->isHtml() === true),
			'isPgpMime' => ($this->isPgpMime() === true),
			'inReplyToMessageId' => $this->getInReplyToMessageId(),
			'attachments' => $this->getAttachments(),
			'from' => array_values(
				array_filter($this->getRecipients(), static function (Recipient $recipient) {
					return $recipient->getType() === Recipient::TYPE_FROM;
				})
			),
			'to' => array_values(
				array_filter($this->getRecipients(), static function (Recipient $recipient) {
					return $recipient->getType() === Recipient::TYPE_TO;
				})
			),
			'cc' => array_values(
				array_filter($this->getRecipients(), static function (Recipient $recipient) {
					return $recipient->getType() === Recipient::TYPE_CC;
				})
			),
			'bcc' => array_values(
				array_filter($this->getRecipients(), static function (Recipient $recipient) {
					return $recipient->getType() === Recipient::TYPE_BCC;
				})
			),
			'failed' => $this->isFailed() === true,
			'smimeCertificateId' => $this->getSmimeCertificateId(),
			'smimeSign' => $this->getSmimeSign() === true,
			'smimeEncrypt' => $this->getSmimeEncrypt() === true,
			'status' => $this->getStatus(),
			'raw' => $this->getRaw(),
			'requestMdn' => $this->getRequestMdn(),
		];
	}

	/**
	 * @param LocalAttachment[] $attachments
	 * @return void
	 */
	public function setAttachments(array $attachments): void {
		$this->attachments = $attachments;
	}

	/**
	 * @return LocalAttachment[]|null
	 */
	public function getAttachments(): ?array {
		return $this->attachments;
	}

	/**
	 * @param Recipient[] $recipients
	 * @return void
	 */
	public function setRecipients(array $recipients): void {
		$this->recipients = $recipients;
	}

	/**
	 * @return Recipient[]|null
	 */
	public function getRecipients(): ?array {
		return $this->recipients;
	}
}
