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
use OCP\AppFramework\Services\IInitialState;
use OCP\Dashboard\IAPIWidget;
use OCP\Dashboard\IIconWidget;
use OCP\Dashboard\IOptionWidget;
use OCP\Dashboard\Model\WidgetItem;
use OCP\Dashboard\Model\WidgetOptions;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\Util;

abstract class MailWidget implements IAPIWidget, IIconWidget, IOptionWidget {
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
	public function getIconUrl(): string {
		return $this->urlGenerator->getAbsoluteURL(
			$this->urlGenerator->imagePath(Application::APP_ID, 'mail.svg')
		);
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

	/**
	 * Get widget-specific search filter
	 * @return string
	 */
	abstract public function getSearchFilter(): string;

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
		$filter = $this->getSearchFilter();
		$emails = $this->mailSearch->findMessagesGlobally($user, $filter, null, $limit);

		if ($minTimestamp !== null) {
			return array_filter($emails, static function (Message $email) use ($minTimestamp) {
				return $email->getSentAt() > $minTimestamp;
			});
		}

		return $emails;
	}

	/**
	 * @inheritDoc
	 */
	public function getItems(string $userId, ?string $since = null, int $limit = 7): array {
		$intSince = $since === null ? null : (int) $since;
		$emails = $this->getEmails($userId, $intSince, $limit);

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
				(string) $email->getSentAt()
			);
		}, $emails);
	}

	/**
	 * @inheritDoc
	 */
	public function getWidgetOptions(): WidgetOptions {
		return new WidgetOptions(true);
	}
}
