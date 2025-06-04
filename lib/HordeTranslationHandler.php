<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2014-2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OCA\Mail;

use Horde_Translation_Handler;

class HordeTranslationHandler implements Horde_Translation_Handler {
	/**
	 * Returns the translation of a message.
	 *
	 * @param string $message The string to translate.
	 *
	 * @return string The string translation, or the original string if no
	 *                translation exists.
	 */
	#[\Override]
	public function t($message) {
		return $message;
	}

	/**
	 * Returns the plural translation of a message.
	 *
	 * @param string $singular The singular version to translate.
	 * @param string $plural The plural version to translate.
	 * @param integer $number The number that determines singular vs. plural.
	 *
	 * @return string The string translation, or the original string if no
	 *                translation exists.
	 */
	#[\Override]
	public function ngettext($singular, $plural, $number) {
		return ($number > 1 ? $plural : $singular);
	}
}
