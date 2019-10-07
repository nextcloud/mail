<?php declare(strict_types=1);

/**
 * @copyright 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2019 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCA\Mail\Db;

use Horde_Imap_Client;
use OCA\Mail\Folder;
use OCP\AppFramework\Db\Entity;
use function in_array;
use function json_decode;
use function ltrim;
use function rtrim;
use function strtolower;

/**
 * @method string getName()
 * @method void setName(string $name)
 * @method int getAccountId()
 * @method void setAccountId(int $accountId)
 * @method string|null getSyncToken()
 * @method void setSyncToken(string|null $syncToken)
 * @method string getAttributes()
 * @method void setAttributes(string $attributes)
 * @method string getDelimiter()
 * @method void setDelimiter(string $delimiter)
 * @method int getMessages()
 * @method void setMessages(int $messages)
 * @method int getUnseen()
 * @method void setUnseen(int $unseen)
 * @method bool getSelectable()
 * @method void setSelectable(bool $selectable)
 * @method string getSpecialUse()
 * @method void setSpecialUse(string $specialUse)
 */
class Mailbox extends Entity {

	protected $name;
	protected $accountId;
	protected $syncToken;
	protected $attributes;
	protected $delimiter;
	protected $messages;
	protected $unseen;
	protected $selectable;
	protected $specialUse;

	public function __construct() {
		$this->addType('accountId', 'integer');
		$this->addType('messages', 'integer');
		$this->addType('unseen', 'integer');
		$this->addType('selectable', 'boolean');
	}

	public function toFolder(): Folder {
		$folder = new Folder(
			$this->accountId,
			new \Horde_Imap_Client_Mailbox($this->name),
			json_decode($this->getAttributes() ?? '[]', true) ?? [],
			$this->delimiter
		);
		$folder->setSyncToken($this->getSyncToken());
		foreach ($this->getSpecialUseParsed() as $use) {
			$folder->addSpecialUse($use);
		}
		return $folder;
	}

	private function getSpecialUseParsed(): array {
		return json_decode($this->getSpecialUse() ?? '[]', true) ?? [];
	}

	public function isSpecialUse(string $specialUse): bool {
		return in_array(
			ltrim(
				strtolower($specialUse),
				'\\'
			),
			array_map("strtolower", $this->getSpecialUseParsed()),
			true
		);
	}

	public function getMailbox(): string {
		// TODO: evaluate if SPECIALUSE_FLAGGED can also be set on a real IMAP mailbox
		// because then this could be problematic if they name also ends with /FLAGGED
		// though, this sounds very unlikely
		if ($this->isSpecialUse(Horde_Imap_Client::SPECIALUSE_FLAGGED)) {
			return rtrim($this->getName(), '/FLAGGED');
		}
		return $this->getName();
	}

}
