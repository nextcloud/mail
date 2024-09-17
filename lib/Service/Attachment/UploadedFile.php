<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\Attachment;

class UploadedFile {
	/** @var array */
	private $fileData;

	/**
	 * @param array $fileData
	 */
	public function __construct(array $fileData) {
		$this->fileData = $fileData;
	}

	/**
	 * @return string|null
	 */
	public function getFileName() {
		return $this->fileData['name'] ?? null;
	}

	/**
	 * @return string|null
	 */
	public function getTempPath() {
		return $this->fileData['tmp_name'] ?? null;
	}

	/**
	 * @return string
	 */
	public function getMimeType() {
		return $this->fileData['type'] ?? 'application/octet-stream';
	}
}
