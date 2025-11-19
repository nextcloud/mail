<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Http;

use OCP\AppFramework\Http\DownloadResponse;

/**
 * @psalm-suppress MissingTemplateParam
 * @todo spec template with 28+
 */
class AvatarDownloadResponse extends DownloadResponse {
	public function __construct(
		private readonly string $content
	) {
		parent::__construct('avatar', 'application/octet-stream');
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
