<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Migration;

use Closure;
use OCP\AppFramework\Services\IAppConfig;
use OCP\DB\ISchemaWrapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;

class Version3600Date20240227172825 extends SimpleMigrationStep {

	public function __construct(
		private IAppConfig $appConfig,
	) {
	}

	/**
	 * @param IOutput $output
	 * @param Closure(): ISchemaWrapper $schemaClosure
	 * @param array $options
	 * @return null|ISchemaWrapper
	 */
	#[\Override]
	public function changeSchema(IOutput $output, Closure $schemaClosure, array $options): ?ISchemaWrapper {
		$allowThreadSummary = $this->appConfig->getAppValue('enabled_thread_summary', 'no');
		$this->appConfig->deleteAppValue('enabled_thread_summary');
		if ($allowThreadSummary !== 'yes') {
			return null;
		}
		$allowSmartReplies = $this->appConfig->getAppValue('enabled_smart_reply', 'no');
		$this->appConfig->deleteAppValue('enabled_smart_reply');
		if ($allowSmartReplies !== 'yes') {
			return null;
		}

		$this->appConfig->setAppValue('llm_processing', 'yes');

		return null;
	}
}
