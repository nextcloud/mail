<?php

declare(strict_types=1);

/**
 * @author Lukas Reschke <lukas@statuscode.ch>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use DateTime;
use OCP\AppFramework\Http\DownloadResponse;

/**
 * @psalm-suppress MissingTemplateParam
 * @todo spec template with 28+
 */
class ProxyDownloadResponse extends DownloadResponse {
	/** @var string */
	private $content;

	/**
	 * Creates a response that prompts the user to download a file which
	 * contains the passed string
	 * Additionally the response will be cacheable by browsers. Since the content is
	 * generally not sensitive content (e.g. Logos in mails) this should not be a problem.
	 *
	 * @param string $content the content that should be written into the file
	 * @param string $filename the name that the downloaded file should have
	 * @param string $contentType the mimetype that the downloaded file should have
	 */
	public function __construct(string $content, string $filename, string $contentType) {
		parent::__construct($filename, $contentType);

		$this->content = $content;

		$now = (new DateTime('now'))->getTimestamp();
		$expires = (new DateTime('now + 11 months'))->getTimestamp();
		$this->cacheFor($expires - $now, false, true);
	}

	/**
	 * Simply sets the headers and returns the file contents
	 *
	 * @return string the file contents
	 */
	public function render(): string {
		return $this->content;
	}
}
