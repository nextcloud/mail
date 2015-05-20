<?php
/**
 * ownCloud - Mail
 *
 * @author Lukas Reschke
 * @copyright 2014 Lukas Reschke lukas@owncloud.org
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

namespace OCA\Mail\Http;

use OCP\AppFramework\Http\DownloadResponse;

class ProxyDownloadResponse extends DownloadResponse {

	private $content;

	/**
	 * Creates a response that prompts the user to download a file which
	 * contains the passed string
	 * Additionally the response will be cacheable by browsers. Since the content is
	 * generally not sensitive content (e.g. Logos in mails) this should not be a problem.
	 * @param string $content the content that should be written into the file
	 * @param string $filename the name that the downloaded file should have
	 * @param string $contentType the mimetype that the downloaded file should have
	 */
	public function __construct($content, $filename, $contentType){
		parent::__construct($filename, $contentType);
		$this->content = $content;

		$expires = new \DateTime('now + 11 months');
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
