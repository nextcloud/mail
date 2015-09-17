<?php

/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 * Copyright (c) 2015 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

namespace OCA\Mail;

use Horde_Imap_Client_Ids;
use Horde_Imap_Client_Mailbox;
use Horde_Imap_Client_Socket;
use Horde_Imap_Client;
use Horde_Mail_Rfc822_Address;
use Horde_Mail_Transport;
use Horde_Mail_Transport_Mail;
use Horde_Mail_Transport_Smtphorde;
use Horde_Mime_Mail;
use OCA\Mail\Cache\Cache;
use OCA\Mail\Db\MailAccount;
use OCA\Mail\Model\IMessage;
use OCA\Mail\Model\Message;
use OCA\Mail\Model\ReplyMessage;
use OCA\Mail\Service\IAccount;
use OCA\Mail\Service\IMailBox;
use OCP\IConfig;
use OCP\ICacheFactory;
use OCP\Security\ICrypto;

class Account implements IAccount {

	/** @var MailAccount */
	private $account;

	/** @var Mailbox[]|null */
	private $mailboxes;

	/** @var Horde_Imap_Client_Socket */
	private $client;

	/** @var ICrypto */
	private $crypto;

	/** @var IConfig */
	private $config;

	/** @var ICacheFactory */
	private $memcacheFactory;

	/**
	 * @param MailAccount $account
	 */
	public function __construct(MailAccount $account) {
		$this->account = $account;
		$this->mailboxes = null;
		$this->crypto = \OC::$server->getCrypto();
		$this->config = \OC::$server->getConfig();
		$this->memcacheFactory = \OC::$server->getMemcacheFactory();
	}

	/**
	 * @return int
	 */
	public function getId() {
		return $this->account->getId();
	}

	/**
	 * @return string
	 */
	public function getName() {
		return $this->account->getName();
	}

	/**
	 * @return string
	 */
	public function getEMailAddress() {
		return $this->account->getEmail();
	}

	/**
	 * @return Horde_Imap_Client_Socket
	 */
	public function getImapConnection() {
		if (is_null($this->client)) {
			$host = $this->account->getInboundHost();
			$user = $this->account->getInboundUser();
			$password = $this->account->getInboundPassword();
			$password = $this->crypto->decrypt($password);
			$port = $this->account->getInboundPort();
			$ssl_mode = $this->convertSslMode($this->account->getInboundSslMode());

			$params = [
				'username' => $user,
				'password' => $password,
				'hostspec' => $host,
				'port' => $port,
				'secure' => $ssl_mode,
				'timeout' => 20,
			];
			if ($this->config->getSystemValue('app.mail.imaplog.enabled', false)) {
				$params['debug'] = $this->config->getSystemValue('datadirectory') . '/horde.log';
			}
			if ($this->config->getSystemValue('app.mail.server-side-cache.enabled', false)) {
				if ($this->memcacheFactory->isAvailable()) {
					$params['cache'] = [
						'backend' => new Cache(array(
							'cacheob' => $this->memcacheFactory
								->create(md5($this->getId() . $this->getEMailAddress()))
						))];
				}
			}
			$this->client = new \Horde_Imap_Client_Socket($params);
			$this->client->login();
		}
		return $this->client;
	}

	/**
	 * @param string $mailBox
	 * @return Mailbox
	 */
	public function createMailbox($mailBox) {
		$conn = $this->getImapConnection();
		$conn->createMailbox($mailBox);
		$this->mailboxes = null;

		return $this->getMailbox($mailBox);
	}

	/**
	 * Send a new message or reply to an existing message
	 *
	 * @param IMessage $message
	 * @param int|null $draftUID
	 */
	public function sendMessage(IMessage $message, $draftUID) {
		// build mime body
		$from = new Horde_Mail_Rfc822_Address($message->getFrom());
		$from->personal = $this->getName();
		$headers = [
			'From' => $from,
			'To' => $message->getToList(),
			'Cc' => $message->getCCList(),
			'Bcc' => $message->getBCCList(),
			'Subject' => $message->getSubject(),
		];

		if (!is_null($message->getRepliedMessage())) {
			$headers['In-Reply-To'] = $message->getRepliedMessage()->getMessageId();
		}

		$mail = new Horde_Mime_Mail();
		$mail->addHeaders($headers);
		$mail->setBody($message->getContent());

		// Append attachments
		foreach ($message->getAttachments() as $attachment) {
			$mail->addMimePart($attachment);
		}

		// Send the message
		$transport = $this->createTransport();
		$mail->send($transport);

		// Save the message in the sent folder
		$sentFolder = $this->getSentFolder();
		/** @var resource $raw */
		$raw = stream_get_contents($mail->getRaw());
		$sentFolder->saveMessage($raw, [
			Horde_Imap_Client::FLAG_SEEN
		]);

		// Delete draft if one exists
		if (!is_null($draftUID)) {
			$draftsFolder = $this->getDraftsFolder();
			$draftsFolder->setMessageFlag($draftUID, Horde_Imap_Client::FLAG_DELETED, true);
			$this->deleteDraft($draftUID);
		}
	}

