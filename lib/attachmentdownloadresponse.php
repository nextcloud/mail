<?php

/**
 * ownCloud - App Framework
 *
 * @author Bernhard Posselt
 * @copyright 2012 Bernhard Posselt dev@bernhard-posselt.com
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */


namespace OCA\Mail;

use OCP\AppFramework\Http\DownloadResponse;

class AttachmentDownloadResponse extends DownloadResponse {

	private $content;

	/**
	 * Creates a response that prompts the user to download a file which
	 * contains the passed string
	 * @param string $content the content that should be written into the file
	 * @param string $filename the name that the downloaded file should have
	 * @param string $contentType the mimetype that the downloaded file should have
	 */
	public function __construct($content, $filename, $contentType){
		parent::__construct($filename, $contentType);
		$this->content = $content;
	}


	/**
	 * Simply sets the headers and returns the file contents
	 * @return string the file contents
	 */
	public function render(){
		return $this->content;
	}

}
