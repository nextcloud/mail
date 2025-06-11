<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Http;

use DateTime;
use OCP\AppFramework\Http\DownloadResponse;

/**
 * @psalm-suppress MissingTemplateParam
 * @todo spec template with 28+
 */
class ProxyDownloadResponse extends DownloadResponse {
	/** @var string */
	private $content;

	/**
	 * Creates a response that prompts the user to download a file which
	 * contains the passed string
	 * Additionally the response will be cacheable by browsers. Since the content is
	 * generally not sensitive content (e.g. Logos in mails) this should not be a problem.
	 *
	 * @param string $content the content that should be written into the file
	 * @param string $filename the name that the downloaded file should have
	 * @param string $contentType the mimetype that the downloaded file should have
	 */
	public function __construct(string $content, string $filename, string $contentType) {
		parent::__construct($filename, $contentType);

		$this->content = $content;

		$now = (new DateTime('now'))->getTimestamp();
		$expires = (new DateTime('now + 11 months'))->getTimestamp();
		$this->cacheFor($expires - $now, false, true);
	}

	/**
	 * Simply sets the headers and returns the file contents
	 *
	 * @return string the file contents
	 */
	#[\Override]
	public function render(): string {
		return $this->content;
	}
}
