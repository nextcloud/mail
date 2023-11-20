<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2023 Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @author Richard Steinmetz <richard@steinmetz.cloud>
 *
 * @license AGPL-3.0-or-later
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Mail\Service;

use Horde\ManageSieve\Exception as ManageSieveException;
use JsonException;
use OCA\Mail\Db\Alias;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\CouldNotConnectException;
use OCA\Mail\Exception\OutOfOfficeParserException;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeParser;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeParserResult;
use OCA\Mail\Service\OutOfOffice\OutOfOfficeState;
use Psr\Log\LoggerInterface;

class OutOfOfficeService {
	public function __construct(
		private OutOfOfficeParser $outOfOfficeParser,
		private SieveService $sieveService,
		private LoggerInterface $logger,
		private AliasesService $aliasesService,
	) {
	}

	/**
	 * @throws ClientException
	 * @throws OutOfOfficeParserException
	 * @throws ManageSieveException
	 * @throws CouldNotConnectException
	 */
	public function parseState(MailAccount $account): OutOfOfficeParserResult {
		$script = $this->sieveService->getActiveScript($account->getUserId(), $account->getId());
		return $this->outOfOfficeParser->parseOutOfOfficeState($script->getScript());
	}

	/**
	 * @throws CouldNotConnectException
	 * @throws JsonException
	 * @throws ClientException
	 * @throws OutOfOfficeParserException
	 * @throws ManageSieveException
	 */
	public function update(MailAccount $account, OutOfOfficeState $state): void {
		$script = $this->sieveService->getActiveScript($account->getUserId(), $account->getId());
		$oldState = $this->outOfOfficeParser->parseOutOfOfficeState($script->getScript());
		$newScript = $this->outOfOfficeParser->buildSieveScript(
			$state,
			$oldState->getUntouchedSieveScript(),
			$this->buildAllowedRecipients($account),
		);
		try {
			$this->sieveService->updateActiveScript($account->getUserId(), $account->getId(), $newScript);
		} catch (ManageSieveException $e) {
			$this->logger->error('Failed to save sieve script: ' . $e->getMessage(), [
				'exception' => $e,
				'script' => $newScript,
			]);
			throw $e;
		}
	}

	/**
	 * @throws ClientException
	 * @throws OutOfOfficeParserException
	 * @throws ManageSieveException
	 * @throws CouldNotConnectException
	 * @throws JsonException
	 */
	public function disable(MailAccount $account): void {
		$state = $this->parseState($account)->getState();
		if ($state === null || !$state->isEnabled()) {
			return;
		}

		$state->setEnabled(false);
		$this->update($account, $state);
	}

	/**
	 * @return string[]
	 */
	private function buildAllowedRecipients(MailAccount $mailAccount): array {
		$aliases = $this->aliasesService->findAll($mailAccount->getId(), $mailAccount->getUserId());
		$formattedAliases = array_map(static function (Alias $alias) {
			return $alias->getAlias();
		}, $aliases);
		return array_merge([$mailAccount->getEmail()], $formattedAliases);
	}
}
