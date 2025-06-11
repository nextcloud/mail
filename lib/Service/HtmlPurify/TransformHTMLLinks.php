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
use OCP\IURLGenerator;

/**
 * Adds target="_blank" to all outbound links.
 */
class TransformHTMLLinks extends HTMLPurifier_AttrTransform {
	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(IURLGenerator $urlGenerator) {
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
		if (!isset($attr['href'])) {
			return $attr;
		}

		$attr['target'] = '_blank';
		$attr['rel'] = 'external noopener noreferrer';

		// Open mailto: links in Mail
		if (stripos($attr['href'], 'mailto:') === 0) {
			$attr['href'] = $this->urlGenerator->linkToRoute('mail.page.mailto') . '?to=' . substr($attr['href'], 7);
		}

		return $attr;
	}
}
