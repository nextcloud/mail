<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2022 Anna Larch <anna.larch@gmx.net>
 *
 * @author Anna Larch <anna.larch@gmx.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Migration;

use Closure;
use OCA\Mail\Db\MessageMapper;
use OCP\Migration\IOutput;
use OCP\Migration\SimpleMigrationStep;
use Psr\Log\LoggerInterface;

class Version1140Date20221027171138 extends SimpleMigrationStep {
	private LoggerInterface $logger;
	private MessageMapper $messageMapper;

	public function __construct(MessageMapper $messageMapper,
		LoggerInterface $logger) {
		$this->logger = $logger;
		$this->messageMapper = $messageMapper;
	}

	/**
	 * @param IOutput $output
	 * @param Closure $schemaClosure The `\Closure` returns a `ISchemaWrapper`
	 * @param array $options
	 */
	public function postSchemaChange(IOutput $output, Closure $schemaClosure, array $options): void {
		if (!method_exists($this->messageMapper, 'resetPreviewDataFlag')) {
			$this->logger->warning('Service method missing due to in process upgrade');
			return;
		}
		$this->messageMapper->resetPreviewDataFlag();
	}
}
