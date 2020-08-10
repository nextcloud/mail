<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2020 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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

namespace OCA\Mail\Dashboard;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Service\AccountService;
use OCP\AppFramework\Services\IInitialState;
use OCP\Dashboard\IWidget;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Util;

class MailWidget implements IWidget {

	/** @var IL10N */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var AccountService */
	private $accountService;

	/** @var IInitialState */
	private $initialState;

	/** @var string|null */
	private $userId;

	public function __construct(IL10N $l10n,
								IURLGenerator $urlGenerator,
								AccountService $accountService,
								IInitialState $initialState,
								?string $userId) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->accountService = $accountService;
		$this->initialState = $initialState;
		$this->userId = $userId;
	}

	/**
	 * @inheritDoc
	 */
	public function getId(): string {
		return Application::APP_ID;
	}

	/**
	 * @inheritDoc
	 */
	public function getTitle(): string {
		return $this->l10n->t('Important mail');
	}

	/**
	 * @inheritDoc
	 */
	public function getOrder(): int {
		return 4;
	}

	/**
	 * @inheritDoc
	 */
	public function getIconClass(): string {
		return 'icon-mail';
	}

	/**
	 * @inheritDoc
	 */
	public function getUrl(): ?string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkToRoute('mail.page.index'));
	}

	/**
	 * @inheritDoc
	 */
	public function load(): void {
		Util::addScript(Application::APP_ID, 'dashboard');

		$this->initialState->provideInitialState(
			'mail-accounts',
			$this->accountService->findByUserId($this->userId)
		);
	}
}
