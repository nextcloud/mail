<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Db;

use JsonSerializable;
use OCP\AppFramework\Db\Entity;
use ReturnTypeWillChange;

/**
 * @method string getName()
 * @method void setName(string $name)
 * @method void setOrder(int $order)
 * @method int getOrder()
 * @method void setActionId(int $actionId)
 * @method int getActionId()
 * @method string getParameter()
 * @method void setParameter(string $parameter)
 */
class ActionStep extends Entity implements JsonSerializable {
	protected $name;
	protected $order;
	protected $actionId;

	public function __construct() {
		$this->addType('name', 'string');
		$this->addType('order', 'integer');
		$this->addType('actionId', 'integer');
		$this->addType('parameter', 'string');
	}

	#[ReturnTypeWillChange]
	public function jsonSerialize() {
		return [
			'id' => $this->getId(),
			'name' => $this->getName(),
			'order' => $this->getOrder(),
			'actionId' => $this->getActionId(),
			'parameter' => $this->getParameter(),
		];
	}
}
