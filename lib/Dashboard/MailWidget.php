<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Dashboard;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Db\Message;
use OCA\Mail\Exception\ClientException;
use OCA\Mail\Exception\ServiceException;
use OCA\Mail\Service\AccountService;
use OCA\Mail\Service\Search\SearchQuery;
use OCP\AppFramework\Services\IInitialState;
use OCP\Dashboard\IAPIWidget;
use OCP\Dashboard\IAPIWidgetV2;
use OCP\Dashboard\IButtonWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IOptionWidget;
use OCP\Dashboard\Model\WidgetButton;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Dashboard\Model\WidgetItems;
use OCP\Dashboard\Model\WidgetOptions;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;

abstract class MailWidget implements IAPIWidget, IAPIWidgetV2, IIconWidget, IOptionWidget, IButtonWidget {
	protected IURLGenerator $urlGenerator;
	protected IUserManager $userManager;
	protected AccountService $accountService;
	protected IMailSearch $mailSearch;
	protected IInitialState $initialState;
	protected ?string $userId;
	protected IL10N $l10n;

	public function __construct(IL10N $l10n,
		IURLGenerator $urlGenerator,
		IUserManager $userManager,
		AccountService $accountService,
		IMailSearch $mailSearch,
		IInitialState $initialState,
		?string $userId) {
		$this->urlGenerator = $urlGenerator;
		$this->userManager = $userManager;
		$this->accountService = $accountService;
		$this->mailSearch = $mailSearch;
		$this->initialState = $initialState;
		$this->userId = $userId;
		$this->l10n = $l10n;
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getOrder(): int {
		return 4;
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getIconClass(): string {
		return 'icon-mail';
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'mail-dark.svg')
		);
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getUrl(): ?string {
		return $this->urlGenerator->getAbsoluteURL($this->urlGenerator->linkToRoute('mail.page.index'));
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function load(): void {
		// No assets need to be loaded anymore as the widget is rendered from the API
	}

	abstract public function getSearchQuery(string $userId): SearchQuery;

	/**
	 * @param string $userId
	 * @param int|null $minTimestamp
	 * @param int $limit
	 * @return Message[]
	 * @throws ClientException
	 * @throws ServiceException
	 */
	protected function getEmails(string $userId, ?int $minTimestamp, int $limit = 7): array {
		$user = $this->userManager->get($userId);
		if ($user === null) {
			return [];
		}

		$query = $this->getSearchQuery($userId);
		if ($minTimestamp !== null) {
			$query->setStart((string)$minTimestamp);
		}

		return $this->mailSearch->findMessagesGlobally($user, $query, $limit);
	}

	protected function getMailboxIdsToExclude(string $userId): array {
		$mailboxIdsToExclude = [];

		foreach ($this->accountService->findByUserId($userId) as $account) {
			$mailboxIdsToExclude[] = $account->getMailAccount()->getJunkMailboxId();
			$mailboxIdsToExclude[] = $account->getMailAccount()->getTrashMailboxId();
		}

		return array_values(array_filter($mailboxIdsToExclude));
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
	public function getItems(string $userId, ?string $since = null, int $limit = 7): array {
		$intSince = $since === null ? null : (int)$since;
		$emails = $this->getEmails($userId, $intSince, $limit);

		/** @var list<WidgetItem> */
		return array_map(function (Message $email) {
			$firstFrom = $email->getFrom()->first();
			return new WidgetItem(
				$firstFrom ? $firstFrom->getLabel() : '',
				$email->getSubject(),
				$this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->linkToRoute('mail.page.thread', ['mailboxId' => $email->getMailboxId(), 'id' => $email->getId()])
				),
				$this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->linkToRoute('core.GuestAvatar.getAvatar', [
						'guestName' => $firstFrom
							? ($firstFrom->getLabel()
								? str_replace('/', '-', $firstFrom->getLabel())
								: $firstFrom->getEmail())
							: '',
						'size' => 44,
					])
				),
				(string)$email->getSentAt()
			);
		}, $emails);
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
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
	#[\Override]
	public function getWidgetOptions(): WidgetOptions {
		return new WidgetOptions(true);
	}

	/**
	 * @inheritDoc
	 */
	#[\Override]
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
