<?php
namespace OCA\Mail\Storage;

class AvatarStorage {

	private $storage;

	public function __construct($storage) {
		// Create the node data/$user/mail/avatars
		if (!$storage->nodeExists('mail')) {
			$storage->newFolder('mail');
		}
		$node = $storage->get('mail');

		if (!$node->nodeExists('avatars')) {
			$node->newFolder('avatars');
		}

		$this->storage = $node->get('avatars');
	}

	public function save($email, $content) {
		// check if file exists and write to it if possible
		$filename = $this->getFilename($email);

		try {
			$file = $this->storage->get($filename);
		} catch(\OCP\Files\NotFoundException $e) {
			$this->storage->newFile($filename);
			$file = $this->storage->get($filename);
		}

		// the id can be accessed by $file->getId();
		$file->putContent($content);
	}

	public function read($email) {
		return $this->storage->get($this->getFilename($email))->getContent();
	}

	private function getFilename($email) {
		return md5($email);
	}
}
