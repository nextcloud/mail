<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Mail\Provider;

use OCP\Mail\Provider\IServiceIdentity;

class MailServiceIdentity implements IServiceIdentity {

	/**
	 * An arbitrary unique text string identifying this credential type
	 *
	 * @since 2024.05.25
	 *
	 * @return string
	 */
	public function type(): string {
		
		return 'Internal';

	}

}
