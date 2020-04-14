<?php

declare(strict_types=1);

namespace OCA\Mail\Exception;

use OCA\Mail\Db\Mailbox;

class MailboxNotCachedException extends ClientException {
	public static function from(Mailbox $mailbox): self {
		return new self("mailbox {$mailbox->getId()} is not cached");
	}
}
