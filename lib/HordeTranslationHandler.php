<?php

/**
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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
namespace OCA\Mail;

use Horde_Translation_Handler;

class HordeTranslationHandler implements Horde_Translation_Handler {
	/**
	 * Returns the translation of a message.
	 *
	 * @var string $message  The string to translate.
	 *
	 * @return string  The string translation, or the original string if no
	 *                 translation exists.
	 */
	public function t($message) {
		return $message;
	}

	/**
	 * Returns the plural translation of a message.
	 *
	 * @param string $singular  The singular version to translate.
	 * @param string $plural    The plural version to translate.
	 * @param integer $number   The number that determines singular vs. plural.
	 *
	 * @return string  The string translation, or the original string if no
	 *                 translation exists.
	 */
	public function ngettext($singular, $plural, $number) {
		return ($number > 1 ? $plural : $singular);
	}
}
