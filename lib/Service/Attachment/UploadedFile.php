<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
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
