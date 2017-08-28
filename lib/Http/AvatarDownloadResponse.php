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
	 * Creates a response that prompts the user to download a file which
	 * contains the passed string
	 * Additionally the response will be cacheable by browsers. Since the content is
	 * generally not sensitive content (e.g. Logos in mails) this should not be a problem.
	 * @param IFile $content the content that should be written into the file
	 */
	public function __construct($content){
		parent::__construct('avatar', 'application/octet-stream');
		$this->content = $content;

		$expires = new \DateTime('now + 2 hours');
		$this->addHeader('Expires', $expires->format(\DateTime::RFC1123));
		$this->addHeader('Cache-Control', 'private');
		$this->addHeader('Pragma', 'cache');
	}

	/**
	 * Simply sets the headers and returns the file contents
	 * @return string the file contents
	 */
	public function render(){
		return $this->content;
	}

}
