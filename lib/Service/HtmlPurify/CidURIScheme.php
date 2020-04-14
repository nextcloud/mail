<?php

declare(strict_types=1);

/**
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
use HTMLPurifier_Context;
use HTMLPurifier_URI;
use HTMLPurifier_URIScheme;

class CidURIScheme extends HTMLPurifier_URIScheme {
	public $default_port = null;
	public $browsable = true;
	public $hierarchical = true;

	public function validate(&$uri, $config, $context) {
		return true;
	}

	/**
	 * Validates the components of a URI for a specific scheme.
	 *
	 * @param HTMLPurifier_URI $uri Reference to a HTMLPurifier_URI object
	 * @param HTMLPurifier_Config $config
	 * @param HTMLPurifier_Context $context
	 * @return bool success or failure
	 */
	public function doValidate(&$uri, $config, $context) {
		return true;
	}
}
