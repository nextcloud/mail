<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <wurst.christoph@gmail.com>
 * @author Lukas Reschke <lukas@owncloud.com>
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

use OCP\Util;
use OCP\AppFramework\Http\Response;

class HtmlResponse extends Response {

	/** @var string */
	private $content;

	/** @var bool */
	private $plain;

	/**
	 * @param string $content message html content
	 * @param bool $plain do not inject scripts if true (default=false)
	 */
	public function __construct(string $content, bool $plain=false) {
		parent::__construct();
		$this->content = $content;
		$this->plain = $plain;
	}

	/**
	 * Inject scripts if not plain and return message html content.
	 *
	 * @return string message html content
	 */
	public function render(): string {
		if ($this->plain) {
			return $this->content;
		}

		$nonce = \OC::$server->getContentSecurityPolicyNonceManager()->getNonce();
		$scriptSrc = Util::linkToAbsolute('mail', 'js/htmlresponse.js');
		return '<script nonce="' . $nonce. '" src="' . $scriptSrc . '"></script>'
			.  $this->content;
	}
}