	/**
	 * @param string $mailBox
	 */
	public function deleteMailbox($mailBox) {
		if ($mailBox instanceof Mailbox) {
			$mailBox = $mailBox->getFolderId();
		}
		$conn = $this->getImapConnection();
		$conn->deleteMailbox($mailBox);
		$this->mailboxes = null;
	}

	/**
	 * Lists mailboxes (folders) for this account.
	 *
	 * Lists mailboxes and also queries the server for their 'special use',
	 * eg. inbox, sent, trash, etc
	 *
	 * @param string $pattern Pattern to match mailboxes against. All by default.
	 * @return Mailbox[]
	 */
	protected function listMailboxes($pattern = '*') {
		// open the imap connection
		$conn = $this->getImapConnection();

		// if successful -> get all folders of that account
		$mailBoxes = $conn->listMailboxes($pattern, Horde_Imap_Client::MBOX_ALL,
			[
			'delimiter' => true,
			'attributes' => true,
			'special_use' => true,
			'sort' => true
		]);

		$mailboxes = [];
		foreach ($mailBoxes as $mailbox) {
			$mailboxes[] = new Mailbox($conn, $mailbox['mailbox'],
				$mailbox['attributes'], $mailbox['delimiter']);
			if ($mailbox['mailbox']->utf8 === 'INBOX') {
				$mailboxes[] = new SearchMailbox($conn, $mailbox['mailbox'],
					$mailbox['attributes'], $mailbox['delimiter']);
			}
		}

		return $mailboxes;
	}

	/**
	 * @param string $folderId
	 * @return \OCA\Mail\Mailbox
	 */
	public function getMailbox($folderId) {
		$conn = $this->getImapConnection();
		$parts = explode('/', $folderId);
		if (count($parts) > 1 && $parts[1] === 'FLAGGED') {
			$mailbox = new Horde_Imap_Client_Mailbox($parts[0]);
			return new SearchMailbox($conn, $mailbox, []);
		}
		$mailbox = new Horde_Imap_Client_Mailbox($folderId);
		return new Mailbox($conn, $mailbox, []);
	}

	/**
	 * Get a list of all mailboxes in this account
	 *
	 * @return Mailbox[]
	 */
	public function getMailboxes() {
		if ($this->mailboxes === null) {
			$this->mailboxes = $this->listMailboxes();
			$this->sortMailboxes();
			$this->localizeSpecialMailboxes();
		}

		return $this->mailboxes;
	}

	/**
	 * @return array
	 */
	public function getListArray() {

		$folders = [];
		$mailBoxes = $this->getMailboxes();
		$mailBoxNames = array_map(function($mb) {
			/** @var Mailbox $mb */
			return $mb->getFolderId();
		}, array_filter($mailBoxes, function($mb) {
			/** @var Mailbox $mb */
			return (!$mb instanceof SearchMailbox) && (!in_array('\noselect', $mb->attributes()));
		}));

		$status = $this->getImapConnection()->status($mailBoxNames);
		foreach ($mailBoxes as $mailbox) {
			$s = isset($status[$mailbox->getFolderId()]) ? $status[$mailbox->getFolderId()] : null;
			$folders[] = $mailbox->getListArray($this->getId(), $s);
		}
		$delimiter = reset($folders)['delimiter'];
		return [
			'id' => $this->getId(),
			'email' => $this->getEMailAddress(),
			'folders' => array_values($folders),
			'specialFolders' => $this->getSpecialFoldersIds(),
			'delimiter' => $delimiter,
		];
	}

