<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * ownCloud - Mail
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

namespace OCA\Mail\Service\AutoCompletion;

use OCA\Mail\Service\ContactsIntegration;

class AutoCompleteService {

	/** @var ContactsIntegration */
	private $contactsIntegration;

	/** @var AddressCollector */
	private $addressCollector;

	public function __construct(ContactsIntegration $ci, AddressCollector $ac) {
		$this->contactsIntegration = $ci;
		$this->addressCollector = $ac;
	}

	public function findMatches($term) {
		$recipientsFromContacts = $this->contactsIntegration->getMatchingRecipient($term);
		$fromCollector = $this->addressCollector->searchAddress($term);

		// Convert collected addresses into same format as CI creates
		$recipientsFromCollector = array_map(function ($address) {
			return [
				'id' => $address->getId(),
				'label' => $address->getEmail(),
				'value' => $address->getEmail(),
			];
		}, $fromCollector);

		return array_merge($recipientsFromContacts, $recipientsFromCollector);
	}

}
