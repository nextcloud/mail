<?php

declare(strict_types=1);

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

use HTMLPurifier_AttrTransform;
use HTMLPurifier_Config;
use HTMLPurifier_Context;
use HTMLPurifier_URIParser;

/**
 * Adds rel="noreferrer" to all outbound links.
 */
class TransformNoReferrer extends HTMLPurifier_AttrTransform {
	/** @var HTMLPurifier_URIParser */
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
		$url = $this->parser->parse($attr['href']);
		$scheme = $url->getSchemeObj($config, $context);

		if ($scheme->browsable && !$url->isLocal($config, $context)) {
			if (isset($attr['rel'])) {
				$rels = explode(' ', $attr['rel']);
				if (!in_array('noreferrer', $rels)) {
					$rels[] = 'noreferrer';
				}
				$attr['rel'] = implode(' ', $rels);
			} else {
				$attr['rel'] = 'noreferrer';
			}
		}
		return $attr;
	}
}
