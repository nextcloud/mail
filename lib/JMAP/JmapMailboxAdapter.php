<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Mail\JMAP;

use JmapClient\Requests\Mail\MailboxParameters as MailboxParametersRequest;
use JmapClient\Responses\Mail\MailboxParameters as MailboxParametersResponse;
use JsonException;
use OCA\Mail\Db\Mailbox;

class JmapMailboxAdapter {
	private const DELIMITER = '/';

	/**
	 * @throws JsonException
	 */
	public function convertToMailbox(MailboxParametersResponse $response): Mailbox {
		$mailbox = new Mailbox();
		$mailbox->setName($response->label() ?? $response->id() ?? '');
		$mailbox->setNameHash(md5($response->id()));
		$mailbox->setRemoteParentId($response->in());
		$mailbox->setRemoteId($response->id());
		$mailbox->setState(null);
		$mailbox->setAttributes(json_encode($this->convertToAttributes($response), JSON_THROW_ON_ERROR));
		$mailbox->setDelimiter(self::DELIMITER);
		$mailbox->setMessages($response->objectsTotal() ?? 0);
		$mailbox->setUnseen($response->objectsUnseen() ?? 0);
		$mailbox->setSelectable($response->rights()?->readItems() === true);
		$mailbox->setSpecialUse(json_encode($this->convertToSpecialUse($response), JSON_THROW_ON_ERROR));
		$mailbox->setMyAcls($this->convertToAcl($response));
		$mailbox->setShared(false);

		return $mailbox;
	}

	/**
	 * @throws JsonException
	 */
	public function convertFromMailbox(Mailbox $mailbox, array $patch = []): MailboxParametersRequest {
		$properties = ['location', 'name', 'subscribed', 'role', 'rights'];
		if (!empty($patch)) {
			$properties = array_intersect($properties, $patch);
		}

		$request = new MailboxParametersRequest();

		if (in_array('location', $properties, true)) {
			$request->in($mailbox->getRemoteParentId());
		}
		if (in_array('name', $properties, true)) {
			$request->label($mailbox->getName());
		}
		if (in_array('subscribed', $properties, true)) {
			$request->subscribed(str_contains($mailbox->getAttributes() ?? '', '\\subscribed'));
		}
		if (in_array('role', $properties, true)) {
			$specialUse = json_decode($mailbox->getSpecialUse() ?? '[]', true) ?? [];
			$role = $this->convertFromSpecialUse($specialUse);
			$request->role($role);
		}
		if (in_array('rights', $properties, true)) {
			$acls = $mailbox->getMyAcls();
			$request->rights(new MailboxRights(
				readItems: str_contains($acls ?? '', 'l') || str_contains($acls ?? '', 'r') || str_contains($acls ?? '', 'a'),
				addItems: str_contains($acls ?? '', 'i') || str_contains($acls ?? '', 'a'),
				removeItems: str_contains($acls ?? '', 't') || str_contains($acls ?? '', 'e') || str_contains($acls ?? '', 'a'),
				setSeen: str_contains($acls ?? '', 's') || str_contains($acls ?? '', 'a'),
				setKeywords: str_contains($acls ?? '', 'w') || str_contains($acls ?? '', 'a'),
				createChild: str_contains($acls ?? '', 'k') || str_contains($acls ?? '', 'a'),
				rename: str_contains($acls ?? '', 'x') || str_contains($acls ?? '', 'a'),
				delete: str_contains($acls ?? '', 'x') || str_contains($acls ?? '', 'a'),
				submit: str_contains($acls ?? '', 'p') || str_contains($acls ?? '', 'a'),
			));
		}

		return $request;
	}

	/**
	 * @return string[]
	 */
	private function convertToAttributes(MailboxParametersResponse $response): array {
		$attributes = [];

		if ($response->subscribed() !== false) {
			$attributes[] = '\\subscribed';
		}

		$role = $response->role();
		if ($role !== null && $role !== '') {
			$attributes[] = '\\' . $this->normalizeSpecialUse($role);
		}

		if ($response->rights()?->readItems() !== true) {
			$attributes[] = '\\noselect';
		}

		return $attributes;
	}

	/**
	 * @return string[]
	 */
	private function convertToSpecialUse(MailboxParametersResponse $response): array {
		$role = $response->role();
		if ($role === null || $role === '') {
			return [];
		}

		return [$this->normalizeSpecialUse($role)];
	}

	/**
	 * @param string[] $specialUse
	 */
	private function convertFromSpecialUse(array $specialUse): ?string {
		$role = $specialUse[0] ?? null;
		if ($role === null) {
			return null;
		}

		$role = strtolower(trim($role, '\\'));
		if ($role === 'flagged') {
			return 'important';
		}

		$allowed = ['all', 'archive', 'drafts', 'important', 'inbox', 'junk', 'sent', 'trash'];

		return in_array($role, $allowed, true) ? $role : null;
	}

	private function normalizeSpecialUse(string $role): string {
		$role = strtolower($role);

		return $role === 'important' ? 'flagged' : $role;
	}

	private function convertToAcl(MailboxParametersResponse $response): ?string {
		$rights = $response->rights();
		if ($rights === null) {
			return null;
		}

		$acls = '';
		if ($rights->readItems() === true) {
			$acls .= 'lr';
		}
		if ($rights->addItems() === true) {
			$acls .= 'i';
		}
		if ($rights->removeItems() === true) {
			$acls .= 'te';
		}
		if ($rights->setSeen() === true) {
			$acls .= 's';
		}
		if ($rights->setKeywords() === true) {
			$acls .= 'w';
		}
		if ($rights->createChild() === true) {
			$acls .= 'k';
		}
		if ($rights->rename() === true || $rights->delete() === true) {
			$acls .= 'x';
		}
		if ($rights->submit() === true) {
			$acls .= 'p';
		}
		if ($rights->createChild() === true && $rights->rename() === true && $rights->delete() === true) {
			$acls .= 'a';
		}

		return $acls === '' ? null : $acls;
	}

}
