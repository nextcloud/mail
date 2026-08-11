<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
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

	#[\Override]
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
	#[\Override]
	public function doValidate(&$uri, $config, $context) {
		return true;
	}
}
