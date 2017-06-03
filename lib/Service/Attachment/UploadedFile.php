<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Luc Calaresu <dev@calaresu.com>
 *
 * ownCloud - Mail
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

use OCP\ITempManager;


class UploadedFile {

	/** @var array */
	private $fileData;

	/** @var string */
	private $oc_tmp_path;

	/**
	 * @param array $fileData
	 */
	public function __construct($fileData) {
		$this->fileData = $fileData;
		// move the php tmp file to a nextcloud temporary folder
		$tmp_folder = \OC::$server->getTempManager()->getTemporaryFolder();
		$this->oc_tmp_path = $tmp_folder.$fileData['name'];
		move_uploaded_file($fileData['tmp_name'], $this->oc_tmp_path);
	}

	/**
	 * @return string
	 */
	public function getFileName() {
		return $this->fileData['name'];
	}

	/**
	 * @return string
	 */
	public function getPath() {
		return $this->oc_tmp_path;
	}

}
