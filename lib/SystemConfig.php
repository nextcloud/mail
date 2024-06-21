<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail;

use OCP\IConfig;

class SystemConfig {
	/** @var IConfig */
	private $config;

	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	public function hasWorkingSmtp(): bool {
		return $this->config->getSystemValue('app.mail.transport', 'smtp') === 'smtp';
	}
}
