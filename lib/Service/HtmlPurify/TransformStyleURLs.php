<?php

declare(strict_types=1);

/**
 * @author Jakob Sack <jakob@owncloud.org>
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
	public function transform($attr, $config, $context) {
		if (!isset($attr['style']) || strpos($attr['style'], 'url(') === false) {
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
			if (strpos($value, 'url(') !== false) {
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
