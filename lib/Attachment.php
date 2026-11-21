<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 owncloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail;

use Horde_Mime_Part;

class Attachment {
	/** @var array<string, string> */
	private array $typeParameters;

	/**
	 * @param array<string, string> $typeParameters Content-Type parameters, e.g. the iTIP method
	 */
	public function __construct(
		private ?string $id,
		private ?string $name,
		private string $type,
		private string $content,
		private int $size,
		public readonly ?string $contentId,
		public readonly ?string $disposition,
		array $typeParameters = [],
	) {
		$this->typeParameters = array_change_key_case($typeParameters);
	}

	public static function fromMimePart(Horde_Mime_Part $mimePart): self {
		$disposition = $mimePart->getDisposition();
		if ($disposition === '') {
			$disposition = null;
		}

		return new Attachment(
			$mimePart->getMimeId(),
			$mimePart->getName(),
			$mimePart->getType(),
			$mimePart->getContents(),
			(int)$mimePart->getBytes(),
			$mimePart->getContentId(),
			$disposition,
			$mimePart->getAllContentTypeParameters(),
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

	/**
	 * @return array<string, string> Content-Type parameters keyed by lower case name
	 */
	public function getTypeParameters(): array {
		return $this->typeParameters;
	}

	public function getContent(): string {
		return $this->content;
	}

	public function getSize(): int {
		return $this->size;
	}
}
