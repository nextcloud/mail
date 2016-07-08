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

namespace OCA\Mail\Model;

use Horde_Imap_Client_Data_Envelope;
use Horde_Mail_Rfc822_List;
use Horde_Mail_Rfc822_Address;

trait ConvertAddresses {

	private function hordeToString(Horde_Mail_Rfc822_Address $address) {
		return $address->writeAddress();
	}

	private function hordeToAssoc(Horde_Mail_Rfc822_Address $address) {
		return [
			'label' => $address->label,
			'email' => $address->bare_address,
		];
	}

	/**
	 * Convert horde mail address list to array of strings
	 *
	 * @param Horde_Mail_Rfc822_List $list
	 * @return string[]
	 */
	protected function hordeListToStringArray(Horde_Mail_Rfc822_List $list) {
		$addresses = [];
		foreach ($list as $address) {
			$addresses[] = $this->hordeToString($address);
		}
		return $addresses;
	}

	/**
	 * @param Horde_Mail_Rfc822_List $list
	 * @return array
	 */
	protected function hordeListToAssocArray(Horde_Mail_Rfc822_List $list) {
		$addresses = [];
		foreach ($list as $address) {
			$addresses[] = $this->hordeToAssoc($address);
		}
		return $addresses;
	}

	/**
	 * @param Horde_Imap_Client_Data_Envelope|Horde_Mail_Rfc822_List $envelope
	 * @return array
	 */
	protected function convertAddressList($envelope) {
		$list = [];
		foreach ($envelope as $t) {
			$list[] = $this->hordeToAssoc($t);
		}
		return $list;
	}

}
