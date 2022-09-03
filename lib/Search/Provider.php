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

	public function __construct(IMailSearch $mailSearch,
								IL10N $l10n,
								IDateTimeFormatter $dateTimeFormatter,
								IURLGenerator $urlGenerator) {
		$this->mailSearch = $mailSearch;
		$this->l10n = $l10n;
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
		if (strpos($route, Application::APP_ID . '.') === 0) {
			// Active app, prefer Mail results
			return -1;
		}

		return 20;
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		$cursor = $query->getCursor();
		$messages = $this->mailSearch->findMessagesGlobally(
			$user,
			$query->getTerm(),
			empty($cursor) ? null : ((int) $cursor),
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
