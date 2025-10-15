<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\Search;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Db\Message;
use OCA\Mail\Service\Search\FilterStringParser;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use OCP\Search\SearchResultEntry;
use function array_map;

class Provider implements IProvider {
	/** @var IMailSearch */
	private $mailSearch;

	/** @var IL10N */
	private $l10n;

	/** @var IDateTimeFormatter */
	private $dateTimeFormatter;

	/** @var IURLGenerator */
	private $urlGenerator;

	public function __construct(
		IMailSearch $mailSearch,
		IL10N $l10n,
		IDateTimeFormatter $dateTimeFormatter,
		IURLGenerator $urlGenerator,
		private FilterStringParser $filterStringParser,
	) {
		$this->mailSearch = $mailSearch;
		$this->l10n = $l10n;
		$this->dateTimeFormatter = $dateTimeFormatter;
		$this->urlGenerator = $urlGenerator;
	}

	#[\Override]
	public function getId(): string {
		return Application::APP_ID;
	}

	#[\Override]
	public function getName(): string {
		return $this->l10n->t('Mails');
	}

	#[\Override]
	public function getOrder(string $route, array $routeParameters): int {
		if (str_starts_with($route, Application::APP_ID . '.')) {
			// Active app, prefer Mail results
			return -1;
		}

		return 20;
	}

	#[\Override]
	public function search(IUser $user, ISearchQuery $query): SearchResult {
		return $this->searchByFilter($user, $query, $query->getTerm());
	}

	protected function searchByFilter(IUser $user, ISearchQuery $query, string $filter): SearchResult {
		$mailQuery = $this->filterStringParser->parse($filter);

		$cursor = $query->getCursor();
		if ($cursor !== null) {
			$mailQuery->setCursor((int)$cursor);
		}

		$messages = $this->mailSearch->findMessagesGlobally(
			$user,
			$mailQuery,
			$query->getLimit()
		);

		$last = end($messages);
		if ($last === false) {
			return SearchResult::complete(
				$this->getName(),
				[]
			);
		}

		return SearchResult::paginated(
			$this->getName(),
			array_map(function (Message $message) {
				$formattedDate = $this->dateTimeFormatter->formatDateTimeRelativeDay($message->getSentAt(), 'short');
				$sender = $message->getFrom()->first();

				if ($sender !== null && $sender->getLabel() !== null) {
					$subline = $sender->getLabel() . ' – ' . $formattedDate;
				} else {
					$subline = $formattedDate;
				}

				if ($sender !== null && $sender->getEmail() !== null) {
					$from = $sender->getEmail();
				} else {
					$from = null;
				}

				return new SearchResultEntry(
					is_null($from) ? '' : $this->urlGenerator->linkToRoute('mail.avatars.image', [
						'email' => $from,
					]),
					$message->getSubject(),
					$subline,
					$this->urlGenerator->linkToRouteAbsolute('mail.page.thread', [
						'mailboxId' => $message->getMailboxId(),
						'id' => $message->getId(),
					]), // TODO: deep URL
					'icon-mail',
					!is_null($from)
				);
			}, $messages),
			$last->getSentAt()
		);
	}
}
