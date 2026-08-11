<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2021-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Service\HtmlPurify;

use HTMLPurifier_AttrTransform;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use OCP\IURLGenerator;

/**
 * Blocks urls in style attributes and backups original styles for restoring them later.
 */
class TransformStyleURLs extends HTMLPurifier_AttrTransform {
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
		if (!isset($attr['style']) || !str_contains($attr['style'], 'url(')) {
			return $attr;
		}

		// Check if there is a background image given
		$cssAttributes = explode(';', $attr['style']);

		$func = function ($cssAttribute) {
			if (preg_match('/\S/', $cssAttribute) === 0) {
				// empty or whitespace
				return '';
			}

			[$name, $value] = explode(':', $cssAttribute, 2);
			if (str_contains($value, 'url(')) {
				// Replace image URL
				$value = preg_replace('/url\(("|\')?http.*\)/i',
					'url(' . $this->urlGenerator->imagePath('mail', 'blocked-image.png') . ')',
					$value);
				return $name . ':' . $value;
			} else {
				return $cssAttribute;
			}
		};

		// Reassemble style
		$cssAttributes = array_map($func, $cssAttributes);
		$newStyle = implode(';', $cssAttributes);

		// Replace style if required
		if ($newStyle !== $attr['style']) {
			$attr['data-original-style'] = $attr['style'];
			$attr['style'] = $newStyle;
		}

		return $attr;
	}
}
