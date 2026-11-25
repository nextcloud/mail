<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 owncloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail;

use Horde_Mime_Part;

class Attachment {
	private const CALENDAR_METHODS = ['ADD', 'CANCEL', 'COUNTER', 'DECLINECOUNTER', 'PUBLISH', 'REFRESH', 'REPLY', 'REQUEST'];

	public function __construct(
		private ?string $id,
		private ?string $name,
		private string $type,
		private string $content,
		private int $size,
		public readonly ?string $contentId,
		public readonly ?string $disposition,
	) {
	}

	public static function fromMimePart(Horde_Mime_Part $mimePart): self {
		$disposition = $mimePart->getDisposition();
		if ($disposition === '') {
			$disposition = null;
		}

		$type = $mimePart->getType();
		$method = strtoupper((string)$mimePart->getContentTypeParameter('method'));
		if ($type === 'text/calendar' && in_array($method, self::CALENDAR_METHODS, true)) {
			$type .= '; method=' . $method;
		}

		return new Attachment(
			$mimePart->getMimeId(),
			$mimePart->getName(),
			$type,
			$mimePart->getContents(),
			(int)$mimePart->getBytes(),
			$mimePart->getContentId(),
			$disposition,
		);
	}

	public function getId(): ?string {
		return $this->id;
	}

	public function getName(): ?string {
		return $this->name;
	}

	public function getType(): string {
		return $this->type;
	}

	public function getContent(): string {
		return $this->content;
	}

	public function getSize(): int {
		return $this->size;
	}
}
