<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Service\HtmlPurify;

use HTMLPurifier_Config;
use HTMLPurifier_URI;
use HTMLPurifier_URIFilter;
use HTMLPurifier_URIParser;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Util;

class TransformURLScheme extends HTMLPurifier_URIFilter {

	public $name = 'TransformURLScheme';
	public $post = true;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var IRequest */
	private $request;

	/**
	 * @var \Closure
	 */
	private $mapCidToAttachmentId;

	public function __construct($messageParameters, \Closure $mapCidToAttachmentId,
		IURLGenerator $urlGenerator, IRequest $request) {
		$this->messageParameters = $messageParameters;
		$this->mapCidToAttachmentId = $mapCidToAttachmentId;
		$this->urlGenerator = $urlGenerator;
		$this->request = $request;
	}

	/**
	 * Transformator which will rewrite all HTTPS and HTTP urls to
	 * @param \HTMLPurifier_URI $uri
	 * @param HTMLPurifier_Config $config
	 * @param \HTMLPurifier_Context $context
	 * @return bool
	 */
	public function filter(&$uri, $config, $context) {
		/** @var \HTMLPurifier_Context $context */
		/** @var \HTMLPurifier_Config $config */
		// Only HTTPS and HTTP urls should get rewritten
		if ($uri->scheme === 'https' || $uri->scheme === 'http') {
			$uri = $this->filterHttp($uri, $context);
		}

		if ($uri->scheme === 'cid') {
			$attachmentId = $this->mapCidToAttachmentId->__invoke($uri->path);
			if (is_null($attachmentId)) {
				return true;
			}
			$this->messageParameters['attachmentId'] = $attachmentId;

			$imgUrl = $this->urlGenerator->linkToRouteAbsolute('mail.messages.downloadAttachment',
				$this->messageParameters);
			$parser = new HTMLPurifier_URIParser();
			$uri = $parser->parse($imgUrl);
		}

		return true;
	}

	/**
	 * @param HTMLPurifier_URI $uri
	 * @param \HTMLPurifier_Context $context
	 * @return HTMLPurifier_URI
	 */
	private function filterHttp(&$uri, $context) {
		$originalURL = urlencode($uri->scheme . '://' . $uri->host . $uri->path);
		if ($uri->query !== null) {
			$originalURL = $originalURL . urlencode('?' . $uri->query);
		}

		// Get the HTML attribute
		$element = $context->get('CurrentAttr');

		// If element is of type "href" it is most likely a link that should get redirected
		// otherwise it's an element that we send through our proxy
		if ($element === 'href') {
			$uri = new \HTMLPurifier_URI(
				$this->request->getServerProtocol(), null, $this->request->getServerHost(), null,
				$this->urlGenerator->linkToRoute('mail.proxy.redirect'),
				'src=' . $originalURL, null);
			return $uri;
		} else {
			$uri = new \HTMLPurifier_URI(
				$this->request->getServerProtocol(), null, $this->request->getServerHost(), null,
				$this->urlGenerator->linkToRoute('mail.proxy.proxy'),
				'src=' . $originalURL . '&requesttoken=' . \OC::$server->getSession()->get('requesttoken'),
				null);
			return $uri;
		}
	}

}
