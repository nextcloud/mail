<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\Mail\Migration;

use Horde_Mail_Exception;
use Horde_Mail_Rfc822_Address;
use OCA\Mail\Db\CollectedAddress;
use OCA\Mail\Db\CollectedAddressMapper;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class FixCollectedAddresses implements IRepairStep {
	/** @var CollectedAddressMapper */
	private $mapper;

	public function __construct(CollectedAddressMapper $mapper) {
		$this->mapper = $mapper;
	}

	#[\Override]
	public function getName(): string {
		return 'Purify and migrate collected mail addresses';
	}

	/**
	 * @return void
	 */
	#[\Override]
	public function run(IOutput $output) {
		$nrOfAddresses = $this->mapper->getTotal();
		$output->startProgress($nrOfAddresses);

		$chunk = $this->mapper->getChunk();
		while (count($chunk) > 0) {
			foreach ($chunk as $address) {
				/* @var $address CollectedAddress */
				$maxId = $address->getId();
				$this->fixAddress($address, $output);
			}

			$output->advance(count($chunk));
			$chunk = $this->mapper->getChunk($maxId + 1);
		}
		$output->finishProgress();
	}

	/**
	 * @return void
	 */
	private function fixAddress(CollectedAddress $address, IOutput $output) {
		if (!is_null($address->getDisplayName())) {
			// Nothing to fix
			return;
		}

		try {
			$hordeAddress = new Horde_Mail_Rfc822_Address($address->getEmail());
			if (!$hordeAddress->valid) {
				throw new Horde_Mail_Exception();
			}
			$address->setDisplayName($hordeAddress->label);
			$address->setEmail($hordeAddress->bare_address);
			$this->mapper->update($address);
		} catch (Horde_Mail_Exception $ex) {
			$output->warning('removed invalid address <' . $address->getEmail() . '>');
			// Invalid address, let's delete it to prevent further errors
			$this->mapper->delete($address);
		}
	}
}
