<?php

/**
 * @author Jakob Sack <mail@jakobsack.de>
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

use OCA\Mail\Db\Avatar;
use OCA\Mail\Db\AvatarMapper;
use OCA\Mail\Service\AvatarSource\AddressbookSource;
use OCA\Mail\Service\AvatarSource\FaviconSource;
use OCA\Mail\Service\AvatarSource\GravatarSource;
use OCA\Mail\Service\AvatarSource\NoneSource;
use OCA\Mail\Service\ContactsIntegration;
use OCA\Mail\Storage\AvatarStorage;

use OCP\IURLGenerator;

class AvatarService {
	/** @var ContactsIntegration */
	private $contactsIntegration;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var AvatarMapper */
	private $mapper;

	/** @var AvatarStorage */
	private $storage;

	/** @var AddressbookSource */
	private $addressbookSource;

	/** @var FaviconSource */
	private $faviconSource;

	/** @var GravatarSource */
	private $gravatarSource;

	/** @var NoneSource */
	private $noneSource;

	public function __construct(AvatarMapper $avatarMapper,
								ContactsIntegration $contactsIntegration,
								IURLGenerator $urlGenerator,
								AvatarStorage $avatarStorage,
								AddressbookSource $addressbookSource,
								FaviconSource $faviconSource,
								GravatarSource $gravatarSource,
								NoneSource $noneSource) {
		$this->mapper = $avatarMapper;
		$this->contactsIntegration = $contactsIntegration;
		$this->urlGenerator = $urlGenerator;
		$this->storage = $avatarStorage;
		$this->addressbookSource = $addressbookSource;
		$this->faviconSource = $faviconSource;
		$this->gravatarSource = $gravatarSource;
		$this->noneSource = $noneSource;
	}

	public function rewriteUrl(Avatar $avatar) {
		if ($avatar === null) {
			return null;
		}

		$copy = new Avatar();
		$copy->setUserId($avatar->getUserId());
		$copy->setEmail($avatar->getEmail());
		$copy->setSource($avatar->getSource());
		$copy->setUpdatedAt($avatar->getUpdatedAt());
		$copy->setUrl('');

		if ($avatar->getSource() === 'addressbook') {
			$result = $this->contactsIntegration->getPhoto($avatar->getEmail());
			if ($result != null) {
				$copy->setUrl($result);
			}
		}
		elseif ($avatar->getUrl() !== '') {
			$copy->setUrl($this->urlGenerator->linkToRoute('mail.avatars.file', [ 'email' => $avatar->getEmail() ]));
		}

		return $copy;
	}

	public function loadFile($email) {
		return $this->storage->read($email);
	}

	public function loadFromCache($email, $userId) {
		// Find avatars (but not older than a week
		$result = $this->mapper->find($email, $userId);
		if (count($result) > 0 && time() - $result[0]->getUpdatedAt() < 604800) {
			return $result[0];
		}

		return null;
	}

	public function fetch($email, $userId) {
		// Try to fetch old data first
		$avatar = $this->loadFromCache($email, $userId);
		if (!is_null($avatar)) {
			return $avatar;
		}

		// our avatar is too old or does not exist
		if (is_null($avatar)) {
			$avatar = new Avatar();
			$avatar->setUserId($userId);
			$avatar->setEmail($email);
		}

		// 1) load the photo from the address book
		$result = $this->addressbookSource->fetch($email);
		if(!$result) $result = $this->gravatarSource->fetch($email);
		if(!$result) $result = $this->faviconSource->fetch($email);
		if(!$result) $result = $this->noneSource->fetch($email);

		$avatar->setSource($result['source']);
		$avatar->setUrl($result['url']);
		$avatar->setUpdatedAt(time());

		if ($avatar->getId() === null) {
			$this->mapper->insert($avatar);
		} else {
			$this->mapper->update($avatar);
		}

		return $avatar;
	}
}
