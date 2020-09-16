<?php

declare(strict_types=1);

/**
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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

namespace OCA\Mail\Search;

use OCA\Mail\AppInfo\Application;
use OCA\Mail\Contracts\IMailSearch;
use OCA\Mail\Db\Message;
use OCP\IDateTimeFormatter;
use OCP\IL10N;
use OCP\ILogger;
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

	/** @var ILogger */
	private $logger;

	public function __construct(IMailSearch $mailSearch,
								IL10N $l10n,
								ILogger $logger,
								IDateTimeFormatter $dateTimeFormatter,
								IURLGenerator $urlGenerator) {
		$this->mailSearch = $mailSearch;
		$this->l10n = $l10n;
		$this->logger = $logger;
		$this->dateTimeFormatter = $dateTimeFormatter;
		$this->urlGenerator = $urlGenerator;
	}

	public function getId(): string {
		return Application::APP_ID;
	}

	public function getName(): string {
		return $this->l10n->t('Mails');
	}

	public function getOrder(string $route, array $routeParameters): int {
		return 20;
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$cursor = $query->getCursor();

		// Search in local cache (doesn't contain the boday of emails)
		$messages = $this->mailSearch->findMessagesGlobally(
			$user,
			$query->getTerm(),
			empty($cursor) ? null : ((int) $cursor),
			$query->getLimit()
		);

		// Search in body of emails on the IMAP server
		$messages2 = $this->mailSearch->findMessagesLocally(
			$user,
			$query->getTerm(),
			empty($cursor) ? null : ((int) $cursor),
			$query->getLimit()
		);
		$this->logger->debug('********** DEBUG SEARCH ************');
		$this->logger->debug(implode('-', array_map(function (Message $message) {
			$message->getFrom();
			},$messages)));
		$this->logger->debug(implode('-', array_map(function (Message $message) {
			$message->getFrom();
			},$messages2)));
		$messages = array_merge($messages, $messages2);

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

				return new SearchResultEntry(
					'',
					$message->getSubject(),
					$subline,
					$this->urlGenerator->linkToRouteAbsolute('mail.page.thread', [
						'mailboxId' => $message->getMailboxId(),
						'id' => $message->getId(),
					]), // TODO: deep URL
					'icon-mail'
				);
			}, $messages),
			$last->getSentAt()
		);
	}
}
