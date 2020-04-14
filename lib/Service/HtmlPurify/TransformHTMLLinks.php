<?php

declare(strict_types=1);

/**
 * @author Jakob Sack <jakob@owncloud.org>
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
use HTMLPurifier_URIParser;

/**
 * Adds target="_blank" to all outbound links.
 */
class TransformHTMLLinks extends HTMLPurifier_AttrTransform {

	/**
	 * @type HTMLPurifier_URIParser
	 */
	private $parser;

	public function __construct() {
		$this->parser = new HTMLPurifier_URIParser();
	}

	/**
	 * @param array $attr
	 * @param HTMLPurifier_Config $config
	 * @param HTMLPurifier_Context $context
	 * @return array
	 */
	public function transform($attr, $config, $context) {
		if (!isset($attr['href'])) {
			return $attr;
		}

		// XXX Kind of inefficient
		$attr['target'] = '_blank';
		$attr['rel'] = 'noopener';

		return $attr;
	}
}
