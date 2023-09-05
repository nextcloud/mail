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

namespace OCA\Mail\Dashboard;

use OCP\Dashboard\IAPIWidgetV2;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetItems;

/**
 * Requires Nextcloud >= 27.1.0
 */
abstract class MailWidgetV2 extends MailWidget implements IAPIWidgetV2, IButtonWidget {

	/**
	 * @inheritDoc
	 */
	public function getItemsV2(string $userId, ?string $since = null, int $limit = 7): WidgetItems {
		$items = $this->getItems($userId, $since, $limit);
		return new WidgetItems(
			$items,
			empty($items) ? $this->l10n->t('No message found yet') : '',
		);
	}

	/**
	 * @inheritDoc
	 */
	public function load(): void {
		// No assets need to be loaded anymore as the widget is rendered from the API
	}

	/**
	 * @inheritDoc
	 */
	public function getWidgetButtons(string $userId): array {
		$buttons = [];

		if ($this->userId !== null) {
			$accounts = $this->accountService->findByUserId($this->userId);
			if (empty($accounts)) {
				$buttons[] = new WidgetButton(
					WidgetButton::TYPE_SETUP,
					$this->urlGenerator->linkToRouteAbsolute('mail.page.setup'),
					$this->l10n->t('Set up an account'),
				);
			}
		}

		return $buttons;
	}
}
