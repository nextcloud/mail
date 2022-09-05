<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <wurst.christoph@gmail.com>
 * @author Lukas Reschke <lukas@owncloud.com>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

namespace OCA\Mail\Http;

use OCP\AppFramework\Http\Response;

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
								 string $nonce = null,
								 string $scriptUrl = null) {
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
	public function render(): string {
		if ($this->plain) {
			return $this->content;
		}

		return '<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><script nonce="' . $this->nonce . '" src="' . $this->scriptUrl . '"></script></head><body>' . $this->content . '<div data-iframe-height></div></body></html>';
	}
}
