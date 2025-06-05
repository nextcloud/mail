<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Http;

use OCP\AppFramework\Http\Response;

/**
 * @psalm-suppress MissingTemplateParam
 * @todo spec template with 28+
 */
class HtmlResponse extends Response {
	/** @var string */
	private $content;

	/** @var bool */
	private $plain;

	/** @var string|null */
	private $nonce;

	/** @var string|null */
	private $scriptUrl;

	/**
	 * @param string $content message html content
	 * @param bool $plain do not inject scripts if true (default=false)
	 * @param string|null $nonce
	 * @param string|null $scriptUrl
	 */
	private function __construct(string $content,
		bool $plain = false,
		?string $nonce = null,
		?string $scriptUrl = null) {
		parent::__construct();
		$this->content = $content;
		$this->plain = $plain;
		$this->nonce = $nonce;
		$this->scriptUrl = $scriptUrl;
	}

	public static function plain(string $content): self {
		return new self($content, true);
	}

	public static function withResizer(string $content,
		string $nonce,
		string $scriptUrl): self {
		return new self(
			$content,
			false,
			$nonce,
			$scriptUrl
		);
	}

	/**
	 * Inject scripts if not plain and return message html content.
	 *
	 * @return string message html content
	 */
	#[\Override]
	public function render(): string {
		if ($this->plain) {
			return $this->content;
		}

		return '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><script nonce="' . $this->nonce . '" src="' . $this->scriptUrl . '"></script></head><body>' . $this->content . '<div data-iframe-size></div></body></html>';
	}
}
