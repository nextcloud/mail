<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
