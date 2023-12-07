<?php

declare(strict_types=1);

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Matthias Rella <mrella@pisys.eu>
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

namespace OCA\Mail\Service\AutoCompletion;

use OCA\Mail\Db\CollectedAddress;
use OCA\Mail\Service\ContactsIntegration;
use OCA\Mail\Service\GroupsIntegration;

class AutoCompleteService {
	/** @var ContactsIntegration */
	private $contactsIntegration;

	/** @var GroupsIntegration */
	private $groupsIntegration;

	/** @var AddressCollector */
	private $addressCollector;

	public function __construct(ContactsIntegration $ci, GroupsIntegration $gi, AddressCollector $ac) {
		$this->contactsIntegration = $ci;
		$this->groupsIntegration = $gi;
		$this->addressCollector = $ac;
	}

	public function findMatches(string $userId, string $term): array {
		$recipientsFromContacts = $this->contactsIntegration->getMatchingRecipient($userId, $term);
		$recipientGroups = $this->groupsIntegration->getMatchingGroups($term);
		$fromCollector = $this->addressCollector->searchAddress($userId, $term);

		// Convert collected addresses into same format as CI creates
		$recipientsFromCollector = array_map(static function (CollectedAddress $address) {
			return [
				'id' => $address->getId(),
				'label' => $address->getDisplayName(),
				'email' => $address->getEmail(),
			];
		}, $fromCollector);

		return array_merge($recipientsFromContacts, $recipientsFromCollector, $recipientGroups);
	}
}
