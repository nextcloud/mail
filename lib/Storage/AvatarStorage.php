<?php
namespace OCA\Mail\Storage;

use OCP\Files\IAppData;
use OCP\Files\NotFoundException;

class AvatarStorage {

	private $storage;

	public function __construct(IAppData $appData) {
		try {
			$this->storage = $appData->getFolder('avatars');
		} catch (NotFoundException $e) {
			$this->storage = $appData->newFolder('avatar');
		}
	}

	public function save($email, $content) {
		// check if file exists and write to it if possible
		$filename = $this->getFilename($email);

		if (!$this->storage->fileExists($filename)) {
			$this->storage->newFile($filename);
		}

		$file = $this->storage->getFile($filename);

		// the id can be accessed by $file->getId();
		$file->putContent($content);
	}

	public function read($email) {
		return $this->storage->getFile($this->getFilename($email))->getContent();
	}

	private function getFilename($email) {
		return md5($email);
	}
}
