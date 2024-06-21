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
		return isset($this->fileData['name']) ? $this->fileData['name'] : null;
	}

	/**
	 * @return string|null
	 */
	public function getTempPath() {
		return isset($this->fileData['tmp_name']) ? $this->fileData['tmp_name'] : null;
	}

	/**
	 * @return string
	 */
	public function getMimeType() {
		return isset($this->fileData['type']) ? $this->fileData['type'] : 'application/octet-stream';
	}
}
