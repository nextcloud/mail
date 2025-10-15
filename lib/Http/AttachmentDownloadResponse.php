<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Http;

use OCP\AppFramework\Http\DownloadResponse;

/**
 * @psalm-suppress MissingTemplateParam
 * @todo spec template with 28+
 */
class AttachmentDownloadResponse extends DownloadResponse {
	/** @var string */
	private $content;

	/**
	 * Creates a response that prompts the user to download a file which
	 * contains the passed string
	 *
	 * @param string $content the content that should be written into the file
	 * @param string $filename the name that the downloaded file should have
	 * @param string $contentType the mimetype that the downloaded file should have
	 */
	public function __construct(string $content, string $filename, string $contentType) {
		parent::__construct($filename, $contentType);

		$this->content = $content;
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
