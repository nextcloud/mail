<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\HtmlPurify;

use Closure;
use HTMLPurifier_Config;
use HTMLPurifier_URI;
use HTMLPurifier_URIFilter;
use HTMLPurifier_URIParser;
use OCP\IRequest;
use OCP\IURLGenerator;

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

	/** @var array */
	private $messageParameters;

	public function __construct(array $messageParameters,
		Closure $mapCidToAttachmentId,
		IURLGenerator $urlGenerator,
		IRequest $request) {
		$this->messageParameters = $messageParameters;
		$this->mapCidToAttachmentId = $mapCidToAttachmentId;
		$this->urlGenerator = $urlGenerator;
		$this->request = $request;
	}

	/**
	 * Transformator which will rewrite all HTTPS and HTTP urls to
	 *
	 * @param \HTMLPurifier_URI $uri
	 * @param HTMLPurifier_Config $config
	 * @param \HTMLPurifier_Context $context
	 * @return bool
	 */
	#[\Override]
	public function filter(&$uri, $config, $context) {

		if ($uri->scheme === null) {
			$uri->scheme = 'https';
		}

		// Only HTTPS and HTTP urls should get rewritten
		if ($uri->scheme === 'https' || $uri->scheme === 'http' || $uri->scheme === 'ftp') {
			$uri = $this->filterHttpFtp($uri, $context);
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
	private function filterHttpFtp(&$uri, $context) {
		$originalURL = urlencode($uri->scheme . '://' . $uri->host);

		// Add the port if it's not a default port
		if ($uri->port !== null
			&& !($uri->scheme === 'http' && $uri->port === 80)
			&& !($uri->scheme === 'https' && $uri->port === 443)
			&& !($uri->scheme === 'ftp' && $uri->port === 21)) {
			$originalURL = $originalURL . urlencode(':' . $uri->port);
		}

		$originalURL = $originalURL . urlencode($uri->path);

		if ($uri->query !== null) {
			$originalURL = $originalURL . urlencode('?' . $uri->query);
		}
		if ($uri->fragment !== null) {
			$originalURL = $originalURL . urlencode('#' . $uri->fragment);
		}

		// Get the HTML attribute
		$element = $context->exists('CurrentAttr') ? $context->get('CurrentAttr') : null;

		// If element is of type "href" it is most likely a link that should get redirected
		// otherwise it's an element that we send through our proxy
		if ($element === 'href') {
			return $uri;
		}

		return new \HTMLPurifier_URI(
			$this->request->getServerProtocol(),
			null, $this->request->getServerHost(),
			null,
			$this->urlGenerator->linkToRoute('mail.proxy.proxy'),
			'src=' . $originalURL . '&requesttoken=' . \OC::$server->getSession()->get('requesttoken'),
			null
		);
	}
}