	/**
	 * @return Horde_Mail_Transport
	 */
	public function createTransport() {
		$transport = $this->config->getSystemValue('app.mail.transport', 'smtp');
		if ($transport === 'php-mail') {
			return new Horde_Mail_Transport_Mail();
		}

		$password = $this->account->getOutboundPassword();
		$password = $this->crypto->decrypt($password);
		$params = [
			'host' => $this->account->getOutboundHost(),
			'password' => $password,
			'port' => $this->account->getOutboundPort(),
			'username' => $this->account->getOutboundUser(),
			'secure' => $this->convertSslMode($this->account->getOutboundSslMode()),
			'timeout' => 2
		];
		return new Horde_Mail_Transport_Smtphorde($params);
	}

	/**
	 * Lists special use folders for this account.
	 *
	 * The special uses returned are the "best" one for each special role,
	 * picked amongst the ones returned by the server, as well
	 * as the one guessed by our code.
	 *
	 * @param bool $base64_encode
	 * @return array In the form [<special use>=><folder id>, ...]
	 */
	public function getSpecialFoldersIds($base64_encode=true) {
		$folderRoles = ['inbox', 'sent', 'drafts', 'trash', 'archive', 'junk', 'flagged', 'all'];
		$specialFoldersIds = [];

		foreach ($folderRoles as $role) {
			$folders = $this->getSpecialFolder($role, true);
			$specialFoldersIds[$role] = (count($folders) === 0) ? null : $folders[0]->getFolderId();
			if ($specialFoldersIds[$role] !== null && $base64_encode === true) {
				$specialFoldersIds[$role] = base64_encode($specialFoldersIds[$role]);
			}
		}
		return $specialFoldersIds;
	}

	/**
	 * Get the "drafts" mailbox
	 *
	 * @return Mailbox The best candidate for the "drafts" inbox
	 */
	public function getDraftsFolder() {
		// check for existence
		$draftsFolder = $this->getSpecialFolder('drafts', true);
		if (count($draftsFolder) === 0) {
			// drafts folder does not exist - let's create one
			$conn = $this->getImapConnection();
			// TODO: also search for translated drafts mailboxes
			$conn->createMailbox('Drafts', [
				'special_use' => ['drafts'],
			]);
			return $this->guessBestMailBox($this->listMailboxes('Drafts'));
		}
		return $draftsFolder[0];
	}

	/**
	 * @return IMailBox
	 */
	public function getInbox() {
		$folders = $this->getSpecialFolder('inbox', false);
		return count($folders) > 0 ? $folders[0] : null;
	}

	/**
	 * Get the "sent mail" mailbox
	 *
	 * @return Mailbox The best candidate for the "sent mail" inbox
	 */
	public function getSentFolder() {
		//check for existence
		$sentFolders = $this->getSpecialFolder('sent', true);
		if (count($sentFolders) === 0) {
			//sent folder does not exist - let's create one
			$conn = $this->getImapConnection();
			//TODO: also search for translated sent mailboxes
			$conn->createMailbox('Sent', [
				'special_use' => ['sent'],
			]);
			return $this->guessBestMailBox($this->listMailboxes('Sent'));
		}
		return $sentFolders[0];
	}

	/**
	 * @param string $sourceFolderId
	 * @param int $messageId
	 */
	public function deleteMessage($sourceFolderId, $messageId) {
		$mb = $this->getMailbox($sourceFolderId);
		$hordeSourceMailBox = $mb->getHordeMailBox();
		// by default we will create a 'Trash' folder if no trash is found
		$trashId = "Trash";
		$createTrash = true;

		$trashFolders = $this->getSpecialFolder('trash', true);

		if (count($trashFolders) !== 0) {
			$trashId = $trashFolders[0]->getFolderId();
			$createTrash = false;
		} else {
			// no trash -> guess
			$trashes = array_filter($this->getMailboxes(), function($box) {
				/**
				 * @var Mailbox $box
				 */
				return (stripos($box->getDisplayName(), 'trash') !== false);
			});
			if (!empty($trashes)) {
				$trashId = array_values($trashes);
				$trashId = $trashId[0]->getFolderId();
				$createTrash = false;
			}
		}

		$hordeMessageIds = new Horde_Imap_Client_Ids($messageId);
		$hordeTrashMailBox = new Horde_Imap_Client_Mailbox($trashId);

		if ($sourceFolderId === $trashId) {
			$this->getImapConnection()->expunge($hordeSourceMailBox,
				array('ids' => $hordeMessageIds, 'delete' => true));

			\OC::$server->getLogger()->info("Message expunged: {message} from mailbox {mailbox}",
				array('message' => $messageId, 'mailbox' => $sourceFolderId));
		} else {
			$this->getImapConnection()->copy($hordeSourceMailBox, $hordeTrashMailBox,
				array('create' => $createTrash, 'move' => true, 'ids' => $hordeMessageIds));

			\OC::$server->getLogger()->info("Message moved to trash: {message} from mailbox {mailbox}",
				array('message' => $messageId, 'mailbox' => $sourceFolderId, 'app' => 'mail'));
		}
	}

