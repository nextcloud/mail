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
namespace OCA\Mail\Model;

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
