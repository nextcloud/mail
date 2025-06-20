<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\HtmlPurify;

use HTMLPurifier_AttrTransform;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_URIParser;
use OCP\IURLGenerator;
use OCP\Util;

/**
 * Adds copies src to data-src on all img tags.
 */
class TransformImageSrc extends HTMLPurifier_AttrTransform {
	/** @var HTMLPurifier_URIParser */
	private $parser;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(IURLGenerator $urlGenerator) {
		$this->parser = new HTMLPurifier_URIParser();
		$this->urlGenerator = $urlGenerator;
	}

	/**
	 * @param array $attr
	 * @param HTMLPurifier_Config $config
	 * @param HTMLPurifier_Context $context
	 * @return array
	 */
	#[\Override]
	public function transform($attr, $config, $context) {
		if ($context->get('CurrentToken')->name !== 'img'
			|| !isset($attr['src'])) {
			return $attr;
		}

		// Block tracking pixels
		if (isset($attr['width']) && isset($attr['height'])
			&& (int)$attr['width'] < 5 && (int)$attr['height'] < 5) {
			// Replace with a transparent png in case it's important for the layout
			$attr['src'] = $this->urlGenerator->imagePath('mail', 'blocked-image.png');
			$attr = $this->setDisplayNone($attr);
			return $attr;
		}

		// Do not block images attached to the email
		$url = $this->parser->parse($attr['src']);
		if ($url->host === Util::getServerHostName() && $url->path === $this->urlGenerator->linkToRoute('mail.proxy.proxy')) {
			$attr['data-original-src'] = $attr['src'];
			$attr['src'] = $this->urlGenerator->imagePath('mail', 'blocked-image.png');
			$attr = $this->setDisplayNone($attr);
		}
		return $attr;
	}

	/**
	 * @param array $attr
	 * @return array
	 *
	 * Sets html attribute style="display: none;", keeps old style
	 * attributes
	 */
	private function setDisplayNone(array $attr): array {
		if (isset($attr['style'])) {
			$attr['style'] = 'display: none;' . $attr['style']; // the space is important for jquery!
		} else {
			$attr['style'] = 'display: none;';
		}
		return $attr;
	}
}
