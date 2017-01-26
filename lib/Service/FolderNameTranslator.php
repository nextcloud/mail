<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * Mail
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\Mail\Service;

use OCA\Mail\Folder;
use OCP\IL10N;

class FolderNameTranslator {

	/** @var IL10N */
	private $l10n;

	/** @var string[] */
	private $translations = [];

	/**
	 * @param IL10N $l10n
	 */
	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
	}

	/**
	 * @return string[]
	 */
	private function buildTranslations() {
		if (empty($this->translations)) {
			$this->translations = [
				// TRANSLATORS: translated mail box name
				'inbox' => $this->l10n->t('Inbox'),
				// TRANSLATORS: translated mail box name
				'sent' => $this->l10n->t('Sent'),
				// TRANSLATORS: translated mail box name
				'drafts' => $this->l10n->t('Drafts'),
				// TRANSLATORS: translated mail box name
				'archive' => $this->l10n->t('Archive'),
				// TRANSLATORS: translated mail box name
				'trash' => $this->l10n->t('Trash'),
				// TRANSLATORS: translated mail box name
				'junk' => $this->l10n->t('Junk'),
				// TRANSLATORS: translated mail box name
				'all' => $this->l10n->t('All'),
				// TRANSLATORS: translated mail box name
				'flagged' => $this->l10n->t('Favorites'),
			];
		}
		return $this->translations;
	}

	/**
	 * @param Folder[] $folders
	 */
	public function translateAll(array $folders) {
		foreach ($folders as $folder) {
			$this->translate($folder);
		}
	}

	/**
	 * @param Folder $folder
	 */
	public function translate(Folder $folder) {
		$translations = $this->buildTranslations();
		// TODO: only list "best" one per type? e.g. only one inbox
		$specialUses = $folder->getSpecialUse();
		$specialUse = count($specialUses) > 0 ? reset($specialUses) : null;
		if (!is_null($specialUse) && isset($translations[$specialUse])) {
			$folder->setDisplayName($translations[$specialUse]);
		} else {
			$folder->setDisplayName($folder->getMailbox());
		}
	}

}