	/**
	 * 
	 * @param int $messageId
	 */
	public function deleteDraft($messageId) {
		$draftsFolder = $this->getDraftsFolder();
		
		$draftsMailBox = new \Horde_Imap_Client_Mailbox($draftsFolder->getFolderId(), true);
		$this->getImapConnection()->expunge($draftsMailBox);
	}

	/**
	 * Get 'best' mailbox guess
	 *
	 * For now the best candidate is the one with
	 * the most messages in it.
	 *
	 * @param array $folders
	 * @return Mailbox
	 */
	protected function guessBestMailBox(array $folders) {
		$maxMessages = -1;
		$bestGuess = null;
		foreach ($folders as $folder) {
			/** @var Mailbox $folder */
			if ($folder->getTotalMessages() > $maxMessages) {
				$maxMessages = $folder->getTotalMessages();
				$bestGuess = $folder;
			}
		}
		return $bestGuess;
	}

	/**
	 * Get mailbox(es) that have the given special use role
	 *
	 * With this method we can get a list of all mailboxes that have been
	 * determined to have a specific special use role. It can also return
	 * the best candidate for this role, for situations where we want
	 * one single folder.
	 *
	 * @param string $role Special role of the folder we want to get ('sent', 'inbox', etc.)
	 * @param bool $guessBest If set to true, return only the folder with the most messages in it
	 *
	 * @return Mailbox[] if $guessBest is false, or Mailbox if $guessBest is true. Empty [] if no match.
	 */
	protected function getSpecialFolder($role, $guessBest=true) {

		$specialFolders = [];
		foreach ($this->getMailboxes() as $mailbox) {
			if ($role === $mailbox->getSpecialRole()) {
				$specialFolders[] = $mailbox;
			}
		}

		if ($guessBest === true && count($specialFolders) > 1) {
			return [$this->guessBestMailBox($specialFolders)];
		} else {
			return $specialFolders;
		}
	}

	/**
	 *  Localizes the name of the special use folders
	 *
	 *  The display name of the best candidate folder for each special use
	 *  is localized to the user's language
	 */
	protected function localizeSpecialMailboxes() {

		$l = \OC::$server->getL10N('mail');
		$map = [
			// TRANSLATORS: translated mail box name
			'inbox'   => $l->t('Inbox'),
			// TRANSLATORS: translated mail box name
			'sent'    => $l->t('Sent'),
			// TRANSLATORS: translated mail box name
			'drafts'  => $l->t('Drafts'),
			// TRANSLATORS: translated mail box name
			'archive' => $l->t('Archive'),
			// TRANSLATORS: translated mail box name
			'trash'   => $l->t('Trash'),
			// TRANSLATORS: translated mail box name
			'junk'    => $l->t('Junk'),
			// TRANSLATORS: translated mail box name
			'all'     => $l->t('All'),
			// TRANSLATORS: translated mail box name
			'flagged' => $l->t('Favorites'),
		];
		$mailboxes = $this->getMailboxes();
		$specialIds = $this->getSpecialFoldersIds(false);
		foreach ($mailboxes as $i => $mailbox) {
			if (in_array($mailbox->getFolderId(), $specialIds) === true) {
				if (isset($map[$mailbox->getSpecialRole()])) {
					$translatedDisplayName = $map[$mailbox->getSpecialRole()];
					$mailboxes[$i]->setDisplayName((string)$translatedDisplayName);
				}
			}
		}
	}

