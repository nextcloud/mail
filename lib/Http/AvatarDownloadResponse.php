<?php

/**
 * @author Jakob Sack <mail@jakobsack.de>
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

use OCP\AppFramework\Http\DownloadResponse;

class AvatarDownloadResponse extends DownloadResponse {

	private $content;

	/**
	 * @param string $content
	 */
	public function __construct($content) {
		parent::__construct('avatar', 'application/octet-stream');
		$this->content = $content;

		$this->cacheFor(2 * 60 * 60);
	}

	/**
	 * Simply sets the headers and returns the file contents
	 * @return string the file contents
	 */
	public function render() {
		return $this->content;
	}

}
