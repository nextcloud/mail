<?php

namespace OCA\Mail\Model;

/**
 * ownCloud - Mail
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @copyright Christoph Wurst 2015
 */
class ReplyMessage extends Message {

	public function setSubject($subject) {
		// prevent 'Re: Re:' stacking
		if (strcasecmp(substr($subject, 0, 4), 'Re: ') === 0) {
			parent::setSubject($subject);
		} else {
			parent::setSubject("Re: $subject");
		}
	}

}