	/**
	 * Sort mailboxes
	 *
	 * Sort the array of mailboxes with
	 *  - special use folders coming first in this order: all, inbox, flagged, drafts, sent, archive, junk, trash
	 *  - 'normal' folders coming after that, sorted alphabetically
	 */
	protected function sortMailboxes() {

		$mailboxes = $this->getMailboxes();
		usort($mailboxes, function($a, $b) {
			/**
			 * @var Mailbox $a
			 * @var Mailbox $b
			 */
			$roleA = $a->getSpecialRole();
			$roleB = $b->getSpecialRole();
			$specialRolesOrder = [
				'all'     => 0,
				'inbox'   => 1,
				'flagged' => 2,
				'drafts'  => 3,
				'sent'    => 4,
				'archive' => 5,
				'junk'    => 6,
				'trash'   => 7,
			];
			// if there is a flag unknown to us, we ignore it for sorting :
			// the folder will be sorted by name like any other 'normal' folder
			if (array_key_exists($roleA, $specialRolesOrder) === false) {
				$roleA = null;
			}
			if (array_key_exists($roleB, $specialRolesOrder) === false) {
				$roleB = null;
			}

			if ($roleA === null && $roleB !== null) {
				return 1;
			} elseif ($roleA !== null && $roleB === null) {
				return -1;
			} elseif ($roleA !== null && $roleB !== null) {
				if ($roleA === $roleB) {
					return strcasecmp($a->getdisplayName(), $b->getDisplayName());
				} else {
					return $specialRolesOrder[$roleA] - $specialRolesOrder[$roleB];
				}
			}
			// we get here if $roleA === null && $roleB === null
			return strcasecmp($a->getDisplayName(), $b->getDisplayName());
		});

		$this->mailboxes = $mailboxes;
	}

	/**
	 * Convert special security mode values into Horde parameters
	 *
	 * @param string $sslMode
	 * @return false|string
	 */
	protected function convertSslMode($sslMode) {
		switch ($sslMode) {
			case 'none':
				return false;
		}
		return $sslMode;
	}

	/**
	 * @param $query
	 * @return array
	 */
	public function getChangedMailboxes($query) {
		$imp = $this->getImapConnection();
		$allBoxes = $this->getMailboxes();
		$allBoxesMap = [];
		foreach ($allBoxes as $mb) {
			$allBoxesMap[$mb->getFolderId()] = $mb;
		}

		// filter non existing mailboxes
		$mailBoxNames = array_filter(array_keys($query), function($folderId) use ($allBoxesMap) {
			return isset($allBoxesMap[$folderId]);
		});

		$status = $imp->status($mailBoxNames);

		// filter for changed mailboxes
		$changedBoxes = [];
		foreach ($status as $folderId => $s) {
			$uidValidity = $query[$folderId]['uidvalidity'];
			$uidNext = $query[$folderId]['uidnext'];

			if ($uidNext === $s['uidnext'] &&
				$uidValidity === $s['uidvalidity']) {
				continue;
			}
			// get unread messages
			if (isset($allBoxesMap[$folderId])) {
				/** @var Mailbox $m */
				$m = $allBoxesMap[$folderId];
				$role = $m->getSpecialRole();
				if (is_null($role) || $role === 'inbox') {
					$newMessages = $m->getMessagesSince($uidNext, $s['uidnext']);
					// only trigger updates in case new messages are actually available
					if (!empty($newMessages)) {
						$changedBoxes[$folderId] = $m->getListArray($this->getId(), $s);
						$changedBoxes[$folderId]['messages'] = $newMessages;
						$newUnreadMessages = array_filter($newMessages, function($m) {
							return $m['flags']['unseen'];
						});
						$changedBoxes[$folderId]['newUnReadCounter'] = count($newUnreadMessages);
					}
				}
			}
		}

		return $changedBoxes;
	}

	/**
	 * @throws \Horde_Imap_Client_Exception
	 */
	public function reconnect() {
		$this->mailboxes = null;
		if ($this->client) {
			$this->client->close();
			$this->client = null;
		}
		$this->getImapConnection();
	}

	/**
	 * @return array
	 */
	public function getConfiguration() {
		return $this->account->toJson();
	}

	/**
	 * @return string|Horde_Mail_Rfc822_List
	 */
	public function getEmail() {
		return $this->account->getEmail();
	}

	public function testConnectivity() {
		// connect to imap
		$this->getImapConnection();

		// connect to smtp
		$smtp = $this->createTransport();
		if ($smtp instanceof Horde_Mail_Transport_Smtphorde) {
			$smtp->getSMTPObject();
		}
	}

	/**
	 * Factory method for creating new messages
	 *
	 * @return OCA\Mail\Model\IMessage
	 */
	public function newMessage() {
		return new Message();
	}

	/**
	 * Factory method for creating new reply messages
	 *
	 * @return OCA\Mail\Model\ReplyMessage
	 */
	public function newReplyMessage() {
		return new ReplyMessage();
	}

}
