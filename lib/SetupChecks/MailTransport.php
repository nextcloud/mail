<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\SetupChecks;

use OCP\IConfig;
use OCP\IL10N;
use OCP\SetupCheck\ISetupCheck;
use OCP\SetupCheck\SetupResult;

class MailTransport implements ISetupCheck {
	public function __construct(
		private IConfig $config,
		private IL10N $l10n,
	) {
	}

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('Mail Transport configuration');
	}

	#[\Override]
	public function getCategory(): string {
		return 'mail';
	}

	#[\Override]
	public function run(): SetupResult {
		$transport = $this->config->getSystemValueString('app.mail.transport', 'smtp');

		if ($transport === 'smtp') {
			return SetupResult::success();
		}

		return SetupResult::warning(
			$this->l10n->t('The app.mail.transport setting is not set to smtp. This configuration can cause issues with modern email security measures such as SPF and DKIM because emails are sent directly from the web server, which is often not properly configured for this purpose. To address this, we have discontinued support for the mail transport. Please remove app.mail.transport from your configuration to use the SMTP transport and hide this message. A properly configured SMTP setup is required to ensure email delivery.')
		);
	}
}
